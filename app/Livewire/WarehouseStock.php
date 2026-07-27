<?php

namespace App\Livewire;

use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * عرض أرصدة المخزون داخل مخزن/سيارة محددة.
 */
class WarehouseStock extends Component
{
    public Warehouse $warehouse;

    public function mount(Warehouse $warehouse): void
    {
        abort_unless(Gate::allows('view-inventory'), 403);
        $this->warehouse = $warehouse;
    }

    public function render()
    {
        $balances = StockBalance::query()
            ->where('warehouse_id', $this->warehouse->id)
            ->with('product')
            ->get()
            ->sortBy(fn ($b) => $b->product?->name);

        return view('livewire.warehouse-stock', ['balances' => $balances]);
    }
}
