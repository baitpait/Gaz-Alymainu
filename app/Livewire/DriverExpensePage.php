<?php

namespace App\Livewire;

use App\Enums\DriverExpenseCategory;
use App\Models\DriverExpense;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CashBoxService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * مصروفات السائق/السيارة: تسجيل مصروف يُخصم من الرصيد النقدي لصندوق السائق.
 * للمحاسب/المدير: يمكن عرض كل السائقين أو سائق واحد؛ التسجيل يبقى لسائق محدد فقط.
 * فلترة السجل (تصنيف/تواريخ/بحث) وتعديل للمحاسب.
 */
class DriverExpensePage extends Component
{
    /** معرف السائق، أو "all" لعرض الجميع، أو فارغ قبل الاختيار. */
    public string $driverUserId = '';

    public string $amount = '';

    public string $category = 'fuel';

    public string $notes = '';

    public string $filterCategory = '';

    public string $filterDateFrom = '';

    public string $filterDateTo = '';

    public string $filterSearch = '';

    public ?int $editingId = null;

    public string $editAmount = '';

    public string $editCategory = 'fuel';

    public string $editNotes = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('manage-driver-expenses'), 403);

        $user = auth()->user();
        if ($user->isDriver()) {
            $this->driverUserId = (string) $user->id;
        }
    }

    public function updatedDriverUserId(): void
    {
        $this->amount = '';
        $this->notes = '';
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

    public function applyExpenseFilters(): void
    {
        // Filters are applied from bound properties on next render.
        $this->cancelEdit();
    }

    public function clearExpenseFilters(): void
    {
        $this->filterCategory = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->filterSearch = '';
        $this->cancelEdit();
    }

    public function hasActiveExpenseFilters(): bool
    {
        return $this->filterCategory !== ''
            || $this->filterDateFrom !== ''
            || $this->filterDateTo !== ''
            || trim($this->filterSearch) !== '';
    }

    public function save(CashBoxService $cashBox): void
    {
        abort_unless(Gate::allows('manage-driver-expenses'), 403);

        $user = auth()->user();
        if ($user->isDriver()) {
            $this->driverUserId = (string) $user->id;
        }

        if ($this->showingAllDrivers()) {
            $this->addError('driverUserId', 'اختر سائقًا محددًا لتسجيل مصروف.');

            return;
        }

        $this->validate([
            'driverUserId' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|gt:0',
            'category' => 'required|in:fuel,maintenance,other',
            'notes' => 'nullable|string|max:2000',
        ], [], [
            'driverUserId' => 'السائق',
            'amount' => 'المبلغ',
            'category' => 'التصنيف',
        ]);

        $driverId = (int) $this->driverUserId;

        $vehicleId = Warehouse::query()
            ->where('type', 'vehicle')
            ->where('assigned_user_id', $driverId)
            ->value('id');

        try {
            $cashBox->recordExpense(
                $driverId,
                (float) $this->amount,
                DriverExpenseCategory::from($this->category),
                $vehicleId ? (int) $vehicleId : null,
                auth()->id(),
                trim($this->notes) !== '' ? trim($this->notes) : null,
            );
        } catch (\RuntimeException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        }

        $this->amount = '';
        $this->notes = '';
        $this->category = 'fuel';
        $this->dispatch('toast', message: 'تم تسجيل المصروف بنجاح');
    }

    /**
     * Business Purpose: Open edit modal for amount/category/notes (accountant).
     */
    public function startEdit(int $id): void
    {
        abort_unless(Gate::allows('update-driver-expenses'), 403);

        $expense = DriverExpense::query()->findOrFail($id);
        $this->editingId = $expense->id;
        $this->editAmount = (string) $expense->amount;
        $this->editCategory = $expense->category?->value ?? 'other';
        $this->editNotes = (string) ($expense->notes ?? '');
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editAmount = '';
        $this->editCategory = 'fuel';
        $this->editNotes = '';
        $this->resetValidation();
    }

    /**
     * Business Purpose: Persist expense corrections within available driver cash.
     */
    public function saveEdit(CashBoxService $cashBox): void
    {
        abort_unless(Gate::allows('update-driver-expenses'), 403);

        $this->validate([
            'editingId' => 'required|integer',
            'editAmount' => 'required|numeric|gt:0',
            'editCategory' => 'required|in:fuel,maintenance,other',
            'editNotes' => 'nullable|string|max:2000',
        ], [], [
            'editAmount' => 'المبلغ',
            'editCategory' => 'التصنيف',
            'editNotes' => 'الملاحظات',
        ]);

        $expense = DriverExpense::query()->findOrFail((int) $this->editingId);

        try {
            $cashBox->updateExpense(
                $expense,
                (float) $this->editAmount,
                DriverExpenseCategory::from($this->editCategory),
                $this->editNotes,
            );
        } catch (\RuntimeException $e) {
            $this->addError('editAmount', $e->getMessage());

            return;
        }

        $this->cancelEdit();
        $this->dispatch('toast', message: 'تم تحديث المصروف');
    }

    /**
     * Business Purpose: إلغاء مصروف سائق بالخذف المنطقي ليعود المبلغ لرصيد صندوق السائق.
     */
    public function deleteRecord(int $id): void
    {
        abort_unless(Gate::allows('delete-driver-expenses'), 403);

        $expense = DriverExpense::query()->findOrFail($id);
        $expense->delete();

        $this->dispatch('toast', message: 'تم حذف المصروف وإرجاع المبلغ لرصيد السائق');
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

        $balance = $driverId ? $cashBox->balance($driverId) : null;

        $baseQuery = DriverExpense::query();
        if ($driverId) {
            $baseQuery->where('driver_user_id', $driverId);
        } elseif (! $showAll) {
            $baseQuery->whereRaw('1 = 0');
        }

        $this->applyHistoryFilters($baseQuery);

        $totalExpenses = $hasSelection ? (float) (clone $baseQuery)->sum('amount') : 0.0;

        $history = $hasSelection
            ? (clone $baseQuery)
                ->with(['recordedBy', 'driver'])
                ->latest('spent_at')
                ->latest('id')
                ->limit($showAll ? 100 : 50)
                ->get()
            : collect();

        return view('livewire.driver-expense-page', [
            'drivers' => $drivers,
            'balance' => $balance,
            'totalExpenses' => $totalExpenses,
            'categories' => DriverExpenseCategory::options(),
            'history' => $history,
            'isDriver' => $user->isDriver(),
            'showAll' => $showAll,
            'hasSelection' => $hasSelection,
            'driverId' => $driverId,
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\DriverExpense>  $query
     */
    private function applyHistoryFilters($query): void
    {
        $categoryValues = array_keys(DriverExpenseCategory::options());
        if (in_array($this->filterCategory, $categoryValues, true)) {
            $query->where('category', $this->filterCategory);
        }

        if ($this->filterDateFrom !== '') {
            try {
                $query->whereDate('expense_date', '>=', Carbon::parse($this->filterDateFrom)->toDateString());
            } catch (\Throwable) {
            }
        }

        if ($this->filterDateTo !== '') {
            try {
                $query->whereDate('expense_date', '<=', Carbon::parse($this->filterDateTo)->toDateString());
            } catch (\Throwable) {
            }
        }

        if (trim($this->filterSearch) !== '') {
            $s = '%'.trim($this->filterSearch).'%';
            $query->where(function ($q) use ($s) {
                $q->where('notes', 'like', $s)
                    ->orWhereHas('driver', fn ($q) => $q->where('full_name', 'like', $s));
            });
        }
    }
}
