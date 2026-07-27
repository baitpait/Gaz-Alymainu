<?php

namespace App\Livewire;

use App\Livewire\Concerns\UsesCommittedSearchFilter;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DriverList extends Component
{
    use UsesCommittedSearchFilter;
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('manage-drivers'), 403);
    }

    public function render()
    {
        $rows = $this->paginateWithPerPage(
            User::query()
                ->where('role', 'driver')
                ->with(['employee', 'assignedVehicle'])
                ->when($this->search, function ($q) {
                    $s = "%{$this->search}%";
                    $q->where(fn ($q) => $q->where('full_name', 'like', $s)
                        ->orWhere('email', 'like', $s)
                    );
                })
                ->orderBy('full_name')
        );

        return view('livewire.driver-list', ['rows' => $rows]);
    }
}
