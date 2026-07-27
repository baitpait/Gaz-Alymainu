<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\DailyPriceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * لوحة تحديد سعر بيع الجرة لكل صنف في يوم محدد (بالشيكل).
 */
class DailyPriceBoard extends Component
{
    #[Url(as: 'date')]
    public string $date = '';

    /** @var array<int, string> سعر البيع لكل صنف (product_id => price) */
    public array $prices = [];

    public function mount(): void
    {
        abort_unless(Gate::allows('view-inventory'), 403);

        if ($this->date === '') {
            $this->date = Carbon::now()->toDateString();
        }

        $this->loadPrices();
    }

    public function updatedDate(): void
    {
        $this->loadPrices();
    }

    private function loadPrices(): void
    {
        $this->prices = [];

        $products = Product::query()->stockTracked()->orderBy('name')->get(['id']);
        $existing = \App\Models\ProductDailyPrice::query()
            ->whereIn('product_id', $products->pluck('id'))
            ->whereDate('price_date', $this->date)
            ->where('currency_code', DailyPriceService::DEFAULT_CURRENCY)
            ->pluck('sale_price', 'product_id');

        foreach ($products as $p) {
            $this->prices[$p->id] = isset($existing[$p->id]) ? (string) $existing[$p->id] : '';
        }
    }

    public function save(DailyPriceService $service): void
    {
        abort_unless(Gate::allows('manage-daily-prices'), 403);

        $this->validate([
            'date' => 'required|date',
            'prices.*' => 'nullable|numeric|min:0',
        ], [], [
            'date' => 'التاريخ',
        ]);

        $count = 0;
        foreach ($this->prices as $productId => $price) {
            if (trim((string) $price) === '') {
                continue;
            }
            $service->setPrice((int) $productId, $this->date, (float) $price, DailyPriceService::DEFAULT_CURRENCY, auth()->id());
            $count++;
        }

        $this->dispatch('toast', message: "تم حفظ أسعار {$count} صنف ليوم {$this->date}");
    }

    public function render()
    {
        $products = Product::query()->stockTracked()->orderBy('name')->get();

        return view('livewire.daily-price-board', ['products' => $products]);
    }
}
