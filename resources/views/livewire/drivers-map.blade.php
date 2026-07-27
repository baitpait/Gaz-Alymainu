<div wire:poll.10s="refreshMarkers">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
        <div>
            <h1 class="text-xl font-bold text-[#3D3D3D]">خريطة السائقين</h1>
            <p class="text-sm text-gray-500 mt-1">
                مواقع السائقين أثناء مشاركة الوردية —
                <span class="font-semibold text-[#C9A227]">{{ $sharingCount }}</span> مشارك،
                <span class="font-semibold text-green-600">{{ $freshCount }}</span> حيّ (آخر 3 دقائق).
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('drivers.index') }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">قائمة السائقين</a>
            <button type="button" wire:click="refreshMarkers" class="btn btn-primary">تحديث</button>
        </div>
    </div>

    @if($mapsApiKey === '')
    <div class="card p-6 text-center text-red-600">
        مفتاح Google Maps غير مضبوط. أضف <code class="font-mono text-sm" dir="ltr">GOOGLE_MAPS_API_KEY</code> في ملف البيئة.
    </div>
    @else
    <div class="grid lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 card overflow-hidden p-0">
            <div
                id="drivers-google-map"
                class="w-full h-[420px] lg:h-[560px] bg-gray-100"
                wire:ignore
                x-data="driversMap(@js($mapsApiKey), @js($defaultCenter))"
                x-init="boot()"
                @drivers-markers-updated.window="syncMarkers($event.detail.markers)"
            ></div>
        </div>

        <div class="card p-4 max-h-[560px] overflow-y-auto">
            <h2 class="font-bold text-sm text-[#3D3D3D] mb-3">السائقون على الخريطة</h2>
            @forelse($markers as $m)
            <div class="border-b border-[#E2E4E9] py-3 last:border-0">
                <div class="flex items-center justify-between gap-2">
                    <p class="font-semibold text-sm">{{ $m['name'] }}</p>
                    @if($m['is_fresh'])
                        <span class="text-[10px] font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded">حيّ</span>
                    @else
                        <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded">{{ $m['status_label'] ?? 'قديم' }}</span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mt-0.5">{{ $m['vehicle'] ?? 'بدون سيارة' }}</p>
                <p class="text-xs text-gray-400 mt-1 font-mono" dir="ltr">{{ $m['recorded_at'] ?? '—' }}</p>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-8">لا يوجد سائق يشارك موقعه حالياً.</p>
            @endforelse
        </div>
    </div>
    @endif
</div>

@script
<script>
(() => {
    const detail = { markers: $wire.markers };
    window.dispatchEvent(new CustomEvent('drivers-markers-updated', { detail }));

    $wire.$watch('markers', (markers) => {
        window.dispatchEvent(new CustomEvent('drivers-markers-updated', { detail: { markers } }));
    });
})();

Alpine.data('driversMap', (apiKey, defaultCenter) => ({
    map: null,
    markerObjs: {},
    info: null,
    apiKey,
    defaultCenter,
    fittedOnce: false,

    async boot() {
        if (! this.apiKey) return;
        await this.loadGoogle();
        this.map = new google.maps.Map(this.$el, {
            center: this.defaultCenter,
            zoom: 11,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
        });
        this.info = new google.maps.InfoWindow();
        this.syncMarkers($wire.markers || []);
    },

    loadGoogle() {
        if (window.google?.maps) {
            return Promise.resolve();
        }
        return new Promise((resolve, reject) => {
            const existing = document.querySelector('script[data-gaz-maps]');
            if (existing) {
                existing.addEventListener('load', () => resolve());
                return;
            }
            const s = document.createElement('script');
            s.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(this.apiKey)}`;
            s.async = true;
            s.defer = true;
            s.dataset.gazMaps = '1';
            s.onload = () => resolve();
            s.onerror = () => reject(new Error('Google Maps load failed'));
            document.head.appendChild(s);
        });
    },

    syncMarkers(markers) {
        if (! this.map || ! window.google?.maps) return;

        const seen = {};
        const bounds = new google.maps.LatLngBounds();
        let has = false;

        (markers || []).forEach((m) => {
            seen[m.id] = true;
            const pos = { lat: Number(m.lat), lng: Number(m.lng) };
            has = true;
            bounds.extend(pos);

            if (! this.markerObjs[m.id]) {
                const marker = new google.maps.Marker({
                    map: this.map,
                    position: pos,
                    title: m.name,
                    label: {
                        text: (m.name || '?').charAt(0),
                        color: '#fff',
                        fontWeight: 'bold',
                    },
                });
                marker.addListener('click', () => {
                    const vehicle = m.vehicle ? `<br>${m.vehicle}` : '';
                    const fresh = m.is_fresh ? 'حيّ' : 'قديم';
                    this.info.setContent(`<div style="font-family:sans-serif;min-width:140px"><strong>${m.name}</strong>${vehicle}<br><span dir="ltr">${m.recorded_at || ''}</span><br>${fresh}</div>`);
                    this.info.open(this.map, marker);
                });
                this.markerObjs[m.id] = marker;
            } else {
                this.markerObjs[m.id].setPosition(pos);
            }
        });

        Object.keys(this.markerObjs).forEach((id) => {
            if (! seen[id]) {
                this.markerObjs[id].setMap(null);
                delete this.markerObjs[id];
            }
        });

        if (has && ! this.fittedOnce) {
            this.map.fitBounds(bounds);
            if (this.map.getZoom() > 15) {
                this.map.setZoom(15);
            }
            this.fittedOnce = true;
        }
    },
}));
</script>
@endscript
