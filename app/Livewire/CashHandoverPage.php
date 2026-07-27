<?php

namespace App\Livewire;

use App\Enums\CollectionMethod;
use App\Models\CashHandover;
use App\Models\User;
use App\Services\CashBoxService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * صندوق السائق: عرض الرصيد النقدي/الشيكات غير المسحوب وسحبه إلى الصندوق الرئيسي.
 */
class CashHandoverPage extends Component
{
    public ?int $driverUserId = null;

    public string $amount = '';

    public string $method = 'cash';

    public string $chequeNumber = '';

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('manage-cash-handover'), 403);

        $user = auth()->user();
        if ($user->isDriver()) {
            $this->driverUserId = $user->id;
        }
    }

    public function updatedDriverUserId(): void
    {
        $this->amount = '';
    }

    public function fillFull(CashBoxService $cashBox): void
    {
        if ($this->driverUserId) {
            $this->amount = $this->method === CollectionMethod::Cheque->value
                ? (string) $cashBox->chequeBalance($this->driverUserId)
                : (string) $cashBox->balance($this->driverUserId);
        }
    }

    public function handOver(CashBoxService $cashBox): void
    {
        abort_unless(Gate::allows('manage-cash-handover'), 403);

        $user = auth()->user();
        if ($user->isDriver()) {
            $this->driverUserId = $user->id;
        }

        $this->validate([
            'driverUserId' => 'required|exists:users,id',
            'amount' => 'required|numeric|gt:0',
            'method' => 'required|in:cash,cheque',
            'chequeNumber' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
        ], [], [
            'driverUserId' => 'السائق',
            'amount' => 'المبلغ',
            'method' => 'آلية الدفع',
        ]);

        try {
            $cashBox->handOver(
                (int) $this->driverUserId,
                (float) $this->amount,
                CollectionMethod::from($this->method),
                auth()->id(),
                trim($this->notes) !== '' ? trim($this->notes) : null,
                trim($this->chequeNumber) !== '' ? trim($this->chequeNumber) : null,
            );
        } catch (\RuntimeException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        }

        $this->amount = '';
        $this->chequeNumber = '';
        $this->notes = '';
        $this->dispatch('toast', message: 'تم السحب بنجاح');
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
        $chequeBalance = $this->driverUserId ? $cashBox->chequeBalance($this->driverUserId) : null;
        $totalCash = $this->driverUserId ? $cashBox->totalCashSales($this->driverUserId) : 0;
        $totalHanded = $this->driverUserId ? $cashBox->totalHandedOver($this->driverUserId) : 0;

        $history = $this->driverUserId
            ? CashHandover::query()
                ->where('driver_user_id', $this->driverUserId)
                ->with('receivedBy')
                ->latest('handed_at')
                ->limit(30)
                ->get()
            : collect();

        return view('livewire.cash-handover-page', [
            'drivers' => $drivers,
            'balance' => $balance,
            'chequeBalance' => $chequeBalance,
            'totalCash' => $totalCash,
            'totalHanded' => $totalHanded,
            'history' => $history,
            'isDriver' => $user->isDriver(),
        ]);
    }
}
