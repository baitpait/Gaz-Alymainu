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
 * مستقلة تمامًا عن وحدة المصروفات العامة.
 */
class DriverExpensePage extends Component
{
    public ?int $driverUserId = null;

    public string $amount = '';

    public string $category = 'fuel';

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('manage-driver-expenses'), 403);

        $user = auth()->user();
        if ($user->isDriver()) {
            $this->driverUserId = $user->id;
        }
    }

    public function updatedDriverUserId(): void
    {
        $this->amount = '';
    }

    public function save(CashBoxService $cashBox): void
    {
        abort_unless(Gate::allows('manage-driver-expenses'), 403);

        $user = auth()->user();
        if ($user->isDriver()) {
            $this->driverUserId = $user->id;
        }

        $this->validate([
            'driverUserId' => 'required|exists:users,id',
            'amount' => 'required|numeric|gt:0',
            'category' => 'required|in:fuel,maintenance,other',
            'notes' => 'nullable|string|max:2000',
        ], [], [
            'driverUserId' => 'السائق',
            'amount' => 'المبلغ',
            'category' => 'التصنيف',
        ]);

        // السيارة المسندة للسائق (إن وُجدت) لربط المصروف بها.
        $vehicleId = Warehouse::query()
            ->where('type', 'vehicle')
            ->where('assigned_user_id', $this->driverUserId)
            ->value('id');

        try {
            $cashBox->recordExpense(
                (int) $this->driverUserId,
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

        $balance = $this->driverUserId ? $cashBox->balance($this->driverUserId) : null;
        $totalExpenses = $this->driverUserId ? $cashBox->totalDriverExpenses($this->driverUserId) : 0;

        $history = $this->driverUserId
            ? DriverExpense::query()
                ->where('driver_user_id', $this->driverUserId)
                ->with('recordedBy')
                ->latest('spent_at')
                ->limit(30)
                ->get()
            : collect();

        return view('livewire.driver-expense-page', [
            'drivers' => $drivers,
            'balance' => $balance,
            'totalExpenses' => $totalExpenses,
            'categories' => DriverExpenseCategory::options(),
            'history' => $history,
            'isDriver' => $user->isDriver(),
        ]);
    }
}
