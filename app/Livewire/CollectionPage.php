<?php

namespace App\Livewire;

use App\Enums\CollectionMethod;
use App\Models\Collection;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CashBoxService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * صفحة التحصيل: تسجيل مبلغ محصّل (نقدي/شيك) بلا ذمم/زبون.
 * للمحاسب/المدير: عرض كل السائقين أو سائق واحد، مع تعديل وحذف منطقي.
 * السائق يسجّل لنفسه فقط ولا يعدّل ولا يحذف.
 */
class CollectionPage extends Component
{
    /** معرف السائق، أو "all" لعرض الجميع، أو فارغ قبل الاختيار. */
    public string $driverUserId = '';

    public string $amount = '';

    public string $method = 'cash';

    public string $chequeNumber = '';

    public string $notes = '';

    public string $filterMethod = '';

    public string $filterDateFrom = '';

    public string $filterDateTo = '';

    public string $filterSearch = '';

    public ?int $editingId = null;

    public string $editAmount = '';

    public string $editMethod = 'cash';

    public string $editChequeNumber = '';

    public string $editNotes = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('record-sales'), 403);

        $user = auth()->user();
        if ($user->isDriver()) {
            $this->driverUserId = (string) $user->id;
        }
    }

    public function updatedDriverUserId(): void
    {
        $this->amount = '';
        $this->notes = '';
        $this->chequeNumber = '';
        $this->cancelEdit();
        $this->resetErrorBag();
    }

    public function showingAllDrivers(): bool
    {
        return $this->driverUserId === 'all';
    }

    public function selectedDriverId(): ?int
    {
        if ($this->driverUserId === '' || $this->driverUserId === 'all') {
            return null;
        }

        return ctype_digit($this->driverUserId) ? (int) $this->driverUserId : null;
    }

    public function applyCollectionFilters(): void
    {
        $this->cancelEdit();
    }

    public function clearCollectionFilters(): void
    {
        $this->filterMethod = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->filterSearch = '';
        $this->cancelEdit();
    }

    public function hasActiveCollectionFilters(): bool
    {
        return $this->filterMethod !== ''
            || $this->filterDateFrom !== ''
            || $this->filterDateTo !== ''
            || trim($this->filterSearch) !== '';
    }

    public function save(CashBoxService $cashBox): void
    {
        abort_unless(Gate::allows('record-sales'), 403);

        $user = auth()->user();
        if ($user->isDriver()) {
            $this->driverUserId = (string) $user->id;
        }

        if ($this->showingAllDrivers()) {
            $this->addError('driverUserId', 'اختر سائقًا محددًا لتسجيل تحصيل.');

            return;
        }

        $this->validate([
            'driverUserId' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|gt:0',
            'method' => 'required|in:cash,cheque',
            'chequeNumber' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
        ], [], [
            'driverUserId' => 'السائق',
            'amount' => 'المبلغ',
            'method' => 'آلية الدفع',
            'chequeNumber' => 'رقم الشيك',
        ]);

        $driverId = (int) $this->driverUserId;

        $vehicleId = Warehouse::query()
            ->where('type', 'vehicle')
            ->where('assigned_user_id', $driverId)
            ->value('id');

        $method = CollectionMethod::from($this->method);

        try {
            $cashBox->recordCollection(
                $driverId,
                (float) $this->amount,
                $method,
                $vehicleId ? (int) $vehicleId : null,
                $method === CollectionMethod::Cheque ? $this->chequeNumber : null,
                auth()->id(),
                trim($this->notes) !== '' ? trim($this->notes) : null,
            );
        } catch (\RuntimeException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        }

        $this->amount = '';
        $this->notes = '';
        $this->chequeNumber = '';
        $this->method = 'cash';
        $this->dispatch('toast', message: 'تم حفظ التحصيل بنجاح');
    }

    /**
     * Business Purpose: Open edit modal for amount/method/notes (accountant).
     */
    public function startEdit(int $id): void
    {
        abort_unless(Gate::allows('update-collections'), 403);

        $collection = Collection::query()->findOrFail($id);
        $this->editingId = $collection->id;
        $this->editAmount = rtrim(rtrim(number_format((float) $collection->amount, 4, '.', ''), '0'), '.') ?: '0';
        $this->editMethod = $collection->method?->value ?? 'cash';
        $this->editChequeNumber = (string) ($collection->cheque_number ?? '');
        $this->editNotes = (string) ($collection->notes ?? '');
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editAmount = '';
        $this->editMethod = 'cash';
        $this->editChequeNumber = '';
        $this->editNotes = '';
        $this->resetValidation();
    }

    /**
     * Business Purpose: Persist collection corrections without dropping driver cash/cheque below zero.
     */
    public function saveEdit(CashBoxService $cashBox): void
    {
        abort_unless(Gate::allows('update-collections'), 403);

        $this->validate([
            'editingId' => 'required|integer',
            'editAmount' => 'required|numeric|gt:0',
            'editMethod' => 'required|in:cash,cheque',
            'editChequeNumber' => 'nullable|string|max:100',
            'editNotes' => 'nullable|string|max:2000',
        ], [], [
            'editAmount' => 'المبلغ',
            'editMethod' => 'آلية الدفع',
            'editChequeNumber' => 'رقم الشيك',
            'editNotes' => 'الملاحظات',
        ]);

        $collection = Collection::query()->findOrFail((int) $this->editingId);
        $method = CollectionMethod::from($this->editMethod);

        try {
            $cashBox->updateCollection(
                $collection,
                (float) $this->editAmount,
                $method,
                $this->editNotes,
                $method === CollectionMethod::Cheque ? $this->editChequeNumber : null,
            );
        } catch (\RuntimeException $e) {
            $this->addError('editAmount', $e->getMessage());

            return;
        }

        $this->cancelEdit();
        $this->dispatch('toast', message: 'تم تحديث التحصيل');
    }

    /**
     * Business Purpose: Soft-delete a collection so cashbox and market debt exclude it.
     */
    public function deleteRecord(int $id, CashBoxService $cashBox): void
    {
        abort_unless(Gate::allows('delete-collections'), 403);

        $collection = Collection::query()->findOrFail($id);

        try {
            $cashBox->voidCollection($collection);
        } catch (\RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage());

            return;
        }

        $this->dispatch('toast', message: 'تم حذف التحصيل');
    }

    public function render(CashBoxService $cashBox)
    {
        $user = auth()->user();

        $drivers = collect();
        if (! $user->isDriver()) {
            $drivers = User::query()
                ->where('role', 'driver')
                ->where('is_active', true)
                ->orderBy('full_name')
                ->get(['id', 'full_name']);
        }

        $showAll = $this->showingAllDrivers();
        $driverId = $this->selectedDriverId();
        $hasSelection = $showAll || $driverId !== null;

        $today = \App\Support\AppDateTime::today();
        $cashToday = $driverId ? $cashBox->cashCollectionsForDate($driverId, $today) : 0;
        $chequeToday = $driverId ? $cashBox->chequeCollectionsForDate($driverId, $today) : 0;

        $baseQuery = Collection::query();
        if ($driverId) {
            $baseQuery->where('driver_user_id', $driverId);
        } elseif (! $showAll) {
            $baseQuery->whereRaw('1 = 0');
        }

        $this->applyHistoryFilters($baseQuery);

        $totalCash = $hasSelection
            ? (float) (clone $baseQuery)->where('method', CollectionMethod::Cash->value)->sum('amount')
            : 0.0;
        $totalCheque = $hasSelection
            ? (float) (clone $baseQuery)->where('method', CollectionMethod::Cheque->value)->sum('amount')
            : 0.0;

        $history = $hasSelection
            ? (clone $baseQuery)
                ->with(['recordedBy', 'driver'])
                ->latest('collected_at')
                ->latest('id')
                ->limit($showAll ? 100 : 50)
                ->get()
            : collect();

        return view('livewire.collection-page', [
            'drivers' => $drivers,
            'cashToday' => $cashToday,
            'chequeToday' => $chequeToday,
            'totalCash' => $totalCash,
            'totalCheque' => $totalCheque,
            'history' => $history,
            'isDriver' => $user->isDriver(),
            'showAll' => $showAll,
            'hasSelection' => $hasSelection,
            'driverId' => $driverId,
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Collection>  $query
     */
    private function applyHistoryFilters($query): void
    {
        if (in_array($this->filterMethod, ['cash', 'cheque'], true)) {
            $query->where('method', $this->filterMethod);
        }

        if ($this->filterDateFrom !== '') {
            try {
                $query->whereDate('collection_date', '>=', Carbon::parse($this->filterDateFrom)->toDateString());
            } catch (\Throwable) {
            }
        }

        if ($this->filterDateTo !== '') {
            try {
                $query->whereDate('collection_date', '<=', Carbon::parse($this->filterDateTo)->toDateString());
            } catch (\Throwable) {
            }
        }

        if (trim($this->filterSearch) !== '') {
            $s = '%'.trim($this->filterSearch).'%';
            $query->where(function ($q) use ($s) {
                $q->where('notes', 'like', $s)
                    ->orWhere('cheque_number', 'like', $s)
                    ->orWhereHas('driver', fn ($q) => $q->where('full_name', 'like', $s));
            });
        }
    }
}
