<?php

namespace App\Livewire;

use App\Livewire\Concerns\FiltersClientsForSelect;
use App\Models\ClientPayment;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductCurrencyPrice;
use App\Services\InvoicePaymentAllocationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class InvoiceForm extends Component
{
    use FiltersClientsForSelect;

    public ?int $invoiceId = null;

    public string $client_id = '';

    public string $clientSearch = '';

    public string $legacy_invoice_no = '';

    public string $document_date = '';

    public string $due_date = '';

    public string $currency_code = 'ILS';

    public string $total_amount = '0';

    public string $discount_amount = '0';

    public string $status = 'draft';

    public string $notes = '';

    /** unpaid | partial | paid — create only; persisted via client_payments, not on invoice row */
    public string $payment_collection = 'unpaid';

    public string $payment_amount = '';

    public string $payment_method = 'cash';

    public string $paid_at = '';

    /** @var array<int, array{product_id: string, product_search: string, title: string, description: string, unit_price: string, quantity: string, line_total: string}> */
    public array $lines = [];

    public ?int $productAutocompleteLine = null;

    /** @var list<array{id: int, name: string, product_code: ?string}> */
    public array $productAutocompleteHits = [];

    public bool $showQuickAddProductModal = false;

    public int $quickAddLineIndex = 0;

    public string $quickAddName = '';

    public string $quickAddProductCode = '';

    public string $quickAddSalePrice = '';

    public string $quickAddMinSalePrice = '0';

    public string $quickAddServiceCost = '0';

    public function mount(?Invoice $invoice = null): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);

        if ($invoice && $invoice->exists) {
            Gate::authorize('update', $invoice);
            $invoice->load(['lines.product', 'client']);
            $this->invoiceId = $invoice->id;
            $this->client_id = (string) $invoice->client_id;
            $this->clientSearch = $invoice->client?->displayName() ?? '';
            $this->legacy_invoice_no = $invoice->legacy_invoice_no ?? '';
            $this->document_date = $invoice->document_date?->format('Y-m-d') ?? '';
            $this->due_date = $invoice->due_date?->format('Y-m-d') ?? '';
            $this->currency_code = $invoice->currency_code ?? 'ILS';
            $this->total_amount = (string) $invoice->total_amount;
            $this->discount_amount = (string) ($invoice->discount_amount ?? 0);
            $this->status = $invoice->status === 'void' ? 'cancelled' : ($invoice->status ?? 'draft');
            $this->notes = $invoice->notes ?? '';
            $this->lines = $invoice->lines->map(fn ($l) => [
                'product_id' => $l->product_id ? (string) $l->product_id : '',
                'product_search' => $l->product_id && $l->product ? ($l->product->name ?? '') : '',
                'title' => $l->title ?? '',
                'description' => $l->description ?? '',
                'unit_price' => (string) $l->unit_price,
                'quantity' => (string) $l->quantity,
                'line_total' => (string) $l->line_total,
            ])->toArray();
        } else {
            Gate::authorize('create', Invoice::class);
            $this->document_date = now()->format('Y-m-d');
            $this->paid_at = now()->format('Y-m-d');
            $this->status = 'issued';
            $this->prefillClientSelect(request()->integer('client'));
        }

        if (count($this->lines) === 0) {
            $this->addLine();
        }
    }

    public function updatedCurrencyCode(): void
    {
        $this->productAutocompleteLine = null;
        $this->productAutocompleteHits = [];
        foreach (array_keys($this->lines) as $i) {
            if (trim((string) ($this->lines[$i]['product_id'] ?? '')) === '') {
                continue;
            }
            $this->applyProductToLine((int) $i);
        }
    }

    public function onProductSearchFocus(int $lineIndex): void
    {
        $this->productAutocompleteLine = $lineIndex;
        $this->refreshProductAutocompleteForLine($lineIndex);
    }

    public function updatedLines(mixed $value, ?string $key = null): void
    {
        if ($key === null || $key === '') {
            return;
        }

        $parts = explode('.', $key);
        if (count($parts) === 2 && $parts[1] === 'product_search') {
            $i = (int) $parts[0];
            $this->productAutocompleteLine = $i;
            if (trim((string) $value) === '') {
                $this->lines[$i]['product_id'] = '';
            } else {
                $pid = (int) ($this->lines[$i]['product_id'] ?? 0);
                if ($pid > 0) {
                    $p = Product::query()->find($pid);
                    if ($p && trim((string) $value) !== $p->name) {
                        $this->lines[$i]['product_id'] = '';
                    }
                }
            }
            $this->refreshProductAutocompleteForLine($i);
        }
        if (count($parts) === 2 && in_array($parts[1], ['unit_price', 'quantity'], true)) {
            $i = (int) $parts[0];
            $price = (float) ($this->lines[$i]['unit_price'] ?? 0);
            $qty = (float) ($this->lines[$i]['quantity'] ?? 1);
            $this->lines[$i]['line_total'] = (string) round($price * $qty, 4);
            $this->recalcTotal();
        }
    }

    public function refreshProductAutocompleteForLine(int $lineIndex): void
    {
        if (! isset($this->lines[$lineIndex])) {
            $this->productAutocompleteHits = [];

            return;
        }
        $q = trim($this->lines[$lineIndex]['product_search'] ?? '');
        if ($q === '' || mb_strlen($q) < 1) {
            $this->productAutocompleteHits = [];

            return;
        }
        $like = '%'.$q.'%';
        $this->productAutocompleteHits = Product::query()
            ->forSaleInCurrency($this->currency_code)
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('product_code', 'like', $like);
            })
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'product_code'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'product_code' => $p->product_code,
            ])
            ->all();
    }

    public function selectProductFromAutocomplete(int $lineIndex, int $productId): void
    {
        if (! isset($this->lines[$lineIndex])) {
            return;
        }
        $this->lines[$lineIndex]['product_id'] = (string) $productId;
        $this->applyProductToLine($lineIndex);
        $this->productAutocompleteHits = [];
        $this->productAutocompleteLine = null;
    }

    public function openQuickAddForLine(int $lineIndex): void
    {
        Gate::authorize('create', Product::class);
        if (! isset($this->lines[$lineIndex])) {
            return;
        }
        $this->quickAddLineIndex = $lineIndex;
        $this->quickAddName = trim($this->lines[$lineIndex]['product_search'] ?? '');
        $this->quickAddProductCode = '';
        $this->quickAddSalePrice = '';
        $this->quickAddMinSalePrice = '0';
        $this->quickAddServiceCost = '0';
        $this->showQuickAddProductModal = true;
        $this->productAutocompleteHits = [];
    }

    public function closeQuickAddProductModal(): void
    {
        $this->showQuickAddProductModal = false;
    }

    public function saveQuickAddProduct(): void
    {
        Gate::authorize('create', Product::class);

        $this->validate([
            'quickAddName' => 'required|string|max:255',
            'quickAddProductCode' => ['nullable', 'string', 'max:64', Rule::unique(Product::class, 'product_code')],
            'quickAddSalePrice' => 'required|numeric|min:0',
            'quickAddMinSalePrice' => 'required|numeric|min:0',
            'quickAddServiceCost' => 'required|numeric|min:0',
        ], [], [
            'quickAddName' => 'اسم المنتج',
            'quickAddProductCode' => 'رمز المنتج',
            'quickAddSalePrice' => 'سعر البيع',
            'quickAddMinSalePrice' => 'الحد الأدنى للبيع',
            'quickAddServiceCost' => 'تكلفة الخدمة',
        ]);

        if ((float) $this->quickAddMinSalePrice > (float) $this->quickAddSalePrice) {
            $this->addError('quickAddMinSalePrice', 'الحد الأدنى يجب أن يكون أقل أو يساوي سعر البيع');

            return;
        }

        $cc = $this->currency_code;
        if (! in_array($cc, Product::billingCurrencies(), true)) {
            $this->addError('currency_code', 'عملة غير معتمدة');

            return;
        }

        $lineIndex = $this->quickAddLineIndex;
        if (! isset($this->lines[$lineIndex])) {
            return;
        }

        $product = DB::transaction(function () use ($cc) {
            $p = Product::query()->create([
                'name' => $this->quickAddName,
                'product_code' => trim($this->quickAddProductCode) !== '' ? trim($this->quickAddProductCode) : null,
                'description' => null,
            ]);

            ProductCurrencyPrice::query()->updateOrCreate(
                [
                    'product_id' => $p->id,
                    'currency_code' => $cc,
                ],
                [
                    'sale_price' => (float) $this->quickAddSalePrice,
                    'min_sale_price' => (float) $this->quickAddMinSalePrice,
                    'service_cost_price' => (float) $this->quickAddServiceCost,
                ]
            );

            ProductCurrencyPrice::query()
                ->where('product_id', $p->id)
                ->whereIn('currency_code', array_values(array_diff(Product::billingCurrencies(), [$cc])))
                ->delete();

            return $p;
        });

        $this->lines[$lineIndex]['product_id'] = (string) $product->id;
        $this->applyProductToLine($lineIndex);
        $this->showQuickAddProductModal = false;
        $this->quickAddName = '';
        $this->quickAddProductCode = '';
        $this->quickAddSalePrice = '';
        $this->quickAddMinSalePrice = '0';
        $this->quickAddServiceCost = '0';
    }

    private function applyProductToLine(int $i): void
    {
        if (! isset($this->lines[$i])) {
            return;
        }
        $raw = $this->lines[$i]['product_id'] ?? '';
        if ($raw === '' || $raw === null) {
            return;
        }
        $pid = (int) $raw;
        $product = Product::query()->with('currencyPrices')->find($pid);
        if (! $product || ! $product->hasCompletePricingForCurrency($this->currency_code)) {
            $this->lines[$i]['product_id'] = '';
            $this->addError("lines.$i.product_id", 'لا يوجد تسعير كامل لهذا المنتج بعملة الفاتورة الحالية');

            return;
        }
        $tier = $product->priceRowForCurrency($this->currency_code);
        if ($tier === null) {
            $this->lines[$i]['product_id'] = '';

            return;
        }
        $this->lines[$i]['title'] = $product->name;
        $this->lines[$i]['product_search'] = $product->name;
        $this->lines[$i]['unit_price'] = (string) $tier->sale_price;
        $p = (float) $this->lines[$i]['unit_price'];
        $q = (float) ($this->lines[$i]['quantity'] ?? 1);
        $this->lines[$i]['line_total'] = (string) round($p * $q, 4);
        $this->recalcTotal();
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
        $titled = collect($this->lines)->filter(fn ($l) => trim((string) ($l['title'] ?? '')) !== '');
        if ($titled->isEmpty()) {
            return;
        }
        $subtotal = $titled->sum(fn ($l) => (float) ($l['line_total'] ?? 0));
        $net = max(0, $subtotal - (float) ($this->discount_amount ?? 0));
        $this->total_amount = (string) round($net, 2);
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

    public function addLine(): void
    {
        $this->lines[] = [
            'product_id' => '',
            'product_search' => '',
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
        $this->productAutocompleteLine = null;
        $this->productAutocompleteHits = [];
        $this->recalcTotal();
    }

    public function save(): void
    {
        if ($this->invoiceId) {
            Gate::authorize('update', Invoice::findOrFail($this->invoiceId));
        } else {
            Gate::authorize('create', Invoice::class);
        }

        $this->syncLineTotalsFromInputs();

        $rules = [
            'client_id' => 'required|exists:clients,id',
            'document_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:document_date',
            'currency_code' => ['required', 'string', 'size:3', Rule::in(Product::billingCurrencies())],
            'status' => 'required|in:draft,issued,cancelled',
            'lines' => 'array',
            'lines.*.product_id' => ['nullable', 'integer', Rule::exists(Product::class, 'id')],
            'lines.*.title' => 'required_with:lines|string|max:500',
            'lines.*.quantity' => 'required_with:lines|numeric|min:0',
            'lines.*.unit_price' => 'required_with:lines|numeric|min:0',
        ];

        if (! $this->invoiceId && $this->status === 'issued') {
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
            'lines.*.title.required_with' => 'اسم البند مطلوب',
            'lines.*.quantity.required_with' => 'الكمية مطلوبة',
            'lines.*.unit_price.required_with' => 'السعر مطلوب',
        ], [
            'client_id' => 'العميل',
            'document_date' => 'تاريخ الفاتورة',
            'due_date' => 'تاريخ الاستحقاق',
            'currency_code' => 'العملة',
            'status' => 'حالة المستند',
            'payment_collection' => 'حالة الدفع',
            'payment_amount' => 'مبلغ الدفعة',
            'payment_method' => 'طريقة الدفع',
            'paid_at' => 'تاريخ الدفع',
        ]);

        foreach ($this->lines as $i => $line) {
            if (trim((string) ($line['title'] ?? '')) === '') {
                continue;
            }
            $pid = isset($line['product_id']) && $line['product_id'] !== '' ? (int) $line['product_id'] : null;
            if ($pid === null) {
                continue;
            }
            $product = Product::query()->with('currencyPrices')->find($pid);
            if (! $product || ! $product->hasCompletePricingForCurrency($this->currency_code)) {
                $this->addError("lines.$i.product_id", 'لا يوجد تسعير كامل لهذا المنتج بعملة الفاتورة');

                return;
            }
            $tier = $product->priceRowForCurrency($this->currency_code);
            if ($tier === null) {
                $this->addError("lines.$i.product_id", 'بيانات التسعير غير متاحة');

                return;
            }
            $unit = (float) ($line['unit_price'] ?? 0);
            if ((float) $tier->min_sale_price > $unit) {
                $this->addError("lines.$i.unit_price", 'سعر الوحدة أقل من الحد الأدنى للبيع لهذا المنتج');

                return;
            }
        }

        $titledLines = array_values(array_filter($this->lines, fn ($l) => trim((string) ($l['title'] ?? '')) !== ''));
        if ($titledLines !== []) {
            $subtotal = collect($titledLines)->sum(fn ($l) => (float) ($l['line_total'] ?? 0));
            $this->total_amount = (string) max(0, round($subtotal - (float) ($this->discount_amount ?? 0), 2));
        }

        if (empty($this->total_amount) || (float) $this->total_amount < 0) {
            $this->addError('total_amount', 'المبلغ الإجمالي مطلوب');

            return;
        }

        $invoiceTotal = (float) $this->total_amount;

        if (! $this->invoiceId && $this->status === 'issued' && $this->payment_collection === 'partial') {
            $payAmount = (float) $this->payment_amount;
            if ($payAmount >= $invoiceTotal) {
                $this->addError('payment_amount', 'للدفع الجزئي يجب أن يكون المبلغ أقل من إجمالي الفاتورة');

                return;
            }
        }

        $storedStatus = $this->status === 'cancelled' ? 'void' : $this->status;

        $data = [
            'client_id' => $this->client_id,
            'legacy_invoice_no' => $this->legacy_invoice_no ?: null,
            'document_date' => $this->document_date,
            'due_date' => $this->due_date ?: null,
            'currency_code' => $this->currency_code,
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount ?: 0,
            'status' => $storedStatus,
            'notes' => $this->notes ?: null,
            'recorded_by_user_id' => auth()->id(),
        ];

        $collectPayment = ! $this->invoiceId
            && $storedStatus === 'issued'
            && in_array($this->payment_collection, ['partial', 'paid'], true);

        $paymentAmount = $collectPayment
            ? ($this->payment_collection === 'paid' ? $invoiceTotal : (float) $this->payment_amount)
            : null;

        DB::transaction(function () use ($data, $titledLines, $collectPayment, $paymentAmount): void {
            if ($this->invoiceId) {
                $invoice = Invoice::findOrFail($this->invoiceId);
                $invoice->update($data);
            } else {
                $invoice = Invoice::create($data);
            }

            $invoice->lines()->delete();
            foreach ($titledLines as $i => $line) {
                $pid = isset($line['product_id']) && $line['product_id'] !== '' ? (int) $line['product_id'] : null;
                $invoice->lines()->create([
                    'product_id' => $pid,
                    'line_order' => $i + 1,
                    'title' => $line['title'],
                    'description' => $line['description'] ?: null,
                    'unit_price' => (float) ($line['unit_price'] ?? 0),
                    'quantity' => (float) ($line['quantity'] ?? 1),
                    'line_total' => (float) ($line['line_total'] ?? 0),
                ]);
            }

            if ($collectPayment && $paymentAmount !== null && $paymentAmount > 0) {
                ClientPayment::query()->create([
                    'client_id' => $invoice->client_id,
                    'amount' => $paymentAmount,
                    'currency_code' => $invoice->currency_code,
                    'paid_at' => $this->paid_at,
                    'method' => $this->payment_method,
                    'bank_reference' => null,
                    'notes' => 'تحصيل عند إنشاء الفاتورة #'.($invoice->legacy_invoice_no ?? $invoice->id),
                    'recorded_by_user_id' => auth()->id(),
                ]);
            }
        });

        $toast = $this->invoiceId ? 'تم تحديث الفاتورة' : 'تم إضافة الفاتورة بنجاح';
        if ($collectPayment) {
            $toast .= ' وتسجيل الدفعة';
        }
        session()->flash('toast', $toast);
        $this->redirect(route('invoices.index'), navigate: true);
    }

    public function render()
    {
        $hasTitledLines = collect($this->lines)
            ->contains(fn ($l) => trim((string) ($l['title'] ?? '')) !== '');

        $computedPaymentStatus = null;
        if ($this->invoiceId) {
            $existing = Invoice::query()->find($this->invoiceId);
            if ($existing) {
                $computedPaymentStatus = (new InvoicePaymentAllocationService)->forInvoice($existing);
            }
        }

        return view('livewire.invoice-form', [
            'clients' => $this->clientsForSelect(),
            'subtotal' => collect($this->lines)
                ->filter(fn ($l) => trim((string) ($l['title'] ?? '')) !== '')
                ->sum(fn ($l) => (float) ($l['line_total'] ?? 0)),
            'hasTitledLines' => $hasTitledLines,
            'computedPaymentStatus' => $computedPaymentStatus,
        ]);
    }
}
