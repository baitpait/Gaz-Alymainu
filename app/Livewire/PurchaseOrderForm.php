<?php

namespace App\Livewire;

use App\Enums\WarehouseType;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\PurchaseOrderPaymentAllocationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

/**
 * Business Purpose: فاتورة مشتريات مع اختيار منتجات من الكتالوج وترحيل مخزون عند الإصدار.
 */
class PurchaseOrderForm extends Component
{
    public ?int $purchaseOrderId = null;

    public string $supplier_id = '';

    public string $legacy_po_no = '';

    public string $document_date = '';

    public string $due_date = '';

    public string $currency_code = 'ILS';

    public string $total_amount = '0';

    public string $discount_amount = '0';

    public string $status = 'draft';

    public string $notes = '';

    public string $receiving_warehouse_id = '';

    /** unpaid | partial | paid — create only; persisted via supplier_payments */
    public string $payment_collection = 'unpaid';

    public string $payment_amount = '';

    public string $payment_method = 'cash';

    public string $paid_at = '';

    /** @var array<int, array{product_id:string, title:string, description:string, unit_price:string, quantity:string, line_total:string}> */
    public array $lines = [];

    public function mount(?PurchaseOrder $purchaseOrder = null): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);

        if ($purchaseOrder && $purchaseOrder->exists) {
            Gate::authorize('update', $purchaseOrder);
            $purchaseOrder->load(['lines.product']);
            $this->purchaseOrderId = $purchaseOrder->id;
            $this->supplier_id = (string) $purchaseOrder->supplier_id;
            $this->legacy_po_no = $purchaseOrder->legacy_po_no ?? '';
            $this->document_date = $purchaseOrder->document_date?->format('Y-m-d') ?? '';
            $this->due_date = $purchaseOrder->due_date?->format('Y-m-d') ?? '';
            $this->currency_code = $purchaseOrder->currency_code ?? 'ILS';
            $this->total_amount = (string) $purchaseOrder->total_amount;
            $this->discount_amount = (string) ($purchaseOrder->discount_amount ?? 0);
            $this->status = $purchaseOrder->status ?? 'draft';
            $this->notes = $purchaseOrder->notes ?? '';
            $this->receiving_warehouse_id = $purchaseOrder->receiving_warehouse_id
                ? (string) $purchaseOrder->receiving_warehouse_id
                : '';
            $this->lines = $purchaseOrder->lines->map(fn ($l) => [
                'product_id' => $l->product_id ? (string) $l->product_id : '',
                'title' => $l->title ?? '',
                'description' => $l->description ?? '',
                'unit_price' => (string) $l->unit_price,
                'quantity' => (string) $l->quantity,
                'line_total' => (string) $l->line_total,
            ])->toArray();
        } else {
            Gate::authorize('create', PurchaseOrder::class);
            $this->document_date = now()->format('Y-m-d');
            $this->paid_at = now()->format('Y-m-d');
            $this->status = 'issued';
            $prefillSupplierId = request()->integer('supplier');
            if ($prefillSupplierId > 0 && Supplier::query()->whereKey($prefillSupplierId)->exists()) {
                $this->supplier_id = (string) $prefillSupplierId;
            }
            $defaultWh = Warehouse::query()
                ->where('type', WarehouseType::Fixed)
                ->where('is_active', true)
                ->orderBy('name')
                ->value('id');
            if ($defaultWh) {
                $this->receiving_warehouse_id = (string) $defaultWh;
            }
        }

        if (count($this->lines) === 0) {
            $this->addLine();
        }
    }

    public function updatedLines(mixed $value, ?string $key = null): void
    {
        if ($key === null || $key === '') {
            return;
        }

        $parts = explode('.', $key);
        if (count($parts) === 2 && $parts[1] === 'product_id') {
            $i = (int) $parts[0];
            if (trim((string) ($this->lines[$i]['product_id'] ?? '')) === '') {
                return;
            }
            $this->applyProductToLine($i);

            return;
        }

        if (count($parts) === 2 && in_array($parts[1], ['unit_price', 'quantity'], true)) {
            $i = (int) $parts[0];
            $price = (float) ($this->lines[$i]['unit_price'] ?? 0);
            $qty = (float) ($this->lines[$i]['quantity'] ?? 1);
            $this->lines[$i]['line_total'] = (string) round($price * $qty, 4);
            $this->recalcTotal();
        }
    }

    private function applyProductToLine(int $i): void
    {
        $pid = (int) ($this->lines[$i]['product_id'] ?? 0);
        if ($pid <= 0) {
            return;
        }

        $product = Product::query()->with('currencyPrices')->find($pid);
        if ($product === null) {
            $this->lines[$i]['product_id'] = '';

            return;
        }

        $this->lines[$i]['title'] = $product->name;

        $row = $product->priceRowForCurrency($this->currency_code)
            ?? $product->priceRowForCurrency('ILS');

        if ($row !== null && $row->service_cost_price !== null) {
            $this->lines[$i]['unit_price'] = (string) $row->service_cost_price;
            $qty = (float) ($this->lines[$i]['quantity'] ?? 1);
            $this->lines[$i]['line_total'] = (string) round((float) $row->service_cost_price * $qty, 4);
            $this->recalcTotal();
        }
    }

    public function updatedDiscountAmount(): void
    {
        $this->recalcTotal();
    }

    public function updatedStatus(): void
    {
        if ($this->status !== 'issued') {
            $this->payment_collection = 'unpaid';
            $this->payment_amount = '';
        }
    }

    public function updatedPaymentCollection(): void
    {
        if ($this->payment_collection === 'paid') {
            $this->payment_amount = $this->total_amount;
        } elseif ($this->payment_collection === 'unpaid') {
            $this->payment_amount = '';
        }
    }

    public function updatedTotalAmount(): void
    {
        if ($this->payment_collection === 'paid') {
            $this->payment_amount = $this->total_amount;
        }
    }

    private function recalcTotal(): void
    {
        $subtotal = collect($this->lines)
            ->filter(fn ($l) => trim((string) ($l['product_id'] ?? '')) !== '')
            ->sum(fn ($l) => (float) ($l['line_total'] ?? 0));
        $net = max(0, $subtotal - (float) ($this->discount_amount ?? 0));
        $this->total_amount = (string) round($net, 2);
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'product_id' => '',
            'title' => '',
            'description' => '',
            'unit_price' => '',
            'quantity' => '1',
            'line_total' => '0',
        ];
    }

    public function removeLine(int $index): void
    {
        array_splice($this->lines, $index, 1);
        $this->lines = array_values($this->lines);
        $this->recalcTotal();
    }

    private function syncLineTotalsFromInputs(): void
    {
        foreach ($this->lines as $i => $line) {
            $p = (float) ($line['unit_price'] ?? 0);
            $q = (float) ($line['quantity'] ?? 0);
            $this->lines[$i]['line_total'] = (string) round($p * $q, 4);
        }
        $this->recalcTotal();
    }

    private function linesNeedStockPosting(array $titledLines): bool
    {
        foreach ($titledLines as $line) {
            $pid = isset($line['product_id']) && $line['product_id'] !== '' ? (int) $line['product_id'] : 0;
            if ($pid <= 0 || (float) ($line['quantity'] ?? 0) <= 0) {
                continue;
            }
            $product = Product::query()->find($pid);
            if ($product && $product->is_stock_tracked) {
                return true;
            }
        }

        return false;
    }

    public function save(InventoryService $inventory): void
    {
        if ($this->purchaseOrderId) {
            Gate::authorize('update', PurchaseOrder::findOrFail($this->purchaseOrderId));
        } else {
            Gate::authorize('create', PurchaseOrder::class);
        }

        $this->syncLineTotalsFromInputs();

        // Drop empty placeholder rows before validation
        $this->lines = array_values(array_filter(
            $this->lines,
            fn ($l) => trim((string) ($l['product_id'] ?? '')) !== ''
        ));
        if ($this->lines === []) {
            $this->addLine();
            $this->addError('lines', 'أضف بنداً واحداً على الأقل.');

            return;
        }

        $rules = [
            'supplier_id' => 'required|exists:suppliers,id',
            'document_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:document_date',
            'currency_code' => 'required|string|size:3',
            'status' => 'required|in:draft,issued,void',
            'receiving_warehouse_id' => 'nullable|exists:warehouses,id',
            'legacy_po_no' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('purchase_orders', 'legacy_po_no')->ignore($this->purchaseOrderId),
            ],
            'lines' => 'array',
            'lines.*.product_id' => ['required', 'integer', Rule::exists(Product::class, 'id')],
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.unit_price' => 'required|numeric|min:0',
        ];

        if (! $this->purchaseOrderId && $this->status === 'issued') {
            $rules['payment_collection'] = 'required|in:unpaid,partial,paid';
            if (in_array($this->payment_collection, ['partial', 'paid'], true)) {
                $rules['payment_method'] = 'required|in:cash,bank,check,transfer';
                $rules['paid_at'] = 'required|date';
            }
            if ($this->payment_collection === 'partial') {
                $rules['payment_amount'] = 'required|numeric|min:0.01';
            }
        }

        $this->validate($rules, [
            'lines.*.product_id.required' => 'اختر منتجاً للبند',
            'lines.*.quantity.required' => 'الكمية مطلوبة',
            'lines.*.unit_price.required' => 'السعر مطلوب',
        ], [
            'supplier_id' => 'المورد',
            'document_date' => 'تاريخ المستند',
            'due_date' => 'تاريخ الاستحقاق',
            'currency_code' => 'العملة',
            'status' => 'حالة المستند',
            'legacy_po_no' => 'رقم المستند',
            'receiving_warehouse_id' => 'مخزن الاستلام',
            'payment_collection' => 'حالة الدفع',
            'payment_amount' => 'مبلغ الدفعة',
            'payment_method' => 'طريقة الدفع',
            'paid_at' => 'تاريخ الدفع',
            'lines.*.product_id' => 'المنتج',
        ]);

        // Ensure title is synced from product (UI no longer edits title/description)
        foreach ($this->lines as $i => $line) {
            $pid = (int) ($line['product_id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $product = Product::query()->find($pid);
            if ($product) {
                $this->lines[$i]['title'] = $product->name;
                $this->lines[$i]['description'] = '';
            }
        }

        $titledLines = array_values(array_filter(
            $this->lines,
            fn ($l) => trim((string) ($l['product_id'] ?? '')) !== ''
        ));
        if ($titledLines === []) {
            $this->addError('lines', 'أضف بنداً واحداً على الأقل.');

            return;
        }

        $needsStock = $this->status === 'issued' && $this->linesNeedStockPosting($titledLines);
        $existing = $this->purchaseOrderId
            ? PurchaseOrder::query()->find($this->purchaseOrderId)
            : null;
        $alreadyPosted = $existing && $existing->inventory_posted_at !== null;

        if ($needsStock && ! $alreadyPosted) {
            if (trim($this->receiving_warehouse_id) === '') {
                $this->addError('receiving_warehouse_id', 'اختر مخزن الاستلام لزيادة المخزون.');

                return;
            }
            $warehouse = Warehouse::query()->find((int) $this->receiving_warehouse_id);
            if ($warehouse === null || $warehouse->type !== WarehouseType::Fixed || ! $warehouse->is_active) {
                $this->addError('receiving_warehouse_id', 'يجب اختيار مخزن ثابت نشط.');

                return;
            }
        }

        $subtotal = collect($titledLines)->sum(fn ($l) => (float) ($l['line_total'] ?? 0));
        $this->total_amount = (string) max(0, round($subtotal - (float) ($this->discount_amount ?? 0), 2));

        if (empty($this->total_amount) || (float) $this->total_amount <= 0) {
            $this->addError('total_amount', 'المبلغ الإجمالي مطلوب ويجب أن يكون أكبر من صفر');

            return;
        }

        $orderTotal = (float) $this->total_amount;

        if (! $this->purchaseOrderId && $this->status === 'issued' && $this->payment_collection === 'partial') {
            $payAmount = (float) $this->payment_amount;
            if ($payAmount >= $orderTotal) {
                $this->addError('payment_amount', 'للدفع الجزئي يجب أن يكون المبلغ أقل من إجمالي المستند');

                return;
            }
        }

        $data = [
            'supplier_id' => $this->supplier_id,
            'legacy_po_no' => $this->legacy_po_no !== '' ? $this->legacy_po_no : null,
            'document_date' => $this->document_date,
            'due_date' => $this->due_date !== '' ? $this->due_date : null,
            'currency_code' => $this->currency_code,
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount !== '' ? $this->discount_amount : 0,
            'status' => $this->status,
            'notes' => $this->notes !== '' ? $this->notes : null,
            'recorded_by_user_id' => auth()->id(),
            'receiving_warehouse_id' => $this->receiving_warehouse_id !== ''
                ? (int) $this->receiving_warehouse_id
                : null,
        ];

        $collectPayment = ! $this->purchaseOrderId
            && $this->status === 'issued'
            && in_array($this->payment_collection, ['partial', 'paid'], true);

        $paymentAmount = $collectPayment
            ? ($this->payment_collection === 'paid' ? $orderTotal : (float) $this->payment_amount)
            : null;

        $stockPosted = false;

        try {
            DB::transaction(function () use ($data, $titledLines, $collectPayment, $paymentAmount, $needsStock, $alreadyPosted, $inventory, &$stockPosted): void {
                if ($this->purchaseOrderId) {
                    $po = PurchaseOrder::findOrFail($this->purchaseOrderId);
                    $po->update($data);
                } else {
                    $po = PurchaseOrder::create($data);
                }

                $po->lines()->delete();
                foreach ($titledLines as $i => $line) {
                    $pid = (int) $line['product_id'];
                    $po->lines()->create([
                        'line_order' => $i,
                        'product_id' => $pid,
                        'title' => $line['title'] !== '' ? $line['title'] : (Product::query()->find($pid)?->name ?? 'منتج'),
                        'description' => null,
                        'unit_price' => (float) ($line['unit_price'] ?? 0),
                        'quantity' => (float) ($line['quantity'] ?? 1),
                        'line_total' => (float) ($line['line_total'] ?? 0),
                    ]);
                }

                if ($collectPayment && $paymentAmount !== null && $paymentAmount > 0) {
                    SupplierPayment::query()->create([
                        'supplier_id' => $po->supplier_id,
                        'amount' => $paymentAmount,
                        'currency_code' => $po->currency_code,
                        'paid_at' => $this->paid_at,
                        'method' => $this->payment_method,
                        'bank_reference' => null,
                        'notes' => 'دفع عند إنشاء فاتورة المشتريات #'.($po->legacy_po_no ?? $po->id),
                        'recorded_by_user_id' => auth()->id(),
                    ]);
                }

                if ($needsStock && ! $alreadyPosted && $po->receiving_warehouse_id) {
                    $warehouse = Warehouse::query()->findOrFail($po->receiving_warehouse_id);
                    $po->load('lines.product');
                    foreach ($po->lines as $line) {
                        if ($line->product_id === null || $line->product === null) {
                            continue;
                        }
                        if (! $line->product->is_stock_tracked) {
                            continue;
                        }
                        $qty = (float) $line->quantity;
                        if ($qty <= 0) {
                            continue;
                        }
                        $inventory->purchaseIn(
                            $warehouse,
                            $line->product,
                            $qty,
                            auth()->id(),
                            $po->document_date,
                            'فاتورة مشتريات #'.($po->legacy_po_no ?? $po->id),
                        );
                    }
                    $po->update(['inventory_posted_at' => now()]);
                    $stockPosted = true;
                }
            });
        } catch (Throwable $e) {
            $this->addError('receiving_warehouse_id', $e->getMessage());

            return;
        }

        $toast = $this->purchaseOrderId ? 'تم تحديث فاتورة المشتريات' : 'تم إضافة فاتورة المشتريات بنجاح';
        if ($collectPayment) {
            $toast .= ' وتسجيل الدفعة';
        }
        if ($stockPosted) {
            $toast .= ' وزيادة المخزون';
        }
        session()->flash('toast', $toast);
        $this->redirect(route('purchase-orders.index'), navigate: true);
    }

    public function render()
    {
        $suppliers = Supplier::query()
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->get();

        $warehouses = Warehouse::query()
            ->where('type', WarehouseType::Fixed)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::query()
            ->orderByDesc('is_stock_tracked')
            ->orderBy('name')
            ->get(['id', 'name', 'product_code']);

        $subtotal = collect($this->lines)
            ->filter(fn ($l) => trim((string) ($l['product_id'] ?? '')) !== '')
            ->sum(fn ($l) => (float) ($l['line_total'] ?? 0));

        $computedPaymentStatus = null;
        if ($this->purchaseOrderId) {
            $existing = PurchaseOrder::query()->find($this->purchaseOrderId);
            if ($existing) {
                $computedPaymentStatus = (new PurchaseOrderPaymentAllocationService)->forPurchaseOrder($existing);
            }
        }

        return view('livewire.purchase-order-form', [
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'products' => $products,
            'subtotal' => $subtotal,
            'computedPaymentStatus' => $computedPaymentStatus,
        ]);
    }
}
