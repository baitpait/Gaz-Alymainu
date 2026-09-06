# جلسة 2026-08-27 — تعديل مبيعات/مصروفات + تمرير الموبايل

> **الغرض:** إغلاق توثيق ما أُنجز ونُشر بعد افتتاح الجلسة 26.
> Commit الإنتاج: `c0fe2e2`.
> إنتاج: `https://gaz.baitpait.space` → `/home/sarfesak/public_html/gaz` — منشور ومزامَن.

---

## 1) ملخص تنفيذي

| البند | حالة | ملاحظة |
|--------|------|--------|
| تعديل المبيعات (كمية/سعر/ملاحظات) من `/sales` | ✅ | Gate `update-sales` — محاسب/مدير |
| حماية إلغاء/تخفيض البيع النقدي بعد تسليم أو صرف الكاش | ✅ | عبر رصيد صندوق السائق |
| فلترة مصروفات السائق + تعديل للمحاسب | ✅ | Gate `update-driver-expenses` |
| إصلاح تمرير الموبايل/APK تحت الشريط السفلي (كل الصفحات) | ✅ | layout مشترك + CSS + PWA |
| رفع GitHub + مزامنة السيرفر + `npm run build` | ✅ | `c0fe2e2` |

---

## 2) تعديل المبيعات

- **الخدمة:** `SalesService::updateSale($sale, $quantity, $unitPrice, $notes, $userId)`
- **المخزون:** زيادة كمية → `saleOut`؛ نقص كمية → `restoreForVoidedSale`
- **الكاش:** تخفيض إجمالي بيع نقدي ممنوع إن لم يكفِ رصيد صندوق السائق
- **الواجهة:** مودال تعديل في `SaleList`
- **اختبارات:** `tests/Feature/SaleEditTest.php`

---

## 3) مصروفات السائق

- **الخدمة:** `CashBoxService::updateExpense` (مبلغ/تصنيف/ملاحظات؛ زيادة المبلغ محدودة برصيد الكاش)
- **الواجهة:** فلاتر بحث/تصنيف/تاريخ + مودال تعديل في `DriverExpensePage`
- **اختبارات:** امتداد `DriverExpensePageTest`

---

## 4) تمرير الموبايل (على مستوى النظام)

**المشكلة:** الشريط السفلي الثابت + `flex-1 min-h-0` بدون تمرير على `main` → آخر الصفوف تُغطى أو تُقصّ (متصفح/APK). بانر تثبيت PWA يزيد التغطية.

**الحل (مشترك لكل صفحات `x-layouts.app`):**

| عنصر | التغيير |
|------|---------|
| `body` | `app-shell` = ارتفاع `100dvh` + منع قصّ الجسم |
| `main` | `app-main-scroll` = منطقة التمرير الوحيدة + حشو سفلي ديناميكي |
| CSS vars | `--app-bottom-nav` + `--app-pwa-banner` + `safe-area-inset-bottom` |
| HTML | صنف `has-mobile-bottom-nav` لمن لديه `record-sales` |
| PWA | `syncBannerSpace()` يفعّل `pwa-banner-visible` أثناء ظهور البانر |
| الشريط | `app-bottom-nav` مع padding للـ home indicator |
| اختبار | `AppShellScrollLayoutTest` |

**ملفات:** `resources/views/components/layouts/app.blade.php`، `resources/css/app.css`، `resources/js/pwa.js`، `partials/pwa-install-banner.blade.php`

**نشر الأصول:** `public/build` في `.gitignore` → على السيرفر إلزامي `npm run build` بعد `git pull`.

---

## 5) نشر الإنتاج (2026-08-27)

```text
محلي:  commit c0fe2e2 → push alymainu/main
سيرفر: cd /home/sarfesak/public_html/gaz
       git pull --ff-only origin main
       npm run build
       php artisan view:clear && config:clear && view:cache
```

- لا migrate لهذا الـ commit.
- لم تُلمس قواعد بيانات ولا مجلدات مشاريع أخرى على السيرفر.

---

## 6) بقايا معلّقة (للجلسة القادمة)

1. أصناف «شراء فقط» — إخفاء من POS / علم قابل للبيع.
2. فجوة سيارة أيهم / جرة 48 (−1) إن لم تُسوَّ بجرد.
3. التحقق من فاتورة التقييم على الإنتاج.

---

## 7) مراجع

- افتتاح الجلسة: `docs/26_SESSION_KICKOFF_2026_08_27_AR.md`
- السجل: `docs/PROJECT_LOG.md`
- النشر: `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md`
- الغاز/السائق: `docs/14_GAS_DRIVER_INVENTORY_SYSTEM_AR.md`
