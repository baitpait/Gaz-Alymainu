# INCIDENT-002: «The selected طريقة الدفع is invalid» عند تعديل دفعة مورد

**الحالة:** مُغلق (مُصلَح)  
**التاريخ:** 2026-06-29  
**Commit الإصلاح:** `50ceee1` — `fix: normalize legacy payment methods on supplier and client payment edit`  
**مثال:** `https://gaz.baitpait.space/supplier-payments/413/edit`

---

## 1) الملخص التنفيذي

عند تعديل دفعة مورد (أو عميل) قديمة على الإنتاج، يظهر خطأ التحقق:

> **The selected طريقة الدفع is invalid.**

**السبب الجذري:** حقل `method` في قاعدة البيانات يحتوي قيماً **غير معيارية** (عربية أو من الاستيراد القديم)، بينما النموذج يقبل فقط: `cash`, `bank`, `check`, `transfer`.

**الإصلاح:**

1. تطبيع القيمة عند تحميل النموذج (`PaymentMethod::normalize`).
2. أمر `php artisan payments:normalize-methods` لتصحيح السجلات المخزّنة.
3. تحسين الاستيراد القديم ليخزّن أكواداً معيارية من البداية.

---

## 2) الأعراض

| السلوك | التفاصيل |
|--------|----------|
| صفحة التعديل تفتح | بيانات المورد والمبلغ تظهر |
| قائمة «طريقة الدفع» | قد تبدو فارغة أو غير متطابقة |
| عند «حفظ» | رسالة `The selected طريقة الدفع is invalid.` |

---

## 3) التشخيص السريع

### 3.1 قراءة القيمة المخزّنة

```bash
cd /home/sarfesak/public_html/gaz
php artisan tinker --execute="echo App\Models\SupplierPayment::find(413)?->method ?? 'not found';"
```

**نتائج شائعة:**

| القيمة في DB | التفسير |
|--------------|---------|
| `نقداً` | نص عربي قديم |
| `تحويل بنكي` | نص عربي مركّب |
| `طريقة #1` | placeholder من `LegacyErpImportService` |
| `NULL` | يُطبَّع إلى `cash` عند التحميل |

### 3.2 استبعاد مشاكل أخرى

| الفحص | إذا فشل → |
|-------|-----------|
| المستخدم `accountant` أو `manager` | 403 وليس خطأ تحقق |
| `supplier_id` موجود | خطأ مختلف |
| CSS / Livewire | لا يؤثر على رسالة `invalid` |

---

## 4) الحل

### 4.1 سحب الإصلاح من Git

```bash
git pull origin main   # يتضمن commit 50ceee1+
```

### 4.2 تطبيع السجلات على السيرفر

```bash
php artisan payments:normalize-methods --dry-run
php artisan payments:normalize-methods
php artisan view:cache
```

### 4.3 التحقق

- أعد فتح `/supplier-payments/413/edit`
- اختر طريقة الدفع (أو اترك الافتراضي بعد التطبيع)
- احفظ — يجب أن ينجح بدون خطأ

---

## 5) الوقاية

| الإجراء | متى |
|---------|-----|
| تشغيل `payments:normalize-methods` | بعد أي استيراد ERP/XML قديم |
| تخزين أكواد إنجليزية فقط | في Seeders والنماذج الجديدة |
| عدم الاعتماد على نص عربي في `method` | العرض عبر `PaymentMethod::label()` |

---

## 6) مراجع

- `docs/12_PAYMENT_METHODS_AND_LEGACY_NORMALIZATION_AR.md`
- `app/Services/Finance/PaymentMethod.php`
- `tests/Feature/PaymentMethodNormalizationTest.php`
