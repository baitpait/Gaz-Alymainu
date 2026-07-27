# تسويات الذمة وكشف الحساب — غاز اليمين

> **الغرض:** توثيق **التسويات على الذمة** (خصم/إعفاء دون تعديل الفواتير) و**كشف حساب العميل/المورد** كما هو مُنفَّذ في التطبيق.  
> **مرجع تقني:** `App\Services\ClientStatementService`، `App\Services\SupplierStatementService`.

---

## 1) المشكلة التي تُحل

| السيناريو | بدون تسوية | مع تسوية |
|-----------|------------|----------|
| فاتورة 860، دفعة 800، إعفاء 60 | الرصيد يبقى **60** | الرصيد **0** |
| لا نريد تعديل الفاتورة القديمة | يجب تعديل `discount_amount` | تسجيل **تسوية 60** |

**التسوية ليست دفعة نقدية** — لا تدخل «صندوق التحصيل»؛ تُنقص الذمة فقط.

---

## 2) المعادلة (لكل عملة)

### عميل

```
الرصيد المستحق = إجمالي الفواتير الصادرة − إجمالي الدفعات − إجمالي التسويات
```

- **فواتير:** `invoices` حيث `status = issued` و`deleted_at` فارغ.
- **دفعات:** `client_payments` (نقد/بنك/…).
- **تسويات:** `client_balance_adjustments` (مبلغ موجب دائماً في الجدول؛ يُعرض في الكشف كـ `−`).

### مورد

```
المتبقي للمورد = إجمالي أوامر الشراء الصادرة − إجمالي الدفعات − إجمالي التسويات
```

---

## 3) الجداول

### `client_balance_adjustments`

| عمود | نوع | ملاحظة |
|------|-----|--------|
| `client_id` | FK | |
| `amount` | decimal(15,4) | **> 0** |
| `currency_code` | char(3) | |
| `adjustment_date` | date | |
| `type` | string | `settlement_discount` \| `write_off` \| `other` |
| `reason` | string nullable | سبب مختصر |
| `notes` | text nullable | |
| `recorded_by_user_id` | FK nullable | |
| `deleted_at` | soft delete | |

**فهرس MySQL:** `cba_client_cur_date_idx` على `(client_id, currency_code, adjustment_date)` — اسم قصير (حد MySQL 64 حرفاً).

### `supplier_balance_adjustments`

نفس البنية مع `supplier_id`. الفهرس: `sba_supplier_cur_date_idx`.

**الترحيل:** `database/migrations/2026_05_25_120000_create_balance_adjustments_tables.php` (idempotent عند إعادة التشغيل بعد فشل جزئي).

---

## 4) أنواع التسوية (واجهة عربية)

| `type` (كود) | التسمية في الواجهة |
|--------------|-------------------|
| `settlement_discount` | خصم تسوية |
| `write_off` | إعفاء |
| `other` | أخرى |

---

## 5) المسارات والقائمة الجانبية

| المسار | الاسم | الصلاحية |
|--------|-------|----------|
| `/client-adjustments` | `client-adjustments.index` | محاسب/مدير — **قائمة + تسوية جديدة** |
| `/clients/{client}/adjustments/create` | `clients.adjustments.create` | محاسب |
| `/clients/{client}/adjustments/{id}/edit` | `clients.adjustments.edit` | محاسب |
| DELETE `/clients/{client}/adjustments/{id}` | `clients.adjustments.destroy` | مدير |
| `/supplier-adjustments` | `supplier-adjustments.index` | محاسب/مدير |
| `/suppliers/{supplier}/adjustments/create` | `suppliers.adjustments.create` | محاسب |
| `/suppliers/{supplier}/adjustments/{id}/edit` | `suppliers.adjustments.edit` | محاسب |
| DELETE `/suppliers/{supplier}/adjustments/{id}` | `suppliers.adjustments.destroy` | مدير |

**القائمة الجانبية** (`components/layouts/app.blade.php`):

- تحت **المبيعات:** «تسويات العملاء»
- تحت **المشتريات:** «تسويات الموردين»

**أماكن إضافية:**

- كشف حساب العميل/المورد → زر «+ تسوية على الذمة»
- صفحة تفاصيل العميل/المورد → زر «تسوية»

---

## 6) مكوّنات Livewire

| الشاشة | المكوّن |
|--------|---------|
| قائمة تسويات العملاء | `ClientAdjustmentList` |
| نموذج تسوية عميل | `ClientAdjustmentForm` |
| قائمة تسويات الموردين | `SupplierAdjustmentList` |
| نموذج تسوية مورد | `SupplierAdjustmentForm` |
| كشف عميل | `ClientStatement` |
| كشف مورد | `SupplierStatement` |

**السياسات:** `ClientBalanceAdjustmentPolicy`، `SupplierBalanceAdjustmentPolicy` (مثل الدفعات: إنشاء/تعديل محاسب، حذف مدير).

---

## 7) عرض كشف حساب العميل

### ملخص أعلى كل عملة

1. إجمالي الفواتير  
2. إجمالي الدفعات  
3. **إجمالي التسويات**  
4. **الرصيد المستحق**  
5. نص: `الرصيد = الفواتير − الدفعات − التسويات`

### جدول الحركات (ترتيب زمني)

| نوع الحركة | عمود المبلغ | تفاصيل |
|------------|-------------|--------|
| فاتورة | `+` | **تفاصيل بنود الفاتورة** (جدول فرعي) كما كانت |
| دفعة | `−` | |
| تسوية | `−` (بنفسجي) | نوع + سبب |

**لا** أعمدة مدين/دائن. **لا** عمود «رصيد متراكم» في كل صف (لتجنب التكرار المربك).

### PDF و CSV

- **PDF:** `resources/views/pdf/client-statement.blade.php` — ملخص + حركات + بنود الفاتورة.
- **CSV:** `ClientStatementService::toCsvRows()` — ترتيب زمني + صفوف ملخص (فواتير، دفعات، تسويات، رصيد).

---

## 8) ميزات مرتبطة (نفس الإصدار)

| الميزة | ملخص |
|--------|------|
| **بحث عميل في الفاتورة** | Trait `FiltersClientsForSelect` في `InvoiceForm`، `InvoiceList`، `PaymentForm` |
| **كشف مبسّط** | ملخص فواتير−دفعات−تسويات؛ تفاصيل بنود الفاتورة محفوظة |

---

## 9) اختبارات

| ملف | ما يغطيه |
|-----|----------|
| `tests/Feature/ClientBalanceAdjustmentTest.php` | معادلة 860−800−60=0، واجهة، كشف |
| `tests/Feature/ClientStatementTest.php` | عملات منفصلة، CSV، PDF |
| `tests/Feature/SupplierStatementTest.php` | مورد، CSV |

---

## 10) نشر واستكشاف أخطاء

```bash
git pull origin main
php artisan migrate --force
php artisan optimize:clear
```

| الخطأ | السبب | الحل |
|-------|--------|------|
| `Identifier name ... is too long` (MySQL) | اسم فهرس تلقائي > 64 حرف | التحديث `93f88b0+` يستخدم `cba_client_cur_date_idx`؛ أعد `migrate --force` |
| جدول موجود بدون فهرس | فشل جزئي سابق | الترحيل idempotent يضيف الفهرس؛ أو `DROP TABLE` ثم `migrate` |

---

## 11) ما لم يُنفَّذ (عمداً)

- ربط تسوية بفاتورة معيّنة (`invoice_id`).
- سند PDF للتسوية.
- دفعة سالبة (مُرفوضة — تُشوّه التحصيل النقدي).
- دمج التسويات في «صناديق العملات» (ليست تدفقاً نقدياً).

---

## 12) مراجع

| الملف | الموضوع |
|-------|---------|
| `docs/07_SYSTEM_OVERVIEW_AR.md` | خريطة المسارات والمكوّنات |
| `docs/03_DATABASE_SPEC.md` | مخطط الجداول |
| `docs/04_REPORTS_AND_UI_MATRIX.md` | مصفوفة التقارير |
| `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md` | النشر |
