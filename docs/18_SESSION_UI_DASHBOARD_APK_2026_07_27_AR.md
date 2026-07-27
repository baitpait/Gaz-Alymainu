# جلسة 2026-07-27 — واجهة تشغيل، لوحة تحكم، ملف شخصي، APK

> **الغرض:** توثيق كل ما أُنجز في هذه الجلسة (ويب + إنتاج + تطبيق السائق) كمرجع واحد للفريق.
> آخر تحديث: 2026-07-27. المستودع: `baitpait/Gaz-Alymainu` — الفرع `main`.

---

## 1) الملخص التنفيذي

| المجال | ما تم |
|--------|--------|
| دعم فني | صورة واتساب أسفل الشريط الجانبي → `wa.me/970599814758` |
| قائمة المستخدم | قائمة منسدلة: ملف شخصي + تسجيل خروج (ويب/موبايل/مدير/سائق) |
| تغيير كلمة المرور | `/profile` — Livewire `ProfilePage` |
| فلترة قوائم | سجل المبيعات + حركات المخزون (نمط تطبيق/مسح) |
| لوحة التحكم | لقطة تشغيل غاز: اليوم / المخزون والأسطول / ملخص مالي مختصر + صناديق مطوية |
| APK | الاسم «غاز اليمني» + أيقونة من `public/branding/logo.png` |
| إنتاج | سحب إلى `gaz.baitpait.space`؛ إصلاح كلمة مرور المدير بعد خطأ Bcrypt |

---

## 2) الدعم الفني (واتساب)

| بند | قيمة |
|-----|------|
| المكوّن | `resources/views/components/support-whatsapp-link.blade.php` — `variant="sidebar"` |
| الموضع | أسفل `<aside>` في `layouts/app.blade.php` (بدون border، صورة فقط) |
| الصورة | `public/branding/support.png` |
| الرقم | `config('app.support_whatsapp')` ← `SUPPORT_WHATSAPP` (افتراضي `970599814758`) |
| الرابط | `https://wa.me/{phone}?text=...` |

---

## 3) قائمة الحساب والملف الشخصي

### الواجهة
- الهيدر: زر الاسم/الصورة → قائمة Alpine: **الملف الشخصي** | **تسجيل الخروج**
- زر الخروج المنفصل أُزيل

### المسار والصلاحيات
| المسار | الاسم | ملاحظة |
|--------|------|--------|
| `GET /profile` | `profile` | أي مستخدم مصادَق؛ السائق مسموح عبر `RestrictDriverToSales` |

### المنطق
- `App\Livewire\ProfilePage` — عرض الاسم/البريد/الدور (للقراءة) + تغيير كلمة المرور (حالية + جديدة + تأكيد)
- العرض: `resources/views/profile/show.blade.php` + `livewire/profile-page.blade.php`
- اختبارات: `tests/Feature/ProfilePageTest.php`

### مسارات السائق المسموحة (`RestrictDriverToSales`)
`pos.index`, `collections.index`, `driver-expenses.index`, `location.share`, **`profile`**, `logout`

---

## 4) فلترة القوائم

نمط موحّد: نموذج + `تطبيق الفلاتر` / `مسح الفلاتر` عبر `AppliesListFiltersOnAction`.

### سجل المبيعات `/sales` — `SaleList`
| فلتر | URL param |
|------|-----------|
| بحث عام | `q` |
| نوع الدفع | `sale_pay` |
| صنف | `sale_product` |
| سائق (غير السائق) | `sale_driver` |
| مخزن/سيارة | `sale_wh` |
| من/إلى تاريخ | `sale_from` / `sale_to` |

اختبار: `tests/Feature/SaleListFiltersTest.php`

### حركات المخزون `/stock-movements` — `StockMovementList`
| فلتر | URL param |
|------|-----------|
| بحث عام | `q` |
| نوع الحركة | `sm_type` |
| صنف | `sm_product` |
| مخزن (من أو إلى) | `sm_wh` |
| سائق | `sm_driver` |
| من/إلى تاريخ | `sm_from` / `sm_to` |

اختبار: `tests/Feature/StockMovementListFiltersTest.php`

### صفحات كانت لديها فلترة مسبقاً (مرجع)
فواتير مبيعات/مشتريات، مدفوعات عملاء/موردين، مصروفات، موظفون/رواتب، كشوف، تقارير فترة وتقادم ذمم.

---

## 5) لوحة التحكم `/dashboard`

### الخدمة
`App\Services\DashboardSummaryService::forDate()` — تجميعات قابلة للاختبار؛ عند الفشل تُسجَّل في اللوج وتُعرض أصفار (لا 500 بعد الدخول).

### الأقسام المعروضة
1. **اليوم — توزيع الغاز:** مبيعات، نقدي/آجل، تحصيلات، مصروفات سائقين، كاش لدى السائقين  
2. **المخزون والأسطول:** أرصدة صفر، تحميل/إرجاع، تسعير يومي، خريطة/سائقون نشطون  
3. **ملخص مالي مختصر (ILS):** ذمم تقريبية، التزامات موردين، الصندوق الرئيسي، مسودات مشتريات  
4. **تفاصيل الصناديق** (مطوي) عبر `partials/currency-boxes-full`

### ما أُزيل بعد المراجعة
- اختصارات تشغيلية  
- قسم «يحتاج انتباه»

### الاختبار
`tests/Feature/DashboardPageTest.php`

### الملفات
- `resources/views/dashboard.blade.php`
- `app/Services/DashboardSummaryService.php`

---

## 6) الشريط الجانبي (مجموعات قابلة للطي)

- المكوّن: `resources/views/components/nav-group.blade.php`
- الحالة في `localStorage` (`gaz-nav-{name}`)
- المجموعة النشطة تبقى مفتوحة

---

## 7) تطبيق السائق (APK)

| بند | قيمة |
|-----|------|
| `applicationId` | `space.baitpait.gaz.driver` |
| اسم العرض | **غاز اليمني** (`strings.xml` + `capacitor.config.ts`) |
| الأيقونة | مولَّدة من `public/branding/logo.png` → `mipmap-*/ic_launcher*.png` |
| خلفية الأيقونة التكيفية | `#FFFFFF` |
| بناء | `cd mobile/android && JAVA_HOME=…/Android Studio…/jbr ./gradlew assembleDebug` |
| المخرج | `mobile/android/app/build/outputs/apk/debug/app-debug.apk` |
| سطح المكتب (محلي) | `~/Desktop/Gaz-Alymainu-driver.apk` |

تفاصيل أوسع: `docs/17_DRIVER_APK_AR.md`.

---

## 8) إنتاج — حساب المدير وكلمة المرور

| بند | قيمة |
|-----|------|
| البريد الحالي | `gaz@baitpait.com` |
| كلمة المرور (بعد الإصلاح) | `12345678` — **يُفضّل تغييرها لاحقاً** |

### خطأ شائع (Bcrypt) — موثّق من الحادثة

```
This password does not use the Bcrypt algorithm
```

**السبب:**  
`User::where(...)->update(['password' => '...'])` يستخدم Query Builder ويتجاوز الـ cast `hashed` فيكتب النص صريحاً.

**الصحيح:**

```bash
cd /home/sarfesak/public_html/gaz && php artisan tinker --execute='$u=\App\Models\User::where("email","gaz@baitpait.com")->first(); $u->password="كلمة_قوية"; $u->save(); echo "ok";'
```

### تحديث سريع على السيرفر

```bash
cd /home/sarfesak/public_html/gaz && \
git pull origin main && \
php artisan migrate --force && \
php artisan optimize:clear && \
php artisan view:cache && \
php artisan config:cache
```

---

## 9) commits المرتبطة (تقريبي)

من `f7dabeb` … حتى `721e35c` — مجموعات الشريط، الدعم، الملف الشخصي، فلترة المبيعات/المخزون، لوحة التحكم، تقوية اللوحة، علامة APK.

راجع: `git log --oneline f7dabeb^..721e35c`

---

## 10) ملفات يُحدَّث معها هذا المستند

عند تغيير أي مما سبق حدّث أيضاً:
- `docs/PROJECT_LOG.md`
- `docs/07_SYSTEM_OVERVIEW_AR.md` (قسم اللوحة)
- `docs/14_GAS_DRIVER_INVENTORY_SYSTEM_AR.md` (مسارات السائق)
- `docs/17_DRIVER_APK_AR.md`
- `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md` (أوامر كلمة المرور)
