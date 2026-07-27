{{-- إشارة موقع صامتة: لا واجهة للسائق، تعمل في الخلفية طالما الصفحة مفتوحة --}}
<div
    wire:ignore.self
    class="hidden"
    aria-hidden="true"
    x-data="driverLocationBeacon()"
    x-init="boot()"
></div>

@script
<script>
Alpine.data('driverLocationBeacon', () => ({
    intervalId: null,
    wakeLock: null,

    boot() {
        this.send();
        this.intervalId = setInterval(() => this.send(), 30000);

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                this.send();
                this.requestWakeLock();
            }
        });

        this.requestWakeLock();
    },

    async requestWakeLock() {
        try {
            if ('wakeLock' in navigator) {
                this.wakeLock = await navigator.wakeLock.request('screen');
            }
        } catch (_) {
            // الجهاز أو المتصفح لا يدعم / رفض
        }
    },

    send() {
        if (! navigator.geolocation) {
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                $wire.ping(
                    pos.coords.latitude,
                    pos.coords.longitude,
                    pos.coords.accuracy ?? null
                );
            },
            () => {},
            { enableHighAccuracy: true, maximumAge: 15000, timeout: 20000 }
        );
    },
}));
</script>
@endscript
