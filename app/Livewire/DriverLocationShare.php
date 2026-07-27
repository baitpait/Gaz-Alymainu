<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Business Purpose: صفحة معلومات فقط — المشاركة تتم تلقائياً عبر DriverLocationBeacon.
 */
class DriverLocationShare extends Component
{
    public function mount(): void
    {
        Gate::authorize('share-location');
    }

    public function render()
    {
        return view('livewire.driver-location-share');
    }
}
