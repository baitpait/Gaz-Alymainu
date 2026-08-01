<?php

namespace App\Livewire;

use App\Models\MarketDebtSetting;
use App\Services\MarketDebtService;
use Livewire\Component;

/**
 * شاشة مالية مستقلة: دين السوق للشركة (افتتاحي + آجل − تحصيل نقدي).
 */
class MarketDebtPage extends Component
{
    public string $openingAmount = '0';

    public string $asOfDate = '';

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);

        $setting = MarketDebtSetting::current();
        $this->openingAmount = (string) (float) $setting->opening_amount;
        $this->asOfDate = $setting->as_of_date->toDateString();
        $this->notes = (string) ($setting->notes ?? '');
    }

    public function saveOpening(): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);

        $this->validate([
            'openingAmount' => 'required|numeric|min:0',
            'asOfDate' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ], [], [
            'openingAmount' => 'الرصيد الافتتاحي',
            'asOfDate' => 'تاريخ البداية',
            'notes' => 'ملاحظات',
        ]);

        $setting = MarketDebtSetting::current();
        $setting->update([
            'opening_amount' => (float) $this->openingAmount,
            'as_of_date' => $this->asOfDate,
            'notes' => trim($this->notes) !== '' ? trim($this->notes) : null,
            'updated_by_user_id' => auth()->id(),
        ]);

        $this->dispatch('toast', message: 'تم حفظ إعدادات دين السوق');
    }

    public function render(MarketDebtService $marketDebt)
    {
        return view('livewire.market-debt-page', [
            'summary' => $marketDebt->summary(),
        ]);
    }
}
