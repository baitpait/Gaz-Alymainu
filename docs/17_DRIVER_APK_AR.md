# تطبيق السائق (APK) — GPS بالخلفية

> **المرحلة 2** من تتبّع المواقع. المرحلة 1 (ويب) موثّقة في `docs/15_DRIVER_LOCATION_TRACKING_AR.md`.

آخر تحديث: 2026-07-27.

---

## 1) الهدف

- تطبيق **Android (Capacitor)** يعرض نفس واجهة الويب (`gaz.baitpait.space`)
- **GPS native بالخلفية** يرسل الموقع للسيرفر حتى مع إغلاق الشاشة
- **لا إعادة بناء** POS / تحصيل / مصروفات — WebView فقط

---

## 2) المعمارية

```
┌─────────────────────────────────────┐
│  APK (Capacitor)                    │
│  WebView → gaz.baitpait.space       │
│  Plugin → BackgroundGeolocation     │
│         → POST /api/driver/location │
└─────────────────────────────────────┘
              │ Bearer Sanctum
              ▼
┌─────────────────────────────────────┐
│  Laravel                            │
│  DriverLocationService              │
│  driver_locations → /drivers/map    │
└─────────────────────────────────────┘
```

---

## 3) API (Sanctum)

| Method | المسار | الغرض |
|--------|--------|--------|
| POST | `/api/apk/bootstrap-session` | دخول APK بدون CSRF → `{ session_url, token? }` |
| GET | `/apk/session/{code}` | فتح جلسة ويب لمرة واحدة (يتجنب 419) |
| POST | `/api/driver/login` | `{ email, password }` → `{ token }` |
| POST | `/api/driver/location` | `{ latitude, longitude, accuracy? }` |
| POST | `/api/driver/logout` | إيقاف المشاركة + حذف token |
| GET | `/api/driver/me` | التحقق من الحساب |
| POST | `/driver/device-token` | (جلسة ويب) إصدار token للـ WebView |

> **ملاحظة 419:** نموذج `/login` داخل WebView يمرّ عبر `bootstrap-session` ثم تنقّل كامل إلى `/apk/session/{code}` حتى تُحفظ كوكيز الجلسة. لا حاجة لإعادة بناء APK بعد نشر هذا المسار على السيرفر.

---

## 4) بناء APK (محلياً)

### المتطلبات
- Node.js 18+
- Android Studio + SDK
- JDK 17+

### الخطوات

```bash
# 1) Backend — بعد git pull على السيرفر أو محلياً
composer install
php artisan migrate --force
npm run build

# 2) Capacitor
cd mobile
npm ci
# للتطوير المحلي غيّر URL في capacitor.config.ts أو:
# DRIVER_APP_URL=http://10.0.2.2:8001 npx cap sync android
npx cap sync android
npx cap open android
```

في Android Studio: **Build → Build Bundle(s) / APK(s) → APK**.

APK debug:
```bash
cd mobile/android && ./gradlew assembleDebug
# المخرج: android/app/build/outputs/apk/debug/app-debug.apk
```

أيقونة الـ APK من `public/branding/logo.png` (ملفات `mipmap-*`).  
واجهة السائق: تبويب **موقعي** مخفي؛ الموقع يُرسل تلقائياً بدون صفحة يدوية.

---

## 5) الإعداد على السيرفر

```env
DRIVER_APP_URL=https://gaz.baitpait.space
DRIVER_LOCATION_PING_SECONDS=30
```

بعد `git pull`:
```bash
php artisan migrate --force
npm run build
php artisan config:cache && php artisan route:cache
```

---

## 6) صلاحيات Android (يضيفها Plugin تلقائياً)

- `ACCESS_FINE_LOCATION`
- `ACCESS_COARSE_LOCATION`
- `ACCESS_BACKGROUND_LOCATION` (Android 10+)
- `FOREGROUND_SERVICE` + `FOREGROUND_SERVICE_LOCATION`
- إشعار دائم: «غاز اليمني — مشاركة موقعك نشطة»

**تعليمات للسائق:**
1. السماح بالموقع **«طوال الوقت»**
2. استثناء التطبيق من **Battery Optimization** إن توقف التتبع

---

## 7) التدفق للسائق

1. تثبيت APK
2. فتح التطبيق → صفحة الدخول
3. تسجيل الدخول → يُفعَّل GPS native تلقائياً
4. استخدام POS / تحصيل / مصروفات كالمعتاد
5. الخروج → يتوقف التتبع

---

## 8) الاختبار

```bash
php artisan test --filter=DriverLocationApi
```

---

## 9) التوزيع

| الطريقة | ملاحظة |
|---------|--------|
| **APK sideload** | رابط تحميل أو WhatsApp — الأسرع |
| **Google Play** | يحتاج حساب مطوّر + سياسة خصوصية |

---

## 10) خطأ 419 PAGE EXPIRED عند الدخول من الـ APK

**السبب:** CapacitorCookies/CapacitorHttp يعترضان الكوكيز ويكسران جلسة Laravel CSRF.

**الإصلاح (مضمّن في المشروع):**
1. `CapacitorCookies` و `CapacitorHttp` معطّلان في `mobile/capacitor.config.ts`
2. `MainActivity` يفعّل قبول الكوكيز في WebView
3. على السيرفر: `SESSION_DOMAIN=null` (host-only) ثم:
   ```bash
   php artisan config:cache && php artisan view:clear
   ```

بعد تغيير إعدادات الجلسة أعد بناء الـ APK (`npx cap sync android` ثم Build).

---

## 11) حدود معروفة

- **iOS:** غير مُنفَّذ في هذه المرحلة (قيود Apple على Background Location)
- **سجل مسار:** غير مُفعَّل — آخر موقع فقط (جدول `driver_locations`)
- **المتصفح:** يبقى `DriverLocationBeacon` كـ fallback طالما الشاشة مفتوحة
- **بدون سيارة على الخريطة:** يعني السائق غير مربوط بمخزن نوعه `vehicle` — عيّنه من «المخازن والسيارات»
