<?php

namespace App\Livewire;

use App\Livewire\Concerns\UsesCommittedSearchFilter;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class WarehouseList extends Component
{
    use UsesCommittedSearchFilter;
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Warehouse::class);
    }

    public function deleteRecord(int $id): void
    {
        $warehouse = Warehouse::query()->findOrFail($id);
        Gate::authorize('delete', $warehouse);
        $warehouse->delete();
        $this->dispatch('toast', message: 'تم حذف المخزن');
    }

    public function render()
    {
        $rows = $this->paginateWithPerPage(
            Warehouse::query()
                ->with('driver')
                ->when($this->search, function ($q) {
                    $s = "%{$this->search}%";
                    $q->where(fn ($q) => $q->where('name', 'like', $s)
                        ->orWhere('code', 'like', $s)
                        ->orWhere('vehicle_plate', 'like', $s)
                    );
                })
                ->orderBy('type')
                ->orderBy('name')
        );

        return view('livewire.warehouse-list', ['rows' => $rows]);
    }
}
