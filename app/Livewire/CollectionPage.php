<?php

namespace App\Livewire;

use App\Enums\CollectionMethod;
use App\Models\Collection;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CashBoxService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * صفحة التحصيل المستقلة: تسجيل مبلغ محصّل (نقدي/شيك) بلا ذمم/زبون.
 * النقدي يدخل صندوق السائق، والشيك يُوثَّق كحركة مالية.
 */
class CollectionPage extends Component
{
    public ?int $driverUserId = null;

    public string $amount = '';

    public string $method = 'cash';

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('record-sales'), 403);

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
        abort_unless(Gate::allows('record-sales'), 403);

        $user = auth()->user();
        if ($user->isDriver()) {
            $this->driverUserId = $user->id;
        }

        $this->validate([
            'driverUserId' => 'required|exists:users,id',
            'amount' => 'required|numeric|gt:0',
            'method' => 'required|in:cash,cheque',
            'notes' => 'nullable|string|max:2000',
        ], [], [
            'driverUserId' => 'السائق',
            'amount' => 'المبلغ',
            'method' => 'آلية الدفع',
        ]);

        $vehicleId = Warehouse::query()
            ->where('type', 'vehicle')
            ->where('assigned_user_id', $this->driverUserId)
            ->value('id');

        try {
            $cashBox->recordCollection(
                (int) $this->driverUserId,
                (float) $this->amount,
                CollectionMethod::from($this->method),
                $vehicleId ? (int) $vehicleId : null,
                null,
                auth()->id(),
                trim($this->notes) !== '' ? trim($this->notes) : null,
            );
        } catch (\RuntimeException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        }

        $this->amount = '';
        $this->notes = '';
        $this->method = 'cash';
        $this->dispatch('toast', message: 'تم حفظ التحصيل بنجاح');
    }

    public function render(CashBoxService $cashBox)
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $drivers = collect();
        if (! $user->isDriver()) {
            $drivers = User::query()
                ->where('role', 'driver')
                ->where('is_active', true)
                ->orderBy('full_name')
                ->get(['id', 'full_name']);
        }

        $cashToday = $this->driverUserId ? $cashBox->cashCollectionsForDate($this->driverUserId, $today) : 0;
        $chequeToday = $this->driverUserId ? $cashBox->chequeCollectionsForDate($this->driverUserId, $today) : 0;

        $history = $this->driverUserId
            ? Collection::query()
                ->where('driver_user_id', $this->driverUserId)
                ->latest('collected_at')
                ->limit(30)
                ->get()
            : collect();

        return view('livewire.collection-page', [
            'drivers' => $drivers,
            'cashToday' => $cashToday,
            'chequeToday' => $chequeToday,
            'history' => $history,
            'isDriver' => $user->isDriver(),
        ]);
    }
}
