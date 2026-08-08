# PWA — تثبيت غاز اليمني من المتصفح

> تاريخ: 2026-08-02. يكمّل `docs/17_DRIVER_APK_AR.md` (APK للـ GPS الخلفي).
> نُشر على الإنتاج: `gaz.baitpait.space` — commit `c00f64b` + `npm run build` ناجح كـ `sarfesak`.

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

> ملاحظة: `manifest` / `sw.js` / الأيقونات تُسحب مع `git pull` فوراً. تسجيل الـ SW من JS يحتاج **`npm run build`** لأن `public/build` في `.gitignore`.

## تجربة المستخدم

- **Android Chrome:** بانر «تثبيت الآن» عند توفر الحدث.
- **iOS Safari:** تعليمات: مشاركة → إضافة إلى الشاشة الرئيسية.
- يمكن إخفاء البانر لمدة 7 أيام.

## النشر على السيرفر (صحيح)

**لا تشغّل `npm ci` كـ `root`.** سبب الحادثة 2026-08-02: Puppeteer يحاول الكتابة على `/root/.config/puppeteer` → `EACCES`، مع تحذيرات Node قديم إن كان الـ PATH يشير لـ 16.

```bash
cd /home/sarfesak/public_html/gaz
git pull origin main

su -s /bin/bash sarfesak -c '
  cd /home/sarfesak/public_html/gaz && \
  export PUPPETEER_SKIP_DOWNLOAD=true && \
  npm ci --ignore-scripts && \
  npm run build && \
  php artisan view:clear
'
```

| متغير / خيار | لماذا |
|--------------|--------|
| `su … sarfesak` | ملكية `public/build` لمستخدم الموقع؛ تجنّب مسار `/root/.config` |
| `PUPPETEER_SKIP_DOWNLOAD=true` | لا حاجة لـ Chromium عند بناء الأصول فقط |
| `npm ci --ignore-scripts` | يمنع سكربت `puppeteer` postinstall الفاشل |

تحقق بعد البناء:

```bash
ls -la public/build/manifest.json public/sw.js public/manifest.webmanifest
```

يجب **HTTPS** (`https://gaz.baitpait.space`). محلياً على HTTP قد لا يظهر زر التثبيت.

## تحقق من الجوال

1. افتح الموقع على Chrome → قائمة المتصفح أو بانر «تثبيت».
2. DevTools (إن أمكن) → Application → Manifest + Service Worker = نشط.
3. سجّل دخول سائق وتأكد أن الموقع يُرسل والصفحة مفتوحة (`driver-location-beacon`).

## Troubleshoot سريع

| عرض | السبب | العلاج |
|-----|--------|--------|
| `EACCES … /root/.config/puppeteer` | `npm` كـ root | الأمر أعلاه بـ `sarfesak` + `PUPPETEER_SKIP_DOWNLOAD` |
| `EBADENGINE` Node 16 | PATH يشير لـ node قديم | استخدم Node ≥ 20 لمستخدم `sarfesak` |
| لا بانر تثبيت | لم يُبنَ JS / HTTP / أو التطبيق مثبّت مسبقاً | `npm run build` + HTTPS؛ جرّب نافذة خاصة |
| Manifest 404 | ملفات غير مسحوبة | `git pull` ثم تحقق `public/manifest.webmanifest` |
