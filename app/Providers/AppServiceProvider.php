<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\ClientBalanceAdjustment;
use App\Models\ClientPayment;
use App\Models\Employee;
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
use App\Policies\ClientBalanceAdjustmentPolicy;
use App\Policies\ClientPaymentPolicy;
use App\Policies\ClientPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LegacyCatalogProductPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\SalaryPaymentPolicy;
use App\Policies\SupplierBalanceAdjustmentPolicy;
use App\Policies\SupplierPaymentPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\WarehousePolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('files', fn () => new \App\Filesystem\Filesystem);
    }

    public function boot(): void
    {
        foreach ([
            storage_path('framework/views'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('logs'),
            storage_path('app/browsershot-tmp'),
            storage_path('app/puppeteer-cache'),
        ] as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
        }

        Paginator::defaultView('vendor.pagination.tailwind');

        Gate::policy(ClientPayment::class, ClientPaymentPolicy::class);
        Gate::policy(ClientBalanceAdjustment::class, ClientBalanceAdjustmentPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(LegacyCatalogProduct::class, LegacyCatalogProductPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(SupplierPayment::class, SupplierPaymentPolicy::class);
        Gate::policy(SupplierBalanceAdjustment::class, SupplierBalanceAdjustmentPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(SalaryPayment::class, SalaryPaymentPolicy::class);
        Gate::policy(Warehouse::class, WarehousePolicy::class);

        // إدارة المخزون والتسعير اليومي والسائقين — للمحاسب فأعلى.
        Gate::define('manage-inventory', fn (User $user): bool => $user->is_active && $user->isAccountant());
        Gate::define('view-inventory', fn (User $user): bool => (bool) $user->is_active);
        Gate::define('manage-daily-prices', fn (User $user): bool => $user->is_active && $user->isAccountant());
        Gate::define('manage-drivers', fn (User $user): bool => $user->is_active && $user->isManager());
        Gate::define('view-driver-locations', fn (User $user): bool => $user->is_active && $user->isManager());
        Gate::define('share-location', fn (User $user): bool => $user->is_active && $user->isDriver());

        // المبيعات وصندوق السائق: السائق يبيع من سيارته، والمحاسب فأعلى أيضًا.
        Gate::define('record-sales', fn (User $user): bool => $user->is_active && ($user->isDriver() || $user->isAccountant()));
        Gate::define('view-sales', fn (User $user): bool => (bool) $user->is_active);
        Gate::define('delete-sales', fn (User $user): bool => $user->is_active && $user->isAccountant());
        Gate::define('manage-cash-handover', fn (User $user): bool => $user->is_active && ($user->isDriver() || $user->isAccountant()));
        Gate::define('manage-driver-expenses', fn (User $user): bool => $user->is_active && ($user->isDriver() || $user->isAccountant()));

        Gate::define('view-client-receivables-aging', fn (User $user): bool => (bool) $user->is_active);
        Gate::define('export-client-receivables-aging-csv', fn (User $user): bool => $user->isAccountant());
        Gate::define('view-period-reports', fn (User $user): bool => (bool) $user->is_active);
        Gate::define('export-period-reports', fn (User $user): bool => $user->isAccountant());
    }
}
