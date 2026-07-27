<?php

namespace App\Services\LegacyErpImport;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Product;
use App\Models\ProductCurrencyPrice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\Finance\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

final class LegacyErpImportService
{
    /** @var array<int, int> */
    private array $userMap = [];

    /** @var array<int, int> */
    private array $clientMap = [];

    /** @var array<int, int> */
    private array $supplierMap = [];

    /** @var array<int, int> */
    private array $productMap = [];

    /** @var array<int, int> */
    private array $invoiceMap = [];

    /** @var array<int, int> */
    private array $purchaseOrderMap = [];

    public function __construct(
        private readonly string $connection = 'legacy_erp'
    ) {}

    /**
     * @param  callable(string):void  $log
     * @return array<string, int>
     */
    public function run(callable $log, bool $dryRun = false): array
    {
        $this->assertLegacySchema();
        $counts = [
            'users' => 0,
            'clients' => 0,
            'suppliers' => 0,
            'products' => 0,
            'invoices' => 0,
            'invoice_lines' => 0,
            'client_payments' => 0,
            'purchase_orders' => 0,
            'purchase_order_lines' => 0,
            'supplier_payments' => 0,
            'expenses' => 0,
        ];

        if ($dryRun) {
            $legacy = DB::connection($this->connection);
            $counts['users'] = $this->safeCount($legacy, 'users');
            $counts['clients'] = $this->safeCount($legacy, 'clients');
            $counts['suppliers'] = $this->safeCount($legacy, 'providers');
            $counts['products'] = $this->safeCount($legacy, 'products');
            $counts['invoices'] = $this->safeCount($legacy, 'sales');
            $counts['invoice_lines'] = $this->safeCount($legacy, 'sale_details');
            $counts['client_payments'] = $this->safeCount($legacy, 'payment_sales');
            $counts['purchase_orders'] = $this->safeCount($legacy, 'purchases');
            $counts['purchase_order_lines'] = $this->safeCount($legacy, 'purchase_details');
            $counts['supplier_payments'] = $this->safeCount($legacy, 'payment_purchases');
            $counts['expenses'] = $this->safeCount($legacy, 'expenses');
            $log('وضع التجربة: تم احتساب الصفوف في قاعدة ERP فقط دون إدراج.');

            return $counts;
        }

        $recordedBy = $this->ensureRecordedByUserId($log);
        $currency = (string) config('legacy_erp_import.default_currency', 'ILS');
        $pfx = config('legacy_erp_import.prefixes');

        DB::transaction(function () use ($log, $recordedBy, $currency, $pfx, &$counts) {
            $this->importUsers($log, $counts);
            $this->importClients($log, $counts, $currency, $pfx['client_match_key']);
            $this->importSuppliers($log, $counts, $pfx['supplier_legacy_number']);
            $this->importProducts($log, $counts, $currency, $pfx['product_code']);
            $this->importSalesAndLines($log, $counts, $recordedBy, $currency, $pfx['invoice_no']);
            $this->importPaymentSales($log, $counts, $recordedBy, $currency, $pfx['payment_sale_ref']);
            $this->importPurchasesAndLines($log, $counts, $recordedBy, $currency, $pfx['purchase_legacy_no']);
            $this->importPaymentPurchases($log, $counts, $recordedBy, $currency, $pfx['payment_purchase_ref']);
            $this->importExpenses($log, $counts, $recordedBy, $currency, $pfx['expense_note']);
        });

        DB::table('import_audit')->insert([
            'batch_name' => 'legacy_erp_import_'.now()->format('Y-m-d_His'),
            'source' => 'legacy_erp',
            'notes' => json_encode($counts, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);

        return $counts;
    }

    public function assertLegacySchema(): void
    {
        $driver = config("database.connections.{$this->connection}.driver");
        if ($driver === 'mysql' && empty(config("database.connections.{$this->connection}.database"))) {
            throw new \RuntimeException('اضبط LEGACY_ERP_DATABASE واتصال MySQL في .env قبل الترحيل.');
        }

        $legacy = DB::connection($this->connection);
        try {
            $legacy->getPdo();
        } catch (Throwable $e) {
            throw new \RuntimeException('تعذّر الاتصال بقاعدة ERP القديمة: '.$e->getMessage(), 0, $e);
        }

        foreach (['clients', 'sales', 'providers', 'purchases'] as $table) {
            if (! Schema::connection($this->connection)->hasTable($table)) {
                throw new \RuntimeException("جدول ERP المطلوب غير موجود: {$table}");
            }
        }
    }

    private function safeCount(Connection $legacy, string $table): int
    {
        if (! Schema::connection($this->connection)->hasTable($table)) {
            return 0;
        }

        return (int) $legacy->table($table)->count();
    }

    /**
     * @param  callable(string):void  $log
     */
    private function ensureRecordedByUserId(callable $log): int
    {
        $email = config('legacy_erp_import.recorded_by_email');
        if ($email) {
            $id = User::query()->where('email', $email)->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        $first = User::query()->orderBy('id')->value('id');
        if ($first) {
            return (int) $first;
        }

        $log('إنشاء مستخدم افتراضي للتسجيل أثناء الاستيراد…');

        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'gaz.local';
        $email = 'legacy-erp-import@'.$host;

        return (int) User::query()->firstOrCreate(
            ['email' => $email],
            [
                'full_name' => 'مستورد ERP',
                'password' => Str::password(24),
                'role' => 'manager',
                'is_active' => true,
            ]
        )->id;
    }

    /**
     * @param  callable(string):void  $log
     * @param  array<string, int>  $counts
     */
    private function importUsers(callable $log, array &$counts): void
    {
        $legacy = DB::connection($this->connection);
        if (! Schema::connection($this->connection)->hasTable('users')) {
            return;
        }

        $roleMap = config('legacy_erp_import.role_map', []);
        $defaultRole = (string) config('legacy_erp_import.default_role', 'accountant');

        foreach ($legacy->table('users')->whereNull('deleted_at')->orderBy('id')->cursor() as $row) {
            $email = $this->normalizeEmail($row->email ?? null) ?: 'erp_user_'.$row->id.'@gaz.local';
            $existing = User::query()->where('email', $email)->first();
            if ($existing) {
                $this->userMap[(int) $row->id] = (int) $existing->id;

                continue;
            }

            $role = $roleMap[(int) ($row->role_id ?? 0)] ?? $defaultRole;
            if (! in_array($role, ['viewer', 'accountant', 'manager'], true)) {
                $role = 'accountant';
            }

            $fullName = trim(($row->firstname ?? '').' '.($row->lastname ?? ''));
            if ($fullName === '') {
                $fullName = 'مستخدم ERP #'.$row->id;
            }

            $user = User::query()->create([
                'full_name' => $fullName,
                'email' => $email,
                'password' => Str::password(24),
                'role' => $role,
                'is_active' => (bool) ($row->statut ?? true),
            ]);

            $this->userMap[(int) $row->id] = (int) $user->id;
            $counts['users']++;
        }

        $log("مستخدمون مستوردون: {$counts['users']}");
    }

    /**
     * @param  callable(string):void  $log
     * @param  array<string, int>  $counts
     */
    private function importClients(callable $log, array &$counts, string $currency, string $matchPrefix): void
    {
        $legacy = DB::connection($this->connection);
        foreach ($legacy->table('clients')->whereNull('deleted_at')->orderBy('id')->cursor() as $row) {
            $key = $matchPrefix.$row->id;
            $country = $this->normalizeCountryCode($row->country ?? null);

            $client = Client::query()->firstOrCreate(
                ['legacy_match_key' => $key],
                [
                    'legacy_number' => (string) ($row->code ?? $row->id),
                    'business_name' => $row->name ?? 'عميل ERP #'.$row->id,
                    'email' => $this->normalizeEmail($row->email ?? null),
                    'phone_primary' => $this->normalizePhone($row->phone ?? null),
                    'city' => $row->city ?: null,
                    'address_line1' => $row->adresse ?: null,
                    'country_code' => $country,
                    'notes' => $this->buildClientNotes($row),
                    'assigned_user_id' => null,
                    'source_row_json' => [
                        'legacy_erp' => ['table' => 'clients', 'id' => (int) $row->id, 'currency_context' => $currency],
                    ],
                ]
            );

            $this->clientMap[(int) $row->id] = (int) $client->id;
            if ($client->wasRecentlyCreated) {
                $counts['clients']++;
            }
        }

        $log("عملاء (مفاتيح {$matchPrefix}*): {$counts['clients']} جديد");
    }

    /**
     * @param  callable(string):void  $log
     * @param  array<string, int>  $counts
     */
    private function importSuppliers(callable $log, array &$counts, string $legacyNumberPrefix): void
    {
        if (! Schema::connection($this->connection)->hasTable('providers')) {
            return;
        }

        $legacy = DB::connection($this->connection);
        foreach ($legacy->table('providers')->whereNull('deleted_at')->orderBy('id')->cursor() as $row) {
            $legacyNo = $legacyNumberPrefix.$row->id;
            $supplier = Supplier::query()->firstOrCreate(
                ['legacy_number' => $legacyNo],
                [
                    'business_name' => $row->name ?? 'مورد ERP #'.$row->id,
                    'email' => $this->normalizeEmail($row->email ?? null),
                    'phone_primary' => $this->normalizePhone($row->phone ?? null),
                    'city' => $row->city ?: null,
                    'address_line1' => $row->adresse ?: null,
                    'country_code' => $this->normalizeCountryCode($row->country ?? null),
                    'notes' => $this->buildProviderNotes($row),
                    'assigned_user_id' => null,
                    'source_row_json' => [
                        'legacy_erp' => ['table' => 'providers', 'id' => (int) $row->id],
                    ],
                ]
            );

            $this->supplierMap[(int) $row->id] = (int) $supplier->id;
            if ($supplier->wasRecentlyCreated) {
                $counts['suppliers']++;
            }
        }

        $log("موردون: {$counts['suppliers']} جديد");
    }

    /**
     * @param  callable(string):void  $log
     * @param  array<string, int>  $counts
     */
    private function importProducts(callable $log, array &$counts, string $currency, string $productCodePrefix): void
    {
        if (! Schema::connection($this->connection)->hasTable('products')) {
            return;
        }

        $legacy = DB::connection($this->connection);
        foreach ($legacy->table('products')->whereNull('deleted_at')->orderBy('id')->cursor() as $row) {
            $code = $productCodePrefix.$row->id;
            $name = $this->truncate((string) ($row->name ?? 'منتج ERP'), 255);

            $product = Product::query()->firstOrCreate(
                ['product_code' => $code],
                [
                    'name' => $name,
                    'description' => isset($row->note) ? $this->truncate((string) $row->note, 65535) : null,
                ]
            );

            $this->productMap[(int) $row->id] = (int) $product->id;

            $cost = $this->toDecimal($row->cost ?? 0);
            $minP = $this->toDecimal($row->min_price ?? 0);
            $price = $this->toDecimal($row->price ?? 0);

            ProductCurrencyPrice::query()->updateOrCreate(
                ['product_id' => $product->id, 'currency_code' => $currency],
                [
                    'service_cost_price' => $cost,
                    'min_sale_price' => $minP,
                    'sale_price' => $price,
                ]
            );

            if ($product->wasRecentlyCreated) {
                $counts['products']++;
            }
        }

        $log("منتجات (مع أسعار {$currency}): {$counts['products']} جديد");
    }

    /**
     * @param  callable(string):void  $log
     * @param  array<string, int>  $counts
     */
    private function importSalesAndLines(callable $log, array &$counts, int $recordedBy, string $currency, string $invoiceNoPrefix): void
    {
        $legacy = DB::connection($this->connection);
        if (! Schema::connection($this->connection)->hasTable('sale_details')) {
            $log('تحذير: لا يوجد جدول sale_details — تخطّي بنود الفواتير.');

            return;
        }

        foreach ($legacy->table('sales')->orderBy('id')->cursor() as $sale) {
            $legacyNo = $invoiceNoPrefix.$sale->id;
            if (Invoice::query()->where('legacy_invoice_no', $legacyNo)->exists()) {
                $existing = Invoice::query()->where('legacy_invoice_no', $legacyNo)->first();
                $this->invoiceMap[(int) $sale->id] = (int) $existing->id;

                continue;
            }

            $clientId = $this->clientMap[(int) $sale->client_id] ?? null;
            if (! $clientId) {
                $log("تخطّي فاتورة ERP-SALE-{$sale->id}: عميل غير معروف client_id={$sale->client_id}");

                continue;
            }

            $deleted = ! empty($sale->deleted_at);
            $status = $deleted ? 'void' : $this->mapSaleStatus($sale->statut ?? null, null);
            $total = $this->toDecimal($sale->GrandTotal ?? 0);
            $discount = $this->toDecimal($sale->discount ?? 0);

            $invoice = Invoice::query()->create([
                'client_id' => $clientId,
                'legacy_invoice_no' => $legacyNo,
                'document_date' => $this->toDateString($sale->date ?? now()),
                'issue_date' => $this->toDateString($sale->date ?? now()),
                'due_date' => null,
                'currency_code' => $currency,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'notes' => $this->truncate((string) ($sale->notes ?? ''), 65535),
                'status' => $status,
                'recorded_by_user_id' => $this->mapUserId((int) ($sale->user_id ?? 0)) ?? $recordedBy,
                'source_row_json' => [
                    'legacy_erp' => ['table' => 'sales', 'id' => (int) $sale->id, 'Ref' => $sale->Ref ?? null],
                ],
            ]);

            $this->invoiceMap[(int) $sale->id] = (int) $invoice->id;
            $counts['invoices']++;

            $lineOrder = 0;
            foreach ($legacy->table('sale_details')->where('sale_id', $sale->id)->orderBy('id')->cursor() as $d) {
                $lineOrder++;
                $title = $this->lineTitleFromProduct((int) $d->product_id, 'بند بيع #'.$lineOrder);
                $qtyFloat = max((float) ($d->quantity ?? 1), 0.0001);
                $qty = $this->toDecimal($qtyFloat);
                $lineTotal = $this->toDecimal($d->total ?? 0);
                $unit = $this->toDecimal($d->price ?? ($qtyFloat > 0 ? (float) $d->total / $qtyFloat : 0));

                InvoiceLine::query()->create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $this->productMap[(int) $d->product_id] ?? null,
                    'line_order' => $lineOrder,
                    'title' => $title,
                    'description' => null,
                    'unit_price' => $unit,
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                    'source_row_json' => [
                        'legacy_erp' => ['table' => 'sale_details', 'id' => (int) $d->id],
                    ],
                ]);
                $counts['invoice_lines']++;
            }
        }

        $log("فواتير من sales: {$counts['invoices']}، بنود: {$counts['invoice_lines']}");
    }

    /**
     * @param  callable(string):void  $log
     * @param  array<string, int>  $counts
     */
    private function importPaymentSales(callable $log, array &$counts, int $recordedBy, string $currency, string $refPrefix): void
    {
        if (! Schema::connection($this->connection)->hasTable('payment_sales')) {
            return;
        }

        $legacy = DB::connection($this->connection);
        foreach ($legacy->table('payment_sales')->whereNull('deleted_at')->orderBy('id')->cursor() as $p) {
            $ref = $refPrefix.$p->id;
            if (ClientPayment::query()->where('bank_reference', $ref)->exists()) {
                continue;
            }

            $sale = $legacy->table('sales')->where('id', $p->sale_id)->first();
            if (! $sale) {
                continue;
            }

            $clientId = $this->clientMap[(int) $sale->client_id] ?? null;
            if (! $clientId) {
                continue;
            }

            ClientPayment::query()->create([
                'client_id' => $clientId,
                'amount' => $this->toDecimal($p->montant ?? 0),
                'currency_code' => $currency,
                'paid_at' => $this->toDateTimeStart($p->date ?? $sale->date ?? now()),
                'method' => isset($p->payment_method_id)
                    ? PaymentMethod::normalize('طريقة #'.$p->payment_method_id)
                    : null,
                'bank_reference' => $ref,
                'notes' => $this->truncate((string) ($p->notes ?? ''), 65535),
                'recorded_by_user_id' => $this->mapUserId((int) ($p->user_id ?? 0)) ?? $recordedBy,
            ]);
            $counts['client_payments']++;
        }

        $log("دفعات عملاء من payment_sales: {$counts['client_payments']}");
    }

    /**
     * @param  callable(string):void  $log
     * @param  array<string, int>  $counts
     */
    private function importPurchasesAndLines(callable $log, array &$counts, int $recordedBy, string $currency, string $poPrefix): void
    {
        if (! Schema::connection($this->connection)->hasTable('purchase_details')) {
            $log('تحذير: لا يوجد purchase_details — تخطّي بنود المشتريات.');

            return;
        }

        $legacy = DB::connection($this->connection);
        foreach ($legacy->table('purchases')->orderBy('id')->cursor() as $po) {
            $legacyNo = $poPrefix.$po->id;
            if (PurchaseOrder::query()->where('legacy_po_no', $legacyNo)->exists()) {
                $existing = PurchaseOrder::query()->where('legacy_po_no', $legacyNo)->first();
                $this->purchaseOrderMap[(int) $po->id] = (int) $existing->id;

                continue;
            }

            $supplierId = $this->supplierMap[(int) $po->provider_id] ?? null;
            if (! $supplierId) {
                $log("تخطّي أمر شراء {$legacyNo}: مورد غير معروف provider_id={$po->provider_id}");

                continue;
            }

            $deleted = ! empty($po->deleted_at);
            $status = $deleted ? 'void' : $this->mapPurchaseStatus($po->statut ?? null, null);

            $order = PurchaseOrder::query()->create([
                'supplier_id' => $supplierId,
                'legacy_po_no' => $legacyNo,
                'document_date' => $this->toDateString($po->date ?? now()),
                'due_date' => null,
                'currency_code' => $currency,
                'discount_amount' => $this->toDecimal($po->discount ?? 0),
                'total_amount' => $this->toDecimal($po->GrandTotal ?? 0),
                'notes' => $this->truncate((string) ($po->notes ?? ''), 65535),
                'status' => $status,
                'recorded_by_user_id' => $this->mapUserId((int) ($po->user_id ?? 0)) ?? $recordedBy,
                'source_row_json' => [
                    'legacy_erp' => ['table' => 'purchases', 'id' => (int) $po->id, 'Ref' => $po->Ref ?? null],
                ],
            ]);

            $this->purchaseOrderMap[(int) $po->id] = (int) $order->id;
            $counts['purchase_orders']++;

            $lineOrder = 0;
            foreach ($legacy->table('purchase_details')->where('purchase_id', $po->id)->orderBy('id')->cursor() as $d) {
                $lineOrder++;
                $title = $this->lineTitleFromProduct((int) $d->product_id, 'بند شراء #'.$lineOrder);
                $qtyFloat = max((float) ($d->quantity ?? 1), 0.0001);
                $qty = $this->toDecimal($qtyFloat);
                $lineTotal = $this->toDecimal($d->total ?? 0);
                $unit = $this->toDecimal($d->cost ?? ($qtyFloat > 0 ? (float) $d->total / $qtyFloat : 0));

                PurchaseOrderLine::query()->create([
                    'purchase_order_id' => $order->id,
                    'line_order' => $lineOrder,
                    'title' => $title,
                    'description' => null,
                    'unit_price' => $unit,
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                    'source_row_json' => [
                        'legacy_erp' => ['table' => 'purchase_details', 'id' => (int) $d->id],
                    ],
                ]);
                $counts['purchase_order_lines']++;
            }
        }

        $log("أوامر شراء: {$counts['purchase_orders']}، بنود: {$counts['purchase_order_lines']}");
    }

    /**
     * @param  callable(string):void  $log
     * @param  array<string, int>  $counts
     */
    private function importPaymentPurchases(callable $log, array &$counts, int $recordedBy, string $currency, string $refPrefix): void
    {
        if (! Schema::connection($this->connection)->hasTable('payment_purchases')) {
            return;
        }

        $legacy = DB::connection($this->connection);
        foreach ($legacy->table('payment_purchases')->whereNull('deleted_at')->orderBy('id')->cursor() as $p) {
            $ref = $refPrefix.$p->id;
            if (SupplierPayment::query()->where('bank_reference', $ref)->exists()) {
                continue;
            }

            $pur = $legacy->table('purchases')->where('id', $p->purchase_id)->first();
            if (! $pur) {
                continue;
            }

            $supplierId = $this->supplierMap[(int) $pur->provider_id] ?? null;
            if (! $supplierId) {
                continue;
            }

            SupplierPayment::query()->create([
                'supplier_id' => $supplierId,
                'amount' => $this->toDecimal($p->montant ?? 0),
                'currency_code' => $currency,
                'paid_at' => $this->toDateTimeStart($p->date ?? $pur->date ?? now()),
                'method' => isset($p->payment_method_id)
                    ? PaymentMethod::normalize('طريقة #'.$p->payment_method_id)
                    : null,
                'bank_reference' => $ref,
                'notes' => $this->truncate((string) ($p->notes ?? ''), 65535),
                'recorded_by_user_id' => $this->mapUserId((int) ($p->user_id ?? 0)) ?? $recordedBy,
            ]);
            $counts['supplier_payments']++;
        }

        $log("دفعات موردين: {$counts['supplier_payments']}");
    }

    /**
     * @param  callable(string):void  $log
     * @param  array<string, int>  $counts
     */
    private function importExpenses(callable $log, array &$counts, int $recordedBy, string $currency, string $notePrefix): void
    {
        $legacy = DB::connection($this->connection);
        foreach ($legacy->table('expenses')->whereNull('deleted_at')->orderBy('id')->cursor() as $e) {
            $marker = $notePrefix.$e->id;
            if (Expense::query()->where('notes', 'like', $marker.'%')->exists()) {
                continue;
            }

            $desc = $this->truncate((string) ($e->details ?? 'مصروف ERP'), 255);
            $notes = $marker."\n".'مرجع قديم: '.($e->Ref ?? '')."\n".'مستورد من ERP';

            Expense::query()->create([
                'description' => $desc,
                'amount' => $this->toDecimal($e->amount ?? 0),
                'currency_code' => $currency,
                'expense_date' => $this->toDateString($e->date ?? now()),
                'notes' => $this->truncate($notes, 65535),
                'recorded_by_user_id' => $this->mapUserId((int) ($e->user_id ?? 0)) ?? $recordedBy,
                'source_row_json' => [
                    'legacy_erp' => ['table' => 'expenses', 'id' => (int) $e->id],
                ],
            ]);
            $counts['expenses']++;
        }

        $log("مصروفات: {$counts['expenses']}");
    }

    private function mapUserId(int $legacyUserId): ?int
    {
        if ($legacyUserId <= 0) {
            return null;
        }

        return $this->userMap[$legacyUserId] ?? null;
    }

    private function lineTitleFromProduct(int $legacyProductId, string $fallback): string
    {
        if ($legacyProductId <= 0) {
            return $fallback;
        }

        $legacy = DB::connection($this->connection);
        if (Schema::connection($this->connection)->hasTable('products')) {
            $name = $legacy->table('products')->where('id', $legacyProductId)->value('name');
            if ($name) {
                return $this->truncate((string) $name, 255);
            }
        }

        return $fallback;
    }

    private function mapSaleStatus(?string $statut, mixed $deletedAt): string
    {
        if ($deletedAt) {
            return 'void';
        }

        $s = strtolower((string) $statut);
        if (in_array($s, ['canceled', 'cancelled', 'void'], true)) {
            return 'void';
        }
        if (in_array($s, ['draft', 'pending'], true)) {
            return 'draft';
        }

        return 'issued';
    }

    private function mapPurchaseStatus(?string $statut, mixed $deletedAt): string
    {
        return $this->mapSaleStatus($statut, $deletedAt);
    }

    private function buildClientNotes(object $row): ?string
    {
        $parts = [];
        if (! empty($row->tax_number)) {
            $parts[] = 'الرقم الضريبي (قديم): '.$row->tax_number;
        }
        if (isset($row->opening_balance) && (float) $row->opening_balance != 0.0) {
            $parts[] = 'رصيد افتتاحي (قديم): '.$row->opening_balance;
        }

        return count($parts) ? implode("\n", $parts) : null;
    }

    private function buildProviderNotes(object $row): ?string
    {
        $parts = [];
        if (! empty($row->tax_number)) {
            $parts[] = 'الرقم الضريبي (قديم): '.$row->tax_number;
        }

        return count($parts) ? implode("\n", $parts) : null;
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = $email !== null ? trim($email) : '';

        return $email !== '' ? Str::lower($email) : null;
    }

    private function normalizePhone(?string $phone): ?string
    {
        $phone = $phone !== null ? trim($phone) : '';

        return $phone !== '' ? $this->truncate($phone, 64) : null;
    }

    private function normalizeCountryCode(?string $c): ?string
    {
        $c = $c !== null ? strtoupper(trim($c)) : '';
        if (strlen($c) === 2) {
            return $c;
        }

        return null;
    }

    private function toDecimal(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '0.0000';
        }

        return number_format((float) $v, 4, '.', '');
    }

    private function toDateString(mixed $d): string
    {
        if ($d instanceof \DateTimeInterface) {
            return $d->format('Y-m-d');
        }

        return substr((string) $d, 0, 10) ?: now()->toDateString();
    }

    private function toDateTimeStart(mixed $d): string
    {
        return $this->toDateString($d).' 00:00:00';
    }

    private function truncate(string $s, int $max): string
    {
        return Str::limit($s, $max, '');
    }
}
