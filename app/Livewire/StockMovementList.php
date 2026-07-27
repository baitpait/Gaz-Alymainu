<?php

namespace App\Livewire;

use App\Enums\StockMovementType;
use App\Livewire\Concerns\AppliesListFiltersOnAction;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Business Purpose: Paginated stock movement register with apply/clear filters
 * (search, type, product, warehouse, driver, date range) matching other list pages.
 */
class StockMovementList extends Component
{
    use AppliesListFiltersOnAction;
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sm_type')]
    public string $filterType = '';

    #[Url(as: 'sm_product')]
    public string $filterProductId = '';

    #[Url(as: 'sm_wh')]
    public string $filterWarehouseId = '';

    #[Url(as: 'sm_driver')]
    public string $filterDriverId = '';

    #[Url(as: 'sm_from')]
    public string $filterDateFrom = '';

    #[Url(as: 'sm_to')]
    public string $filterDateTo = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('view-inventory'), 403);
    }

    /**
     * Business Purpose: Clear stock movement filters and return to the first page.
     */
    public function clearStockMovementFilters(): void
    {
        $this->search = '';
        $this->filterType = '';
        $this->filterProductId = '';
        $this->filterWarehouseId = '';
        $this->filterDriverId = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->resetPage();
    }

    public function hasActiveStockMovementFilters(): bool
    {
        return trim($this->search) !== ''
            || $this->filterType !== ''
            || $this->filterProductId !== ''
            || $this->filterWarehouseId !== ''
            || $this->filterDriverId !== ''
            || $this->filterDateFrom !== ''
            || $this->filterDateTo !== '';
    }

    public function render()
    {
        $typeValues = array_column(StockMovementType::cases(), 'value');

        $query = StockMovement::query()
            ->with(['product', 'fromWarehouse', 'toWarehouse', 'driver'])
            ->when(trim($this->search) !== '', function ($q) {
                $s = '%'.trim($this->search).'%';
                $q->where(function ($q) use ($s) {
                    $q->where('notes', 'like', $s)
                        ->orWhereHas('product', fn ($q) => $q->where('name', 'like', $s)
                            ->orWhere('product_code', 'like', $s))
                        ->orWhereHas('fromWarehouse', fn ($q) => $q->where('name', 'like', $s)
                            ->orWhere('code', 'like', $s)
                            ->orWhere('vehicle_plate', 'like', $s))
                        ->orWhereHas('toWarehouse', fn ($q) => $q->where('name', 'like', $s)
                            ->orWhere('code', 'like', $s)
                            ->orWhere('vehicle_plate', 'like', $s))
                        ->orWhereHas('driver', fn ($q) => $q->where('full_name', 'like', $s)
                            ->orWhere('email', 'like', $s));
                });
            })
            ->when(in_array($this->filterType, $typeValues, true), fn ($q) => $q->where('type', $this->filterType))
            ->when(ctype_digit($this->filterProductId), fn ($q) => $q->where('product_id', (int) $this->filterProductId))
            ->when(ctype_digit($this->filterWarehouseId), function ($q) {
                $id = (int) $this->filterWarehouseId;
                $q->where(fn ($q) => $q->where('from_warehouse_id', $id)->orWhere('to_warehouse_id', $id));
            })
            ->when(ctype_digit($this->filterDriverId), fn ($q) => $q->where('driver_user_id', (int) $this->filterDriverId))
            ->when($this->filterDateFrom !== '', function ($q) {
                try {
                    $from = Carbon::parse($this->filterDateFrom)->startOfDay();
                    $q->where('moved_at', '>=', $from);
                } catch (\Throwable) {
                }
            })
            ->when($this->filterDateTo !== '', function ($q) {
                try {
                    $to = Carbon::parse($this->filterDateTo)->endOfDay();
                    $q->where('moved_at', '<=', $to);
                } catch (\Throwable) {
                }
            })
            ->latest('moved_at')
            ->latest('id');

        $rows = $this->paginateWithPerPage($query);

        return view('livewire.stock-movement-list', [
            'rows' => $rows,
            'typeOptions' => collect(StockMovementType::cases())
                ->mapWithKeys(fn (StockMovementType $type) => [$type->value => $type->label()])
                ->all(),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'drivers' => User::query()->where('role', 'driver')->where('is_active', true)->orderBy('full_name')->get(['id', 'full_name']),
        ]);
    }
}
