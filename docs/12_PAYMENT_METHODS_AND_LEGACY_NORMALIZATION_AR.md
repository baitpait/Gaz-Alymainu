# طرق الدفع — القيم المعيارية وتطبيع البيانات القديمة

**الحالة:** ساري  
**آخر تحديث:** 2026-06-29  
**Commits:** `50ceee1` (تطبيع طرق الدفع)، `2d18e7c` (إصلاح عرض `<select>` في Dark Mode)

---

## 1) الهدف التشغيلي

حقل `method` في جداول الدفعات يجب أن يخزّن **أكواداً إنجليزية ثابتة** حتى:

- تعمل نماذج التعديل (Livewire) دون أخطاء تحقق.
- تُصفّى التقارير والكشوف بشكل متسق.
- تبقى الواجهة العربية في العرض فقط (تسميات)، لا في التخزين.

---

## 2) القيم المعيارية (Canonical)

| الكود | التسمية العربية في الواجهة | الاستخدام |
|-------|---------------------------|-----------|
| `cash` | نقداً / نقدي | نقدي |
| `bank` | بنك / بنكي | تحويل بنكي، بطاقة، إيداع |
| `check` | شيك | شيك |
| `transfer` | تحويل | حوالة / تحويل بنكي صريح |

**الكود المرجعي:** `App\Services\Finance\PaymentMethod`

```php
PaymentMethod::CODES;           // ['cash', 'bank', 'check', 'transfer']
PaymentMethod::validationRule(); // required|in:cash,bank,check,transfer
PaymentMethod::label('cash');   // نقداً
PaymentMethod::normalize($raw); // تحويل قيمة قديمة → كود معياري
```

**العرض في التقارير:** `App\Services\Reports\PaymentMethodLabels::label()` — يفوّض إلى `PaymentMethod::label()`.

---

## 3) الجداول المتأثرة

| الجدول | الحقل |
|--------|--------|
| `client_payments` | `method` |
| `supplier_payments` | `method` |
| `salary_payments` | `method` (عند `status=paid`) |

> فواتير التحصيل (`InvoiceForm` / `PurchaseOrderForm`) تنشئ دفعات جديدة بأكواد معيارية مباشرة.

---

## 4) مصادر القيم غير المعيارية (Legacy)

| المصدر | مثال مخزّن | يتحوّل إلى |
|--------|-----------|------------|
| استيراد ERP قديم | `طريقة #1` | `cash` |
| | `طريقة #2` | `bank` |
| | `طريقة #3` | `check` |
| | `طريقة #4` | `transfer` |
| بيانات تجريبية / قديمة | `نقداً` | `cash` |
| | `تحويل بنكي` | `transfer` |
| | `شيك` | `check` |
| XML / ترحيل بدون طريقة | `NULL` | `cash` (افتراضي عند التعديل) |

**ملاحظة:** الاستيراد الجديد عبر `LegacyErpImportService` يطبّع `payment_method_id` عند الإنشاء (منذ `50ceee1`).

---

## 5) سلوك النماذج (Livewire)

عند **فتح تعديل** دفعة عميل أو مورد:

```php
$this->payment_method = PaymentMethod::normalize($payment->method);
```

- يُحمَّل الـ `<select>` بقيمة صالحة.
- عند **الحفظ** تُخزَّن القيمة المعيارية في `method`.

**الملفات:**

- `app/Livewire/SupplierPaymentForm.php`
- `app/Livewire/PaymentForm.php`

---

## 6) أمر Artisan — تطبيع قاعدة البيانات

لتصحيح السجلات القديمة دفعة واحدة (بعد النشر أو بعد استيراد):

```bash
# معاينة بدون كتابة
php artisan payments:normalize-methods --dry-run

# تطبيق
php artisan payments:normalize-methods
```

**الملف:** `app/Console/Commands/NormalizePaymentMethodsCommand.php`

يعالج: `supplier_payments` و `client_payments` حيث `method` ليس من الأكواد الأربعة.

---

## 7) النشر على الإنتاج

```bash
cd /home/sarfesak/public_html/gaz
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan payments:normalize-methods --dry-run
php artisan payments:normalize-methods
php artisan config:cache && php artisan view:cache
```

لا حاجة لـ `migrate` لهذا الإصلاح تحديداً.

---

## 8) الاختبارات

`tests/Feature/PaymentMethodNormalizationTest.php`

- تطبيع نصوص عربية و `طريقة #N`.
- حفظ تعديل دفعة مورد كانت `method = نقداً` → تُحفظ `cash`.

```bash
php artisan test tests/Feature/PaymentMethodNormalizationTest.php
```

---

## 9) مراجع ذات صلة

- `docs/troubleshooting/INCIDENT-002-payment-method-invalid-on-edit.md`
- `docs/03_DATABASE_SPEC.md` — قسم الدفعات
- `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md` — استكشاف الأخطاء
- `docs/10_EMPLOYEES_AND_PAYROLL_AR.md` — رواتب (`method` عند الدفع)
