<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class StockMovementList extends Component
{
    use WithPagination;
    use WithPerPagePagination;

    public function mount(): void
    {
        abort_unless(Gate::allows('view-inventory'), 403);
    }

    public function render()
    {
        $rows = $this->paginateWithPerPage(
            StockMovement::query()
                ->with(['product', 'fromWarehouse', 'toWarehouse', 'driver'])
                ->latest('moved_at')
                ->latest('id')
        );

        return view('livewire.stock-movement-list', ['rows' => $rows]);
    }
}
