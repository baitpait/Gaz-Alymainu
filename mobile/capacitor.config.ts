import type { CapacitorConfig } from '@capacitor/cli';

const serverUrl = process.env.DRIVER_APP_URL || 'https://gaz.baitpait.space';

/**
 * CapacitorCookies + CapacitorHttp يجب تعطيلهما مع server.url عن بُعد —
 * وإلا تكسر كوكيز جلسة Laravel ويظهر 419 PAGE EXPIRED عند تسجيل الدخول.
 */
const config: CapacitorConfig = {
  appId: 'space.baitpait.gaz.driver',
  appName: 'غاز اليمني - سائق',
  webDir: 'www',
  server: {
    url: serverUrl,
    cleartext: serverUrl.startsWith('http://'),
    androidScheme: 'https',
  },
  android: {
    allowMixedContent: false,
    backgroundColor: '#0B1929',
  },
  plugins: {
    CapacitorCookies: {
      enabled: false,
    },
    CapacitorHttp: {
      enabled: false,
    },
    BackgroundGeolocation: {
      notificationTitle: 'غاز اليمني',
      notificationText: 'مشاركة موقعك نشطة أثناء الوردية',
    },
  },
};

export default config;
