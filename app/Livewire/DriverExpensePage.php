<?php

namespace App\Livewire;

use App\Enums\DriverExpenseCategory;
use App\Models\DriverExpense;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CashBoxService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * مصروفات السائق/السيارة: تسجيل مصروف يُخصم من الرصيد النقدي لصندوق السائق.
 * للمحاسب/المدير: يمكن عرض كل السائقين أو سائق واحد؛ التسجيل يبقى لسائق محدد فقط.
 */
class DriverExpensePage extends Component
{
    /** معرف السائق، أو "all" لعرض الجميع، أو فارغ قبل الاختيار. */
    public string $driverUserId = '';

    public string $amount = '';

    public string $category = 'fuel';

    public string $notes = '';

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
        $this->resetErrorBag();
    }

    /**
     * هل العرض الحالي لكل السائقين؟
     */
    public function showingAllDrivers(): bool
    {
        return $this->driverUserId === 'all';
    }

    /**
     * معرف السائق المختار للتسجيل/الرصيد الفردي، أو null.
     */
    public function selectedDriverId(): ?int
    {
        if ($this->driverUserId === '' || $this->driverUserId === 'all') {
            return null;
        }

        return ctype_digit($this->driverUserId) ? (int) $this->driverUserId : null;
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

        // السيارة المسندة للسائق (إن وُجدت) لربط المصروف بها.
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
        $totalExpenses = $showAll
            ? (float) DriverExpense::query()->sum('amount')
            : ($driverId ? $cashBox->totalDriverExpenses($driverId) : 0.0);

        $historyQuery = DriverExpense::query()
            ->with(['recordedBy', 'driver'])
            ->latest('spent_at')
            ->limit($showAll ? 50 : 30);

        if ($driverId) {
            $historyQuery->where('driver_user_id', $driverId);
        } elseif (! $showAll) {
            $historyQuery->whereRaw('1 = 0');
        }

        $history = $hasSelection ? $historyQuery->get() : collect();

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
}
