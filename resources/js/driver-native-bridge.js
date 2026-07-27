/**
 * Business Purpose: يربط WebView (Capacitor) بطبقة GPS native للسائق بعد تسجيل الدخول.
 * يعمل فقط داخل تطبيق APK — لا تأثير على المتصفح العادي.
 */

let watcherStarted = false;

function getCapacitorPlugin(name) {
    return window.Capacitor?.Plugins?.[name] ?? null;
}

function isNativeDriverApp() {
    return Boolean(window.Capacitor?.isNativePlatform?.())
        && document.body?.dataset?.driverNativeBridge === '1';
}

async function postLocation(apiBase, token, location) {
    if (! location) {
        return;
    }

    await fetch(`${apiBase}/driver/location`, {
        method: 'POST',
        headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            latitude: location.latitude,
            longitude: location.longitude,
            accuracy: location.accuracy ?? null,
        }),
    });
}

async function startNativeBackgroundGps(token, apiBase) {
    if (watcherStarted) {
        return;
    }

    const BackgroundGeolocation = getCapacitorPlugin('BackgroundGeolocation');
    const Preferences = getCapacitorPlugin('Preferences');

    if (! BackgroundGeolocation?.addWatcher) {
        console.warn('[gaz-driver] BackgroundGeolocation plugin unavailable');
        return;
    }

    if (Preferences?.set) {
        await Preferences.set({ key: 'driver_api_token', value: token });
        await Preferences.set({ key: 'driver_api_base', value: apiBase });
    }

    const title = document.body?.dataset?.driverBgTitle || 'غاز اليمني';
    const message = document.body?.dataset?.driverBgText || 'مشاركة موقعك نشطة أثناء الوردية';

    await BackgroundGeolocation.addWatcher(
        {
            backgroundTitle: title,
            backgroundMessage: message,
            requestPermissions: true,
            stale: false,
            distanceFilter: 15,
        },
        (location, error) => {
            if (error) {
                return;
            }
            postLocation(apiBase, token, location).catch(() => {});
        },
    );

    watcherStarted = true;
}

async function syncDriverDeviceToken() {
    if (! isNativeDriverApp()) {
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const response = await fetch('/driver/device-token', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf ?? '',
        },
        credentials: 'same-origin',
    });

    if (! response.ok) {
        return;
    }

    const data = await response.json();
    if (data?.token && data?.api_base) {
        await startNativeBackgroundGps(data.token, data.api_base);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => syncDriverDeviceToken().catch(() => {}));
} else {
    syncDriverDeviceToken().catch(() => {});
}

document.addEventListener('livewire:navigated', () => {
    syncDriverDeviceToken().catch(() => {});
});
