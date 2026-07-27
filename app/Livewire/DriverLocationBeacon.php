<?php

namespace App\Livewire;

use App\Services\DriverLocationService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

/**
 * Business Purpose: إشارة موقع صامتة للسائق — تُفعَّل تلقائياً بلا أزرار بدء/إيقاف.
 * تعمل طالما أي صفحة من التطبيق مفتوحة على جهاز السائق.
 */
class DriverLocationBeacon extends Component
{
    public function mount(DriverLocationService $locations): void
    {
        Gate::authorize('share-location');

        try {
            $locations->startSharing((int) auth()->id());
        } catch (Throwable) {
            // صامت — لا نُعيق واجهة السائق إن فشل البدء
        }
    }

    /**
     * يستقبل إحداثيات GPS من المتصفح دورياً ويفرض حالة المشاركة.
     */
    public function ping(float $lat, float $lng, ?float $accuracy, DriverLocationService $locations): void
    {
        Gate::authorize('share-location');

        try {
            $locations->updatePosition((int) auth()->id(), $lat, $lng, $accuracy);
        } catch (Throwable) {
            // صامت
        }
    }

    public function render()
    {
        return view('livewire.driver-location-beacon');
    }
}
