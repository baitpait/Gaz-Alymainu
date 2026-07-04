# واجهات فواتير العملاء وفواتير المشتريات — مرجع تنفيذي

**الإصدار:** 1.0 — **التاريخ:** 2026-05-12  
**الجمهور:** مطوّرون، مراجعو واجهة، دعم تشغيل.

---

## 1) نظرة عامة

| النطاق | الوصف |
|--------|--------|
| **فواتير العملاء** | إنشاء وتعديل عبر صفحات كاملة (`InvoiceForm`)؛ القائمة في `InvoiceList` مع روابط إنشاء/تعديل/طباعة. |
| **فواتير المشتريات** | إنشاء وتعديل عبر صفحات كاملة (`PurchaseOrderForm`)؛ القائمة في `PurchaseOrderList` مع عرض سريع، حذف، وروابط للصفحات. |

**مبدأ الواجهة:** عربية فقط، اتجاه RTL، تسميات المستخدم بالعربية (رموز العملات مثل `ILS` مسموحة).

---

## 2) المسارات (Routes)

| المسار | الاسم | من يصل؟ |
|--------|--------|----------|
| `GET /invoices` | `invoices.index` | مستخدم مسجّل |
| `GET /invoices/create` | `invoices.create` | محاسب/مدير (`isAccountant`) — يحمّل `InvoiceForm` |
| `GET /invoices/{invoice}/edit` | `invoices.edit` | محاسب |
| `GET /purchase-orders` | `purchase-orders.index` | مستخدم مسجّل |
| `GET /purchase-orders/create` | `purchase-orders.create` | من يملك `create` على `PurchaseOrder` (المحاسب/المدير) |
| `GET /purchase-orders/{purchaseOrder}/edit` | `purchase-orders.edit` | من يملك `update` على الطلب |
| `GET /purchase-orders/{purchaseOrder}` | `purchase-orders.show` | من يملك `view` |

**ترتيب التعريف في `routes/web.php`:** يجب أن تسبق مسارات `create` و`edit` المسار العام `{purchaseOrder}` حتى لا يُفسَّر `create` كمعرّف.

---

## 3) مكوّنات Livewire والعروض

### 3.1 فاتورة عميل — `InvoiceForm`

| البند | القيمة |
|--------|--------|
| **الملف** | `app/Livewire/InvoiceForm.php` |
| **العرض** | `resources/views/livewire/invoice-form.blade.php` |
| **التهيئة** | `mount(?Invoice $invoice = null)` — يتطلب محاسباً؛ `Gate::authorize` للإنشاء/التعديل. |
| **فاتورة جديدة** | تاريخ المستند = اليوم؛ يُضاف سطر بند فارغ تلقائياً إن لم تُحمَّل بنود. |
| **البنود** | جدول داخل إطار بحدود؛ `line_total` من السعر × الكمية؛ مزامنة عند الحفظ. |
| **بحث الصنف** | `wire:model.live` على `lines.{i}.product_search` + `onProductSearchFocus` + `refreshProductAutocompleteForLine`. |
| **Livewire `updatedLines`** | يجب أن يقبل `$key = null` عند مزامنة `$lines` كاملة (مثلاً `addLine`) — راجع `INCIDENT-005`. |
| **طباعة / PDF** | من القائمة: `<x-document-export-buttons>`؛ من صفحة الطباعة: `<x-print-page-actions>`. |
| **الإجمالي** | إن وُجدت بنود بعناوين غير فارغة يُحسب الإجمالي من البنود والخصم؛ وإلا يُعرض حقل **إجمالي كلي** يدوي (`$hasTitledLines`). |
| **بعد الحفظ** | `session()->flash('toast', …)` ثم `redirect` إلى `invoices.index` مع `navigate: true`. |

### 3.2 قائمة فواتير العملاء — `InvoiceList`

| البند | القيمة |
|--------|--------|
| **الملف** | `app/Livewire/InvoiceList.php` |
| **العرض** | `resources/views/livewire/invoice-list.blade.php` |
| **السلوك** | فلاتر، عرض منبثق سريع، روابط لإنشاء/تعديل/**طباعة/PDF**؛ نافذة إنشاء/تعديل كاملة في القائمة معطّلة (`@if(false)`) لصالح الصفحات المنفصلة. |

### 3.3 فاتورة مشتريات — `PurchaseOrderForm`

| البند | القيمة |
|--------|--------|
| **الملف** | `app/Livewire/PurchaseOrderForm.php` |
| **العرض** | `resources/views/livewire/purchase-order-form.blade.php` |
| **الصفحات** | `resources/views/purchase-orders/create.blade.php`، `edit.blade.php` |
| **التهيئة** | `mount(?PurchaseOrder $purchaseOrder = null)` — محاسب؛ تفويض `create`/`update`. |
| **حالة المستند في القاعدة** | `draft`، `issued`، `void` (في الواجهة: مسودة / صادر / ملغى). |
| **`legacy_po_no`** | اختياري، فريد عند التعبئة (`Rule::unique` مع `ignore` عند التعديل). |
| **البنود** | يشترط بنداً واحداً على الأقل بعنوان؛ إجمالي > 0. |
| **بعد الحفظ** | توجيه إلى `purchase-orders.index` مع `session()->flash('toast', …)`. |

### 3.4 قائمة فواتير المشتريات — `PurchaseOrderList`

| البند | القيمة |
|--------|--------|
| **الملف** | `app/Livewire/PurchaseOrderList.php` |
| **العرض** | `resources/views/livewire/purchase-order-list.blade.php` |
| **الميزات** | بحث، فلتر مورد، ترقيم `WithPerPagePagination`، عرض سريع (`viewingId`)، تأكيد حذف للمدير. |
| **لا يوجد** | مودال إنشاء/تعديل — استُبدل بروابط إلى `purchase-orders.create` و`purchase-orders.edit`. |

### 3.5 صفحة تفاصيل مشتريات — `purchase-orders/show`

| البند | القيمة |
|--------|--------|
| **العرض** | `resources/views/purchase-orders/show.blade.php` |
| **زر تعديل** | يظهر بـ `@can('update', $purchaseOrder)` للمحاسب/المدير. |

---

## 4) ترتيب البطاقات في النماذج (فاتورة عميل + فاتورة مشتريات)

من الأعلى إلى الأسفل داخل العمود الرئيسي (بعد شريط العنوان):

1. **معلومات المستند / الفاتورة** — بطاقة بعرض كامل: العميل أو المورد، ثم حقول في صف مرن (`flex-wrap`) لرقم المستند، الحالة، التواريخ، العملة.
2. **البنود** — بطاقة بعرض كامل: رأس جدول + صفوف؛ زر «إضافة بند» و«+ إضافة بند آخر».
3. **ملاحظات** — بطاقة بعرض كامل ومنطقة نص موسّعة (`rows="10"`، `min-height: 20rem`، خط أوضح).
4. **الإجمالي** — بطاقة بعرض كامل: مجموع فرعي، خصم، (للفاتورة: إجمالي يدوي عند غياب بنود بعناوين)، شريط الإجمالي.
5. **زر حفظ** بعرض كامل أسفل الصفحة (مع الإبقاء على أزرار الشريط العلوي حيث وُجدت).

---

## 5) السياسات والصلاحيات

| النموذج | السياسة | إنشاء/تعديل | حذف |
|---------|---------|-------------|-----|
| `Invoice` | `InvoicePolicy` | محاسب/مدير | مدير |
| `PurchaseOrder` | `PurchaseOrderPolicy` | محاسب/مدير | مدير |

التسجيل في `AppServiceProvider` عبر `Gate::policy` أو ما يعادله حسب إصدار المشروع.

---

## 6) الاختبارات

| الملف | ما يغطيه |
|--------|----------|
| `tests/Feature/SupplierPurchasingScreensTest.php` | فتح فهرس المشتريات، إنشاء PO عبر `PurchaseOrderForm`، رفض `purchase-orders.create` للمشاهد، قبول المحاسب، عرض مستند، دفعات المورد. |

تشغيل جزئي:

```bash
./vendor/bin/pest tests/Feature/SupplierPurchasingScreensTest.php
```

---

## 7) قرار معماري مرتبط

راجع: `docs/decisions/ADR-002-purchase-orders-full-page-forms.md` — لماذا إنشاء/تعديل فواتير المشتريات في صفحات كاملة وليس مودالاً في القائمة.

---

## 8) ملخص مسارات الملفات السريع

```
app/Livewire/InvoiceForm.php
app/Livewire/InvoiceList.php
app/Livewire/PurchaseOrderForm.php
app/Livewire/PurchaseOrderList.php
resources/views/livewire/invoice-form.blade.php
resources/views/livewire/invoice-list.blade.php
resources/views/livewire/purchase-order-form.blade.php
resources/views/livewire/purchase-order-list.blade.php
resources/views/invoices/create.blade.php
resources/views/invoices/edit.blade.php
resources/views/purchase-orders/create.blade.php
resources/views/purchase-orders/edit.blade.php
resources/views/purchase-orders/show.blade.php
routes/web.php
tests/Feature/SupplierPurchasingScreensTest.php
```

---

## 9) تنبيهات تشغيلية

- **استيراد XML:** قد يملأ `purchase_orders` من `LegacyXmlImporter`؛ عند إدخال يدوي تجنّب تكرار `legacy_po_no` مع مستوردات سابقة.
- **حالة الفاتورة في واجهة العميل:** قائمة الفاتورة قد تعرض «ملغاة» مع قيمة `cancelled` في التحقق بينما عمود القاعدة قد يكون `void` — عند مواءمة القاعدة راجع `InvoiceForm` والهجرات.
