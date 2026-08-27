/**
 * Business Purpose: Register the PWA service worker and expose a safe install prompt
 * for browsers (skipped inside Capacitor native WebView).
 */
function isNativeApp() {
    return Boolean(window.Capacitor?.isNativePlatform?.());
}

function isStandaloneDisplay() {
    return window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
}

export function registerPwa() {
    if (isNativeApp()) {
        return;
    }

    if (! ('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {
            // Silent: PWA is progressive enhancement.
        });
    });
}

/**
 * Alpine/component helper state for install CTA.
 */
export function createPwaInstallController() {
    return {
        canInstall: false,
        showIosHint: false,
        dismissed: false,
        deferredPrompt: null,
        /**
         * Business Purpose: Tell the app shell to reserve scroll padding while the
         * fixed install banner is visible so page bottoms stay reachable.
         */
        syncBannerSpace() {
            const visible = ! this.dismissed && (this.canInstall || this.showIosHint);
            document.documentElement.classList.toggle('pwa-banner-visible', visible);
        },
        init() {
            if (isNativeApp() || isStandaloneDisplay()) {
                this.syncBannerSpace();
                return;
            }

            const dismissedAt = localStorage.getItem('gaz-pwa-install-dismissed');
            if (dismissedAt && Date.now() - Number(dismissedAt) < 7 * 24 * 60 * 60 * 1000) {
                this.dismissed = true;
            }

            const ua = window.navigator.userAgent || '';
            const isIos = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
            const isSafari = /Safari/.test(ua) && ! /CriOS|FxiOS|EdgiOS|Chrome/.test(ua);

            if (isIos && isSafari && ! this.dismissed) {
                this.showIosHint = true;
            }

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;
                if (! this.dismissed) {
                    this.canInstall = true;
                }
                this.syncBannerSpace();
            });

            window.addEventListener('appinstalled', () => {
                this.canInstall = false;
                this.deferredPrompt = null;
                this.showIosHint = false;
                this.syncBannerSpace();
            });

            this.syncBannerSpace();
        },
        async install() {
            if (! this.deferredPrompt) {
                return;
            }
            this.deferredPrompt.prompt();
            await this.deferredPrompt.userChoice;
            this.deferredPrompt = null;
            this.canInstall = false;
            this.syncBannerSpace();
        },
        dismiss() {
            this.dismissed = true;
            this.canInstall = false;
            this.showIosHint = false;
            localStorage.setItem('gaz-pwa-install-dismissed', String(Date.now()));
            this.syncBannerSpace();
        },
    };
}

// Expose for inline Alpine x-data without bundling Alpine.store complexity
window.GazPwaInstall = createPwaInstallController;
