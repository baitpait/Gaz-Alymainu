<?php

use App\Http\Controllers\ClientPaymentPdfController;
use App\Http\Controllers\ClientPaymentPrintController;
use App\Http\Controllers\ClientReceivablesAgingController;
use App\Http\Controllers\ClientStatementController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\DriverDeviceTokenController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\InvoicePrintController;
use App\Http\Controllers\PurchaseOrderPdfController;
use App\Http\Controllers\PurchaseOrderPrintController;
use App\Http\Controllers\Reports\PeriodReportsController;
use App\Http\Controllers\Reports\SupplierReceivablesAgingController;
use App\Http\Controllers\SupplierPaymentPdfController;
use App\Http\Controllers\SupplierPaymentPrintController;
use App\Http\Controllers\SupplierStatementController;
use App\Models\Client;
use App\Models\ClientBalanceAdjustment;
use App\Models\ClientPayment;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LegacyCatalogProduct;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalaryPayment;
use App\Models\Supplier;
use App\Models\SupplierBalanceAdjustment;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InvoicePaymentAllocationService;
use App\Services\PurchaseOrderPaymentAllocationService;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'driver.sales', 'block.sales'])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
    Route::get('/profile', fn () => view('profile.show'))->name('profile');
    Route::get('/financial-summary', fn () => view('financial-summary'))->name('financial-summary');

    Route::get('/clients', fn () => view('clients.index'))->name('clients.index');
    Route::get('/clients/{client}/statement', [ClientStatementController::class, 'show'])->name('clients.statement');
    Route::get('/clients/{client}/statement/pdf', [ClientStatementController::class, 'pdf'])->name('clients.statement.pdf');
    Route::get('/clients/{client}/adjustments/create', function (Client $client) {
        abort_unless(auth()->user()->can('create', ClientBalanceAdjustment::class), 403);

        return view('clients.adjustments.create', compact('client'));
    })->name('clients.adjustments.create');
    Route::get('/clients/{client}/adjustments/{adjustment}/edit', function (Client $client, ClientBalanceAdjustment $adjustment) {
        abort_unless($adjustment->client_id === $client->id, 404);
        abort_unless(auth()->user()->can('update', $adjustment), 403);

        return view('clients.adjustments.edit', compact('client', 'adjustment'));
    })->name('clients.adjustments.edit');
    Route::delete('/clients/{client}/adjustments/{adjustment}', function (Client $client, ClientBalanceAdjustment $adjustment) {
        abort_unless($adjustment->client_id === $client->id, 404);
        abort_unless(auth()->user()->can('delete', $adjustment), 403);
        $adjustment->delete();

        return redirect()->route('clients.statement', $client)->with('toast', 'تم حذف التسوية');
    })->name('clients.adjustments.destroy');

    Route::get('/products', fn () => view('products.index'))->name('products.index');
    Route::get('/products/create', function () {
        abort_unless(auth()->user()->can('create', Product::class), 403);

        return view('products.create');
    })->name('products.create');
    Route::get('/products/{product}/edit', function (Product $product) {
        abort_unless(auth()->user()->can('update', $product), 403);

        return view('products.edit', compact('product'));
    })->name('products.edit');
    Route::delete('/products/{product}', function (Product $product) {
        abort_unless(auth()->user()->can('delete', $product), 403);
        $product->delete();

        return redirect()->route('products.index')->with('toast', 'تم حذف المنتج');
    })->name('products.destroy');

    Route::get('/legacy-catalog/products', function () {
        abort_unless(auth()->user()->can('viewAny', LegacyCatalogProduct::class), 403);

        return view('legacy-catalog.products');
    })->name('legacy-catalog-products.index');

    Route::get('/suppliers', fn () => view('suppliers.index'))->name('suppliers.index');

    Route::get('/purchase-orders', fn () => view('purchase-orders.index'))->name('purchase-orders.index');
    Route::get('/purchase-orders/create', function () {
        abort_unless(auth()->user()->can('create', PurchaseOrder::class), 403);

        return view('purchase-orders.create');
    })->name('purchase-orders.create');
    Route::get('/purchase-orders/{purchaseOrder}/edit', function (PurchaseOrder $purchaseOrder) {
        abort_unless(auth()->user()->can('update', $purchaseOrder), 403);

        return view('purchase-orders.edit', compact('purchaseOrder'));
    })->name('purchase-orders.edit');
    Route::get('/purchase-orders/{purchaseOrder}', function (PurchaseOrder $purchaseOrder) {
        abort_unless(auth()->user()->can('view', $purchaseOrder), 403);
        $purchaseOrder->load(['supplier', 'lines']);
        $paymentStatus = (new PurchaseOrderPaymentAllocationService)->forPurchaseOrder($purchaseOrder);

        return view('purchase-orders.show', compact('purchaseOrder', 'paymentStatus'));
    })->name('purchase-orders.show');
    Route::get('/purchase-orders/{purchaseOrder}/print', [PurchaseOrderPrintController::class, 'show'])->name('purchase-orders.print');
    Route::get('/purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderPdfController::class, 'show'])->name('purchase-orders.pdf');

    Route::get('/supplier-payments', fn () => view('supplier-payments.index'))->name('supplier-payments.index');
    Route::get('/supplier-payments/create', function () {
        abort_unless(auth()->user()->isAccountant(), 403);

        return view('supplier-payments.create');
    })->name('supplier-payments.create');
    Route::get('/supplier-payments/{supplierPayment}/edit', function (SupplierPayment $supplierPayment) {
        abort_unless(auth()->user()->isAccountant(), 403);

        return view('supplier-payments.edit', compact('supplierPayment'));
    })->name('supplier-payments.edit');
    Route::get('/supplier-payments/{supplierPayment}/print', [SupplierPaymentPrintController::class, 'show'])->name('supplier-payments.print');
    Route::get('/supplier-payments/{supplierPayment}/pdf', [SupplierPaymentPdfController::class, 'show'])->name('supplier-payments.pdf');
    Route::get('/supplier-payments/{supplierPayment}', fn (SupplierPayment $supplierPayment) => view('supplier-payments.show', compact('supplierPayment')))->name('supplier-payments.show');
    Route::delete('/supplier-payments/{supplierPayment}', function (SupplierPayment $supplierPayment) {
        abort_unless(auth()->user()->isManager(), 403);
        $supplierPayment->delete();

        return redirect()->route('supplier-payments.index')->with('toast', 'تم حذف الدفعة');
    })->name('supplier-payments.destroy');

    Route::get('/invoices', fn () => view('invoices.index'))->name('invoices.index');
    Route::get('/invoices/create', function () {
        abort_unless(auth()->user()->isAccountant(), 403);

        return view('invoices.create');
    })->name('invoices.create');
    Route::get('/invoices/{invoice}/edit', function (Invoice $invoice) {
        abort_unless(auth()->user()->isAccountant(), 403);

        return view('invoices.edit', compact('invoice'));
    })->name('invoices.edit');
    Route::get('/invoices/{invoice}', function (Invoice $invoice) {
        $invoice->load(['client', 'lines', 'recordedBy']);
        $paymentStatus = (new InvoicePaymentAllocationService)->forInvoice($invoice);

        return view('invoices.show', compact('invoice', 'paymentStatus'));
    })->name('invoices.show');
    Route::get('/invoices/{invoice}/print', [InvoicePrintController::class, 'show'])->name('invoices.print');
    Route::get('/invoices/{invoice}/pdf', [InvoicePdfController::class, 'show'])->name('invoices.pdf');

    Route::get('/expenses', fn () => view('expenses.index'))->name('expenses.index');
    Route::get('/reports/client-receivables-aging', [ClientReceivablesAgingController::class, '__invoke'])
        ->name('reports.client-receivables-aging');
    Route::get('/reports/client-receivables-aging/pdf', [ClientReceivablesAgingController::class, 'pdf'])
        ->name('reports.client-receivables-aging.pdf');

    Route::get('/reports', [PeriodReportsController::class, 'index'])->name('reports.index');

    // ═══ تقارير الغاز ═══
    $gasReport = function (string $view) {
        abort_unless(auth()->user()->can('view-period-reports'), 403);

        return view($view);
    };
    Route::get('/reports/gas-sales', fn () => $gasReport('reports.gas.sales'))->name('reports.gas-sales');
    Route::get('/reports/gas-collections', fn () => $gasReport('reports.gas.collections'))->name('reports.gas-collections');
    Route::get('/reports/gas-driver-cash', fn () => $gasReport('reports.gas.driver-cash'))->name('reports.gas-driver-cash');
    Route::get('/reports/gas-stock-balances', fn () => $gasReport('reports.gas.stock-balances'))->name('reports.gas-stock-balances');
    Route::get('/reports/gas-stock-movements', fn () => $gasReport('reports.gas.stock-movements'))->name('reports.gas-stock-movements');
    Route::get('/reports/gas-driver-performance', fn () => $gasReport('reports.gas.driver-performance'))->name('reports.gas-driver-performance');
    Route::get('/reports/cashflow', [PeriodReportsController::class, 'cashflow'])->name('reports.cashflow');
    Route::get('/reports/cashflow/pdf', [PeriodReportsController::class, 'cashflowPdf'])->name('reports.cashflow.pdf');
    Route::get('/reports/client-payments', [PeriodReportsController::class, 'clientPayments'])->name('reports.client-payments');
    Route::get('/reports/client-payments/pdf', [PeriodReportsController::class, 'clientPaymentsPdf'])->name('reports.client-payments.pdf');
    Route::get('/reports/supplier-payments', [PeriodReportsController::class, 'supplierPayments'])->name('reports.supplier-payments');
    Route::get('/reports/supplier-payments/pdf', [PeriodReportsController::class, 'supplierPaymentsPdf'])->name('reports.supplier-payments.pdf');
    Route::get('/reports/expenses', [PeriodReportsController::class, 'expenses'])->name('reports.expenses');
    Route::get('/reports/expenses/pdf', [PeriodReportsController::class, 'expensesPdf'])->name('reports.expenses.pdf');
    Route::get('/reports/salaries', [PeriodReportsController::class, 'salaries'])->name('reports.salaries');
    Route::get('/reports/salaries/pdf', [PeriodReportsController::class, 'salariesPdf'])->name('reports.salaries.pdf');
    Route::get('/reports/profit-loss', [PeriodReportsController::class, 'profitLoss'])->name('reports.profit-loss');
    Route::get('/reports/profit-loss/pdf', [PeriodReportsController::class, 'profitLossPdf'])->name('reports.profit-loss.pdf');
    Route::get('/reports/profit-loss-cash', [PeriodReportsController::class, 'profitLossCash'])->name('reports.profit-loss-cash');
    Route::get('/reports/profit-loss-cash/pdf', [PeriodReportsController::class, 'profitLossCashPdf'])->name('reports.profit-loss-cash.pdf');
    Route::get('/reports/profit-loss-ils', [PeriodReportsController::class, 'profitLossIls'])->name('reports.profit-loss-ils');
    Route::get('/reports/profit-loss-ils/pdf', [PeriodReportsController::class, 'profitLossIlsPdf'])->name('reports.profit-loss-ils.pdf');
    Route::get('/reports/sales', [PeriodReportsController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/sales/pdf', [PeriodReportsController::class, 'salesPdf'])->name('reports.sales.pdf');
    Route::get('/reports/purchase-orders', [PeriodReportsController::class, 'purchaseOrders'])->name('reports.purchase-orders');
    Route::get('/reports/purchase-orders/pdf', [PeriodReportsController::class, 'purchaseOrdersPdf'])->name('reports.purchase-orders.pdf');
    Route::get('/reports/supplier-receivables-aging', [SupplierReceivablesAgingController::class, '__invoke'])->name('reports.supplier-receivables-aging');
    Route::get('/reports/supplier-receivables-aging/pdf', [SupplierReceivablesAgingController::class, 'pdf'])->name('reports.supplier-receivables-aging.pdf');
    Route::get('/reports/supplier-adjustments', [PeriodReportsController::class, 'supplierAdjustments'])->name('reports.supplier-adjustments');
    Route::get('/reports/supplier-adjustments/pdf', [PeriodReportsController::class, 'supplierAdjustmentsPdf'])->name('reports.supplier-adjustments.pdf');
    Route::get('/reports/financial-period', [PeriodReportsController::class, 'financialPeriod'])->name('reports.financial-period');
    Route::get('/reports/financial-period/pdf', [PeriodReportsController::class, 'financialPeriodPdf'])->name('reports.financial-period.pdf');
    Route::get('/reports/activity-log', [PeriodReportsController::class, 'activityLog'])->name('reports.activity-log');
    Route::get('/reports/activity-log/pdf', [PeriodReportsController::class, 'activityLogPdf'])->name('reports.activity-log.pdf');
    Route::get('/reports/client-adjustments', [PeriodReportsController::class, 'clientAdjustments'])->name('reports.client-adjustments');
    Route::get('/reports/client-adjustments/pdf', [PeriodReportsController::class, 'clientAdjustmentsPdf'])->name('reports.client-adjustments.pdf');
    Route::get('/reports/client-receivables-summary', [PeriodReportsController::class, 'clientReceivablesSummary'])->name('reports.client-receivables-summary');
    Route::get('/reports/client-receivables-summary/pdf', [PeriodReportsController::class, 'clientReceivablesSummaryPdf'])->name('reports.client-receivables-summary.pdf');
    Route::get('/reports/supplier-payables-summary', [PeriodReportsController::class, 'supplierPayablesSummary'])->name('reports.supplier-payables-summary');
    Route::get('/reports/supplier-payables-summary/pdf', [PeriodReportsController::class, 'supplierPayablesSummaryPdf'])->name('reports.supplier-payables-summary.pdf');
    Route::get('/reports/aggregated-client-statements', [PeriodReportsController::class, 'aggregatedClientStatements'])->name('reports.aggregated-client-statements');
    Route::get('/reports/aggregated-client-statements/pdf', [PeriodReportsController::class, 'aggregatedClientStatementsPdf'])->name('reports.aggregated-client-statements.pdf');
    Route::get('/reports/aggregated-supplier-statements', [PeriodReportsController::class, 'aggregatedSupplierStatements'])->name('reports.aggregated-supplier-statements');
    Route::get('/reports/aggregated-supplier-statements/pdf', [PeriodReportsController::class, 'aggregatedSupplierStatementsPdf'])->name('reports.aggregated-supplier-statements.pdf');
    Route::get('/expenses/create', function () {
        abort_unless(auth()->user()->isAccountant(), 403);

        return view('expenses.create');
    })->name('expenses.create');
    Route::get('/expenses/{expense}/edit', function (Expense $expense) {
        abort_unless(auth()->user()->isAccountant(), 403);

        return view('expenses.edit', compact('expense'));
    })->name('expenses.edit');
    Route::get('/expenses/{expense}', fn (Expense $expense) => view('expenses.show', compact('expense')))->name('expenses.show');
    Route::delete('/expenses/{expense}', function (Expense $expense) {
        abort_unless(auth()->user()->isManager(), 403);
        $expense->delete();

        return redirect()->route('expenses.index')->with('toast', 'تم حذف المصروف');
    })->name('expenses.destroy');

    $incomeMergedRedirect = fn () => redirect()
        ->route('payments.index')
        ->with('toast', 'تُسجّل الإيرادات النقدية ضمن «دفعات العملاء» فقط.');

    Route::get('/income-entries', $incomeMergedRedirect)->name('income-entries.index');
    Route::get('/income-entries/create', $incomeMergedRedirect)->name('income-entries.create');
    Route::get('/income-entries/{any}/edit', $incomeMergedRedirect)
        ->where('any', '[0-9]+')
        ->name('income-entries.edit');
    Route::get('/income-entries/{any}', $incomeMergedRedirect)
        ->where('any', '[0-9]+')
        ->name('income-entries.show');
    Route::delete('/income-entries/{any}', $incomeMergedRedirect)
        ->where('any', '[0-9]+')
        ->name('income-entries.destroy');

    Route::get('/client-adjustments', function () {
        abort_unless(auth()->user()->isAccountant(), 403);

        return view('client-adjustments.index');
    })->name('client-adjustments.index');
    Route::get('/supplier-adjustments', function () {
        abort_unless(auth()->user()->isAccountant(), 403);

        return view('supplier-adjustments.index');
    })->name('supplier-adjustments.index');

    Route::get('/payments', fn () => view('payments.index'))->name('payments.index');
    Route::get('/payments/create', function () {
        abort_unless(auth()->user()->isAccountant(), 403);

        return view('payments.create');
    })->name('payments.create');
    Route::get('/payments/{payment}/edit', function (ClientPayment $payment) {
        abort_unless(auth()->user()->isAccountant(), 403);

        return view('payments.edit', compact('payment'));
    })->name('payments.edit');
    Route::get('/payments/{payment}/print', [ClientPaymentPrintController::class, 'show'])->name('payments.print');
    Route::get('/payments/{payment}/pdf', [ClientPaymentPdfController::class, 'show'])->name('payments.pdf');
    Route::get('/payments/{payment}', fn (ClientPayment $payment) => view('payments.show', compact('payment')))->name('payments.show');
    Route::delete('/payments/{payment}', function (ClientPayment $payment) {
        abort_unless(auth()->user()->isManager(), 403);
        $payment->delete();

        return redirect()->route('payments.index')->with('toast', 'تم حذف الدفعة');
    })->name('payments.destroy');

    Route::get('/clients/create', function () {
        abort_unless(auth()->user()->isAccountant(), 403);

        return view('clients.create');
    })->name('clients.create');
    Route::get('/clients/{client}/edit', function (Client $client) {
        abort_unless(auth()->user()->isAccountant(), 403);

        return view('clients.edit', compact('client'));
    })->name('clients.edit');
    Route::delete('/clients/{client}', function (Client $client) {
        abort_unless(auth()->user()->isManager(), 403);
        $client->delete();

        return redirect()->route('clients.index')->with('toast', 'تم حذف العميل');
    })->name('clients.destroy');
    Route::get('/clients/{client}', function (Client $client) {
        $client->load(['invoices' => fn ($q) => $q->latest('document_date'), 'payments' => fn ($q) => $q->latest('paid_at')]);

        return view('clients.show', compact('client'));
    })->name('clients.show');

    Route::get('/suppliers/create', function () {
        abort_unless(auth()->user()->isAccountant(), 403);

        return view('suppliers.create');
    })->name('suppliers.create');
    Route::get('/suppliers/{supplier}/statement', [SupplierStatementController::class, 'show'])->name('suppliers.statement');
    Route::get('/suppliers/{supplier}/statement/pdf', [SupplierStatementController::class, 'pdf'])->name('suppliers.statement.pdf');
    Route::get('/suppliers/{supplier}/adjustments/create', function (Supplier $supplier) {
        abort_unless(auth()->user()->can('create', SupplierBalanceAdjustment::class), 403);

        return view('suppliers.adjustments.create', compact('supplier'));
    })->name('suppliers.adjustments.create');
    Route::get('/suppliers/{supplier}/adjustments/{adjustment}/edit', function (Supplier $supplier, SupplierBalanceAdjustment $adjustment) {
        abort_unless($adjustment->supplier_id === $supplier->id, 404);
        abort_unless(auth()->user()->can('update', $adjustment), 403);

        return view('suppliers.adjustments.edit', compact('supplier', 'adjustment'));
    })->name('suppliers.adjustments.edit');
    Route::delete('/suppliers/{supplier}/adjustments/{adjustment}', function (Supplier $supplier, SupplierBalanceAdjustment $adjustment) {
        abort_unless($adjustment->supplier_id === $supplier->id, 404);
        abort_unless(auth()->user()->can('delete', $adjustment), 403);
        $adjustment->delete();

        return redirect()->route('suppliers.statement', $supplier)->with('toast', 'تم حذف التسوية');
    })->name('suppliers.adjustments.destroy');
    Route::get('/suppliers/{supplier}/edit', function (Supplier $supplier) {
        abort_unless(auth()->user()->isAccountant(), 403);

        return view('suppliers.edit', compact('supplier'));
    })->name('suppliers.edit');
    Route::get('/suppliers/{supplier}', function (Supplier $supplier) {
        $supplier->load([
            'purchaseOrders' => fn ($q) => $q->latest('document_date'),
            'payments' => fn ($q) => $q->latest('paid_at'),
        ]);

        return view('suppliers.show', compact('supplier'));
    })->name('suppliers.show');
    Route::delete('/suppliers/{supplier}', function (Supplier $supplier) {
        abort_unless(auth()->user()->isManager(), 403);
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('toast', 'تم حذف المورد');
    })->name('suppliers.destroy');

    Route::get('/employees', fn () => view('employees.index'))->name('employees.index');
    Route::get('/employees/create', function () {
        abort_unless(auth()->user()->can('create', Employee::class), 403);

        return view('employees.create');
    })->name('employees.create');
    Route::get('/employees/{employee}/edit', function (Employee $employee) {
        abort_unless(auth()->user()->can('update', $employee), 403);

        return view('employees.edit', compact('employee'));
    })->name('employees.edit');
    Route::get('/employees/{employee}', function (Employee $employee) {
        abort_unless(auth()->user()->can('view', $employee), 403);

        return view('employees.show', compact('employee'));
    })->name('employees.show');

    Route::get('/salary-payments', fn () => view('salary-payments.index'))->name('salary-payments.index');
    Route::get('/salary-payments/create', function () {
        abort_unless(auth()->user()->can('create', SalaryPayment::class), 403);

        return view('salary-payments.create');
    })->name('salary-payments.create');
    Route::get('/salary-payments/{salaryPayment}/edit', function (SalaryPayment $salaryPayment) {
        abort_unless(auth()->user()->can('update', $salaryPayment), 403);

        return view('salary-payments.edit', compact('salaryPayment'));
    })->name('salary-payments.edit');
    Route::get('/salary-payments/{salaryPayment}', function (SalaryPayment $salaryPayment) {
        abort_unless(auth()->user()->can('view', $salaryPayment), 403);

        return view('salary-payments.show', compact('salaryPayment'));
    })->name('salary-payments.show');

    // ═══ الغاز والمخزون ═══
    Route::get('/warehouses', function () {
        abort_unless(auth()->user()->can('viewAny', Warehouse::class), 403);

        return view('warehouses.index');
    })->name('warehouses.index');
    Route::get('/warehouses/create', function () {
        abort_unless(auth()->user()->can('create', Warehouse::class), 403);

        return view('warehouses.create');
    })->name('warehouses.create');
    Route::get('/warehouses/{warehouse}/edit', function (Warehouse $warehouse) {
        abort_unless(auth()->user()->can('update', $warehouse), 403);

        return view('warehouses.edit', compact('warehouse'));
    })->name('warehouses.edit');
    Route::get('/warehouses/{warehouse}/stock', function (Warehouse $warehouse) {
        abort_unless(auth()->user()->can('view', $warehouse), 403);

        return view('warehouses.stock', compact('warehouse'));
    })->name('warehouses.stock');
    Route::delete('/warehouses/{warehouse}', function (Warehouse $warehouse) {
        abort_unless(auth()->user()->can('delete', $warehouse), 403);
        $warehouse->delete();

        return redirect()->route('warehouses.index')->with('toast', 'تم حذف المخزن');
    })->name('warehouses.destroy');

    Route::get('/drivers', function () {
        abort_unless(auth()->user()->can('manage-drivers'), 403);

        return view('drivers.index');
    })->name('drivers.index');
    Route::get('/drivers/map', function () {
        abort_unless(auth()->user()->can('view-driver-locations'), 403);

        return view('drivers.map');
    })->name('drivers.map');
    Route::get('/drivers/create', function () {
        abort_unless(auth()->user()->can('manage-drivers'), 403);

        return view('drivers.create');
    })->name('drivers.create');
    Route::get('/drivers/{driver}/edit', function (User $driver) {
        abort_unless(auth()->user()->can('manage-drivers'), 403);
        abort_unless($driver->role === 'driver', 404);

        return view('drivers.edit', compact('driver'));
    })->name('drivers.edit');

    Route::get('/daily-prices', function () {
        abort_unless(auth()->user()->can('view-inventory'), 403);

        return view('daily-prices.index');
    })->name('daily-prices.index');

    Route::get('/stock-movements', function () {
        abort_unless(auth()->user()->can('view-inventory'), 403);

        return view('stock-movements.index');
    })->name('stock-movements.index');
    Route::get('/stock-movements/create', function () {
        abort_unless(auth()->user()->can('manage-inventory'), 403);

        return view('stock-movements.create');
    })->name('stock-movements.create');

    // ═══ المبيعات وصندوق السائق ═══
    Route::get('/pos', function () {
        abort_unless(auth()->user()->can('record-sales'), 403);

        return view('pos.index');
    })->name('pos.index');
    Route::get('/sales', function () {
        abort_unless(auth()->user()->can('view-sales'), 403);

        return view('sales.index');
    })->name('sales.index');
    Route::get('/cash-handovers', function () {
        abort_unless(auth()->user()->can('manage-cash-handover'), 403);

        return view('cash-handovers.index');
    })->name('cash-handovers.index');
    Route::get('/driver-expenses', function () {
        abort_unless(auth()->user()->can('manage-driver-expenses'), 403);

        return view('driver-expenses.index');
    })->name('driver-expenses.index');
    Route::get('/collections', function () {
        abort_unless(auth()->user()->can('record-sales'), 403);

        return view('collections.index');
    })->name('collections.index');

    // صفحة «موقعي» مخفية — المشاركة تلقائية عبر Beacon / APK؛ التوجيه لنقطة البيع
    Route::get('/my-location', function () {
        abort_unless(auth()->user()->can('share-location'), 403);

        return redirect()->route('pos.index');
    })->name('location.share');

    Route::post('/driver/device-token', [DriverDeviceTokenController::class, 'store'])
        ->name('driver.device-token');

    Route::get('/users', function () {
        abort_unless(auth()->user()->isManager(), 403);

        return view('users.index');
    })->name('users.index');
    Route::get('/users/create', function () {
        abort_unless(auth()->user()->isManager(), 403);

        return view('users.create');
    })->name('users.create');
    Route::get('/users/{user}/edit', function (User $user) {
        abort_unless(auth()->user()->isManager(), 403);

        return view('users.edit', compact('user'));
    })->name('users.edit');

    Route::get('/database-backup', function () {
        abort_unless(auth()->user()->isManager(), 403);

        return view('database-backup.index');
    })->name('database-backup.index');

    Route::get('/database-backup/download/{filename}', [DatabaseBackupController::class, 'download'])
        ->name('database-backup.download');
});

require __DIR__.'/auth.php';
