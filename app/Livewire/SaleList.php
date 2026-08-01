<?php

namespace App\Livewire;

use App\Enums\SalePaymentType;
use App\Livewire\Concerns\AppliesListFiltersOnAction;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Business Purpose: Paginated sales register with apply/clear filters matching other list pages
 * (search, payment type, product, driver, warehouse, date range) for managers and drivers.
 * Accountants may void a sale (soft-delete + restore stock).
 */
class SaleList extends Component
{
    use AppliesListFiltersOnAction;
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sale_pay')]
    public string $filterPaymentType = '';

    #[Url(as: 'sale_product')]
    public string $filterProductId = '';

    #[Url(as: 'sale_driver')]
    public string $filterDriverId = '';

    #[Url(as: 'sale_wh')]
    public string $filterWarehouseId = '';

    #[Url(as: 'sale_from')]
    public string $filterDateFrom = '';

    #[Url(as: 'sale_to')]
    public string $filterDateTo = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('view-sales'), 403);
    }

    /**
     * Business Purpose: Void a sale so inventory returns and cash/market debt totals exclude it.
     */
    public function deleteRecord(int $id, \App\Services\SalesService $sales): void
    {
        abort_unless(Gate::allows('delete-sales'), 403);

        $sale = Sale::query()->findOrFail($id);

        try {
            $sales->voidSale($sale, auth()->id());
        } catch (\RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage());

            return;
        }

        $this->dispatch('toast', message: 'تم حذف البيع وإرجاع الكمية للمخزن');
    }

    /**
     * Business Purpose: Reset sale list filters and return to the first page.
     */
    public function clearSaleFilters(): void
    {
        $this->search = '';
        $this->filterPaymentType = '';
        $this->filterProductId = '';
        $this->filterDriverId = '';
        $this->filterWarehouseId = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->resetPage();
    }

    public function hasActiveSaleFilters(): bool
    {
        return trim($this->search) !== ''
            || $this->filterPaymentType !== ''
            || $this->filterProductId !== ''
            || $this->filterDriverId !== ''
            || $this->filterWarehouseId !== ''
            || $this->filterDateFrom !== ''
            || $this->filterDateTo !== '';
    }

    public function render()
    {
        $user = auth()->user();

        $paymentValues = array_column(SalePaymentType::cases(), 'value');

        $query = Sale::query()
            ->with(['product', 'warehouse', 'driver'])
            ->when(trim($this->search) !== '', function ($q) {
                $s = '%'.trim($this->search).'%';
                $q->where(function ($q) use ($s) {
                    $q->where('notes', 'like', $s)
                        ->orWhereHas('product', fn ($q) => $q->where('name', 'like', $s)
                            ->orWhere('product_code', 'like', $s))
                        ->orWhereHas('warehouse', fn ($q) => $q->where('name', 'like', $s)
                            ->orWhere('code', 'like', $s)
                            ->orWhere('vehicle_plate', 'like', $s))
                        ->orWhereHas('driver', fn ($q) => $q->where('full_name', 'like', $s)
                            ->orWhere('email', 'like', $s));
                });
            })
            ->when(in_array($this->filterPaymentType, $paymentValues, true), fn ($q) => $q->where('payment_type', $this->filterPaymentType))
            ->when(ctype_digit($this->filterProductId), fn ($q) => $q->where('product_id', (int) $this->filterProductId))
            ->when(ctype_digit($this->filterWarehouseId), fn ($q) => $q->where('warehouse_id', (int) $this->filterWarehouseId))
            ->when(
                ! $user->isDriver() && ctype_digit($this->filterDriverId),
                fn ($q) => $q->where('driver_user_id', (int) $this->filterDriverId)
            )
            ->when($this->filterDateFrom !== '', function ($q) {
                try {
                    $from = Carbon::parse($this->filterDateFrom)->toDateString();
                    $q->whereDate('sale_date', '>=', $from);
                } catch (\Throwable) {
                }
            })
            ->when($this->filterDateTo !== '', function ($q) {
                try {
                    $to = Carbon::parse($this->filterDateTo)->toDateString();
                    $q->whereDate('sale_date', '<=', $to);
                } catch (\Throwable) {
                }
            })
            // السائق يرى مبيعاته فقط.
            ->when($user->isDriver(), fn ($q) => $q->where('driver_user_id', $user->id))
            ->latest('sold_at')
            ->latest('id');

        $rows = $this->paginateWithPerPage($query);

        return view('livewire.sale-list', [
            'rows' => $rows,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'drivers' => $user->isDriver()
                ? collect()
                : User::query()->where('role', 'driver')->where('is_active', true)->orderBy('full_name')->get(['id', 'full_name']),
            'showDriverFilter' => ! $user->isDriver(),
        ]);
    }
}
