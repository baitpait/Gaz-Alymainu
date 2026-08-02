# PWA — تثبيت غاز اليمني من المتصفح

> تاريخ: 2026-08-02. يكمّل `docs/17_DRIVER_APK_AR.md` (APK للـ GPS الخلفي).

## الغرض

تثبيت اختصار التطبيق على الشاشة الرئيسية من المتصفح (Android Chrome / iOS Safari) **بدون متاجر**.

## ماذا يفعل / ماذا لا يفعل

| القدرة | PWA | APK Capacitor |
|--------|-----|----------------|
| أيقونة على الشاشة + فتح بملء الشاشة | نعم | نعم |
| موقع والمتصفح/التطبيق مفتوح | نعم (beacon ويب) | نعم |
| موقع والشاشة مغلقة (خلفية) | لا | نعم |

## الملفات

| ملف | دور |
|-----|-----|
| `public/manifest.webmanifest` | اسم التطبيق، الألوان، الأيقونات، `start_url=/` |
| `public/sw.js` | Service Worker خفيف (أيقونات + fallback بسيط) |
| `public/pwa/icon-*.png` | أيقونات 192/512 + Apple Touch |
| `resources/js/pwa.js` | تسجيل SW + زر التثبيت (`beforeinstallprompt`) / تلميح iOS |
| `resources/views/partials/pwa-head.blade.php` | وسوم الـ head |
| `resources/views/partials/pwa-install-banner.blade.php` | بانر التثبيت (دخول + داخل التطبيق) |

يُتخطى التثبيت داخل Capacitor WebView.

## تجربة المستخدم

- **Android Chrome:** بانر «تثبيت الآن» عند توفر الحدث.
- **iOS Safari:** تعليمات: مشاركة → إضافة إلى الشاشة الرئيسية.
- يمكن إخفاء البانر لمدة 7 أيام.

## النشر

```bash
git pull origin main
npm ci && npm run build
php artisan view:clear
```

يجب **HTTPS** (متوفر على `gaz.baitpait.space`). محلياً على HTTP قد لا يظهر زر التثبيت في كل المتصفحات.

## تحقق سريع

1. افتح الموقع على الجوال → DevTools/Application → Manifest + Service Worker.
2. أضف للشاشة الرئيسية وجرّب فتحاً مستقلاً.
3. سجّل دخول سائق وتأكد أن `driver-location-beacon` ما زال يرسل الموقع والتبويب مفتوح.
