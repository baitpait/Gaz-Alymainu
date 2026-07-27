<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Sale;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SaleList extends Component
{
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'date')]
    public string $date = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('view-sales'), 403);
    }

    public function render()
    {
        $user = auth()->user();

        $query = Sale::query()
            ->with(['product', 'warehouse', 'driver'])
            ->when($this->date, fn ($q) => $q->whereDate('sale_date', $this->date))
            // السائق يرى مبيعاته فقط.
            ->when($user->isDriver(), fn ($q) => $q->where('driver_user_id', $user->id))
            ->latest('sold_at')
            ->latest('id');

        $rows = $this->paginateWithPerPage($query);

        return view('livewire.sale-list', ['rows' => $rows]);
    }
}
