<?php

namespace App\Livewire;

use App\Services\DriverLocationService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Business Purpose: خريطة إدارة تعرض مواقع السائقين المشاركين في الوقت شبه الحيّ.
 */
class DriversMap extends Component
{
    /** @var list<array<string, mixed>> */
    public array $markers = [];

    public int $sharingCount = 0;

    public int $freshCount = 0;

    public function mount(DriverLocationService $locations): void
    {
        Gate::authorize('view-driver-locations');
        $this->refreshMarkers($locations);
    }

    public function refreshMarkers(DriverLocationService $locations): void
    {
        Gate::authorize('view-driver-locations');

        $this->markers = $locations->mapMarkers();
        $this->sharingCount = count($this->markers);
        $this->freshCount = count(array_filter($this->markers, fn (array $m): bool => (bool) ($m['is_fresh'] ?? false)));
    }

    public function render()
    {
        return view('livewire.drivers-map', [
            'mapsApiKey' => (string) config('services.google_maps.key', ''),
            'defaultCenter' => [
                'lat' => (float) config('services.google_maps.default_lat', 31.9038),
                'lng' => (float) config('services.google_maps.default_lng', 35.2034),
            ],
        ]);
    }
}
