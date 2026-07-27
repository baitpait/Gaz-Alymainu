# سجل المشروع — غاز اليمين

<!-- الصيغة الموحّدة عند كل تحديث مهم:

## [YYYY-MM-DD HH:MM] - عنوان المهمة
- **الهدف:** ...
- **التغييرات:** ...
- **الأدوات:** ...
- **تنبيه:** ...
---
-->

## [2026-07-27 18:20] - جلسة واجهة + لوحة + ملف شخصي + فلترة + APK (مكتمل ✅)
- **الهدف:** توثيق كامل لما أُنجز في جلسة التشغيل (دعم، حساب، فلاتر، لوحة غاز، إنتاج، APK).
- **التغييرات:**
  - دعم واتساب أسفل الشريط (`support.png` → `970599814758`).
  - قائمة حساب: ملف شخصي `/profile` + خروج؛ السائق مسموح على `profile`.
  - فلترة `/sales` و`/stock-movements` (تطبيق/مسح).
  - لوحة تحكم تشغيلية عبر `DashboardSummaryService` (بدون اختصارات/تنبيهات بعد المراجعة).
  - مجموعات شريط جانبي قابلة للطي.
  - APK: الاسم «غاز اليمني» + أيقونة الشعار.
  - إنتاج: بريد المدير `gaz@baitpait.com`؛ إصلاح تعيين كلمة المرور عبر Eloquent وليس Query `update`.
  - وثيقة الجلسة: `docs/18_SESSION_UI_DASHBOARD_APK_2026_07_27_AR.md`.
- **الأدوات:** Laravel/Livewire، Capacitor/Gradle، GitHub `Gaz-Alymainu`.
- **تنبيه:** لا تستخدم `User::where()->update(['password'=>…])` — يتجاوز bcrypt. استخدم `$user->password=…; $user->save();`.

---

## [2026-07-27 12:05] - تهيئة نشر gaz.baitpait.space + إعادة تسمية المستودع (مكتمل ✅)
- **الهدف:** تجهيز المشروع للنشر على الدومين الجديد واستبدال كل إشارات prfile/profile القديمة.
- **التغييرات:**
  - الدومين: `https://gaz.baitpait.space`
  - المسار: `/home/sarfesak/public_html/gaz` (Document root: `.../gaz/public`)
  - المستودع: `baitpait/Gaz-Alymainu`
  - تحديث `README.md`، `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md`، الحوادث، `.env.example`، قالب `.env.production`.
  - بذرة المدير الافتراضية: `admin@gaz.local`
- **تنبيه:** على السيرفر اضبط `DB_*` و`APP_KEY` و`GOOGLE_MAPS_API_KEY`، ثم `migrate` + `npm run build` + كاش الإنتاج. Document Root = مجلد `public`.

---

## [2026-05-10] - تهيئة حزمة المشروع والدستور
- **الهدف:** تأسيس مجلد مستقل باسم غاز اليمين مع دستور معاد صياغته وتقارير ووثائق وبرومبت مبرمج.
- **التغييرات:** إضافة `.cursorrules` ومجلد `docs/` و`database/README.md`.
- **الأدوات:** لا شيء (وثائق فقط).
- **تنبيه:** اختيار الباكند/الفرونت مؤجل إلى ADR.

---

## [2026-05-10] - برومبت بداية للمبرمج
- **الهدف:** ملف واحد يلخّص الدردشة والدستور والمكدس لإرساله فوراً للمبرمج.
- **التغييرات:** `docs/KICKOFF_PROMPT_AR.md` + تحديث `README.md`.
- **الأدوات:** لا شيء.
- **تنبيه:** المبرمج ينسخ من داخل الملف بين العلامتين المحددتين.

---

## [2026-05-10] - اعتماد Laravel + اقتراح فرونت (ADR-001)
- **الهدف:** تثبيت الباكند على Laravel وتوثيق توصية الفرونت (Livewire افتراضياً، Inertia بديلاً).
- **التغييرات:** `docs/decisions/ADR-001-backend-laravel-frontend-stack.md`، تحديث `docs/06_RECOMMENDED_LANGUAGES_AR.md` و`.cursorrules` v1.1 و`DEVELOPER_MASTER_PROMPT.md`.
- **الأدوات:** وثائق.
- **تنبيه:** إن اخترتم Inertia كمسار أساسي حدّثوا ADR أو أضيفوا ADR-002.

---

## [2026-05-12] - توثيق وواجهات فواتير العملاء وفواتير المشتريات
- **الهدف:** توثيق مسارات النماذج، تخطيط البطاقات (معلومات → بنود → ملاحظات موسّعة → إجمالي)، وقرار الصفحات الكاملة لفواتير المشتريات مقابل المودال.
- **التغييرات:** `docs/ar_invoices_and_purchase_orders_ui.md`، `docs/decisions/ADR-002-purchase-orders-full-page-forms.md`، وتحديث هذا السجل.
- **الأدوات:** وثائق فقط (لا تغيير على منطق التطبيق ضمن هذه الخطوة).
- **تنبيه:** عند تغيير مسارات `purchase-orders` حافظ على ترتيب `create`/`edit` قبل `{purchaseOrder}`.

---

## [2026-05-10] - قاعدة مُرحَّلة + هوية بصرية + عربي فقط
- **الهدف:** توحيد الأصول داخل `profile-mida`: قاعدة `business_v1.sqlite`، شعار رسمي، دليل هوية عربي، وفرض واجهة عربية فقط.
- **التغييرات:** `database/business_v1.sqlite`، `branding/logo-official.png`، `docs/05_VISUAL_IDENTITY_AR.md`، تحديث `.cursorrules` و`README.md`.
- **الأدوات:** نسخ ملفات.
- **تنبيه:** عيّن قيم HEX النهائية من الشعار عبر مصمم/أداة استخراج لون.

---

## [2026-05-12] - نظرة شاملة على النظام (توثيق)
- **الهدف:** توثيق الحالة الفعلية للتطبيق (مسارات، Livewire، تجميعات لوحة التحكم، سياسات، ترحيل الكتالوج) في ملف مرجعي واحد لتقليل الالتباس بين الفريق.
- **التغييرات:** إضافة `docs/07_SYSTEM_OVERVIEW_AR.md` (يشمل مخطط ERD مبسّط بصيغة Mermaid) وتحديث هذا السجل.
- **الأدوات:** مراجعة `routes/web.php`، `AppServiceProvider`، `dashboard.blade.php`، نماذج المجال.
- **تنبيه:** عند تغيير صلاحيات أو تجميعات مالية حدّث الملف `07` مع الكود في نفس طلب الدمج.

---

## [2026-05-12] - ترحيل ERP القديم + نشر أول مرة على gaz.baitpait.space
- **الهدف:** نشر التطبيق على الإنتاج، استيراد بيانات ERP القديمة (`sarfesak_gazMedia`) إلى مخطط Laravel، وتجهيز ملف SQL جاهز لاستيراد phpMyAdmin.
- **التغييرات:**
  - `app/Console/Commands/ExportLocalDataToMysqlFileCommand.php` (أمر `export:mysql-data` يدعم `--sqlite` و`--output`، يصدّر INSERT فقط بدون سكيما).
  - `app/Services/LegacyErpImport/LegacyErpImportService.php` + `app/Console/Commands/ImportLegacyErpCommand.php` (ترحيل من ERP بـ idempotency عبر `legacy_match_key`, `legacy_invoice_no`, ...).
  - `config/legacy_erp_import.php` و`config/database.php` (اتصال `legacy_erp`).
  - `database/seeders/DemoDataSeeder.php` لبيانات تجريبية اختيارية (`SEED_DEMO_DATA=true`).
  - `database/backups/` لنسخ SQL و SQLite (مستثناة من Git).
  - `docs/DATABASE_BACKUP_AND_RESTORE_AR.md` و`docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md`.
  - إعادة تسمية هجرة `purchase_orders` إلى `094927` لتفادي خطأ FK في MySQL.
- **الأدوات:** Laravel artisan، MySQL/MariaDB، phpMyAdmin، Git/GitHub (`baitpait/Gaz-Alymainu`).
- **تنبيه:** عند `migrate:fresh` على بيئة فيها بيانات، خذ نسخة احتياطية أولاً. ملف ERP الخام لا يُستورد داخل قاعدة Laravel — يبقى في قاعدة منفصلة ويُرحَّل عبر `legacy-erp:import`.

---

## [2026-05-13] - ربط APP_NAME بالقوالب
- **الهدف:** جعل اسم التطبيق في الشريط العلوي + عنوان النافذة + صفحة الدخول قابلاً للتغيير من `.env` بدل النص الثابت.
- **التغييرات:** `resources/views/components/layouts/app.blade.php` و`resources/views/auth/login.blade.php` يقرآن `config('app.name', 'غاز اليمين')`.
- **الأدوات:** Blade.
- **تنبيه:** أي قالب جديد يجب أن يستخدم `config('app.name')` لا نصاً ثابتاً. بعد تغيير `APP_NAME` في الإنتاج: `php artisan config:clear && config:cache && view:clear && view:cache`.

---

## [2026-05-25] - تسويات الذمة + كشف حساب مبسّط + بحث عميل في الفاتورة
- **الهدف:** تسجيل خصم/إعفاء على ذمة العميل/المورد **دون** تعديل الفواتير؛ تبسيط كشف الحساب (ملخص + مبالغ موقّعة)؛ بحث عميل في نماذج الفاتورة.
- **التغييرات:**
  - جداول `client_balance_adjustments`، `supplier_balance_adjustments` + Livewire (قائمة/نموذج) + مسارات + قائمة جانبية.
  - تحديث `ClientStatementService` / `SupplierStatementService` (معادلة: مستندات − دفعات − تسويات).
  - Trait `FiltersClientsForSelect` في الفواتير والدفعات.
  - إصلاح أسماء فهارس MySQL (`cba_client_cur_date_idx`) بعد فشل `migrate` على الإنتاج.
  - توثيق: `docs/09_BALANCE_ADJUSTMENTS_AND_STATEMENTS_AR.md` + تحديث `03`، `04`، `07`، `08`.
- **الأدوات:** Laravel migrations، Livewire، PHPUnit.
- **تنبيه:** التسوية **ليست** دفعة نقدية — لا تدخل صناديق التحصيل. بعد النشر: `git pull && php artisan migrate --force && php artisan optimize:clear`.

---

## [2026-05-25] - بحث العملاء/الموردين + إصلاح UTF-8 BOM في supplier-list
- **الهدف:** بحث مباشر بالاسم في قوائم الأطراف؛ إصلاح تعطّل البحث في الموردين.
- **التغييرات:**
  - استبدال فلاتر «تطبيق» بـ `ListsPartyDirectory` + `party-name-search.blade.php` (`wire:model.live.debounce.300ms`).
  - إزالة **UTF-8 BOM** (`EF BB BF`) من `supplier-list.blade.php` — كان يكسر جذر Livewire (`inputInWireRoot: false`).
  - حذف `FiltersPartyDirectory`، `UsesCommittedPartyDirectoryFilters`، `party-directory-filters.blade.php`.
  - اختبار: `PartyDirectoryListTest` (5 tests).
  - توثيق: `docs/troubleshooting/INCIDENT-001-supplier-list-utf8-bom-livewire.md`.
- **Commit:** `d0260ae`.
- **تنبيه:** احفظ Blade بـ UTF-8 **بدون BOM**. بعد النشر: `git pull && php artisan view:clear && php artisan view:cache`.

---

## [2026-06-29] - تطبيع طرق الدفع + إصلاح عرض القوائم المنسدلة
- **الهدف:** إصلاح فشل تعديل دفعات قديمة (`طريقة الدفع invalid`)؛ إصلاح `<select>` الأبيض على الإنتاج في Dark Mode.
- **التغييرات:**
  - `App\Services\Finance\PaymentMethod` + تطبيع في `SupplierPaymentForm` / `PaymentForm`.
  - أمر `php artisan payments:normalize-methods`.
  - CSS: `color-scheme: light` على `select.input`.
  - توثيق: `docs/12_PAYMENT_METHODS_AND_LEGACY_NORMALIZATION_AR.md`، `INCIDENT-002`، `INCIDENT-003`.
- **Commits:** `2d18e7c`, `50ceee1`.
- **تنبيه:** بعد النشر: `git pull && php artisan payments:normalize-methods && npm run build && php artisan view:cache`.

---

## [2026-05-25] - PDF مطابق للطباعة 100% (Browsershot)
- **الهدف:** إلغاء الفجوة بين معاينة الطباعة وملف PDF (كانت قوالب mPDF منفصلة بخط وتخطيط مختلفين).
- **التغييرات:**
  - `spatie/browsershot` + `puppeteer` (dev) + `PrintViewPdfRenderer` (نفس Blade + `emulateMedia('print')`).
  - تحديث controllers PDF الأربعة لاستخدام قوالب الطباعة.
  - مكوّن `<x-print-page-actions>` (طباعة + PDF) في صفحات الطباعة.
  - `config/browsershot.php` + متغيرات `.env.example`.
  - اختبارات `DocumentPdfTest` (تتخطى تلقائياً إن لم يتوفر Chrome).
  - توثيق: `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md` §11.
- **الأدوات:** Browsershot، Puppeteer، Headless Chrome.
- **تنبيه:** على الإنتاج: `npm ci` (ليس `npm install --production` فقط) + `BROWSERSHOT_NO_SANDBOX=true` + `config:cache`.

---

## [2026-07-01] - نشر PDF على الإنتاج + إصلاحات النشر (مكتمل ✅)
- **الهدف:** تفعيل PDF المطابق للطباعة على `gaz.baitpait.space` وإغلاق حوادث النشر.
- **ما تم إنجازه:**

### ميزات PDF والواجهة
- PDF من **نفس قالب الطباعة** (Browsershot + `emulateMedia('print')`) للفواتير، أوامر الشراء، سندات العملاء والموردين.
- أزرار **طباعة + PDF** في قوائم المستندات وصفحات الطباعة (`document-export-buttons`، `print-page-actions`).
- إصلاح تراكب أزرار الطباعة/PDF (`position: fixed` مكرر).
- إصلاح deadlock PDF محلياً: تضمين الشعار `base64` في HTML (لا طلب HTTP لنفس `artisan serve`).

### نشر الإنتاج (`gaz.baitpait.space`)
- `git pull` + `composer install` + `npm ci` + `npm run browsershot:install` + `npm run build`.
- متغيرات `.env`: `BROWSERSHOT_NODE`, `PUPPETEER_CACHE_DIR`, `BROWSERSHOT_NO_SANDBOX=true`.
- تثبيت مكتبات Chromium على **Ubuntu 24.04** (حزم `*t64`: `libatk1.0-0t64`, `libasound2t64`, …).
- Puppeteer **23** (متوافق Node 20 على السيرفر).
- `php artisan browsershot:check` → **Test PDF generated successfully**.

### حوادث مُغلقة
| # | العرض | الحل |
|---|--------|------|
| — | `Route [invoices.pdf] not defined` | `php artisan route:cache` بعد `git pull` |
| — | PDF 500 — مكتبات Chrome ناقصة | `apt-get install` حزم t64 + `browsershot:install` |
| INCIDENT-004 | `tempnam()` 500 على `/invoices` | `chown baitpait` + `config/view.php` + `App\Filesystem\Filesystem` |
| INCIDENT-005 | `updatedLines` + APP_DEBUG على تعديل الفاتورة | `44be136`, `APP_DEBUG=false` |

### أوامر تشخيص جديدة
- `php artisan browsershot:check`
- `php artisan storage:doctor`

### Commits
- `a435cd5` — PDF Browsershot
- `0f09fa6` — تشديد Linux + browsershot:check
- `c298f37` — view config + storage:doctor
- `76073fb` — tempnam PHP 8.4
- `6af30ed` — توثيق PDF + INCIDENT-004
- `44be136` — updatedLines nullable + APP_DEBUG

### توثيق
- `docs/13_DOCUMENT_PDF_BROWSERSHOT_AR.md` (دليل شامل)
- `docs/troubleshooting/INCIDENT-004-tempnam-storage-ownership-php84.md`
- `docs/troubleshooting/INCIDENT-005-invoice-edit-updatedlines-app-debug.md`
- تحديث `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md` §11 و§8

- **تنبيه:** بعد كل نشر كـ root: `chown -R sarfesak:sarfesak storage bootstrap/cache`. لا تفترض أن `webuzo` = مستخدم الموقع.

---

## [2026-07-04] - إصلاح تعديل الفاتورة: updatedLines + APP_DEBUG (مكتمل ✅)
- **الهدف:** إغلاق خطأ 500 على `/invoices/{id}/edit` — ظهور كود PHP للزبون (تقرير فاتورة #758).
- **السبب:**
  - Livewire يمرّر `$key = null` عند مزامنة مصفوفة `$lines` كاملة (مثلاً `addLine()`).
  - `updatedLines(string $key)` → **TypeError** في `InvoiceForm.php:131`.
  - **`APP_DEBUG=true`** على الإنتاج يعرض مقتطف الكود للزبون بدل رسالة عامة.
- **التغييرات:**
  - `?string $key = null` + early return في `InvoiceForm`, `PurchaseOrderForm`, `InvoiceList`.
  - اختبار: `invoice form add line survives whole lines array sync`.
  - توثيق: `INCIDENT-005`, تحديث `docs/08` §8.
- **نشر الإنتاج:**
  - `git pull` → `44be136`
  - `APP_DEBUG=false` + `php artisan config:cache`
  - `chown -R sarfesak:sarfesak storage bootstrap/cache`
- **Commit:** `44be136`
- **تنبيه:** **دائماً** `APP_DEBUG=false` على الإنتاج. أي `updated{ArrayProperty}` في Livewire يجب أن يقبل `$key` nullable.

---

## [2026-07-20 18:19] - نظام السائقين والمخزون ونقطة البيع (Phase 1 + Phase 2) (مكتمل ✅)
- **الهدف:** إعادة توجيه المشروع إلى «غاز اليمين» — توزيع أسطوانات الغاز عبر سائقين بسيارات كمخازن متنقلة، تحميل من المخازن الثابتة، تسعير يومي مرن، مبيعات نقدية/على الحساب، تحصيل، سحب كاش، ومصروفات سائق.
- **التغييرات:**
  - **Phase 1 (مخازن/تحميل/تسعير):** جداول `warehouses`, `warehouse_product`, `stock_balances`, `stock_movements`, `product_daily_prices` + دور `driver` + حقول الغاز على `products`. Enums `WarehouseType`, `StockMovementType`. خدمات `InventoryService` (transfer/saleOut داخل transaction) و`DailyPriceService`.
  - **Phase 2 (بيع/مالية):** جداول `sales`, `cash_handovers` (+method/cheque_number), `collections`, `driver_expenses`. Enums `SalePaymentType`, `CollectionMethod`, `DriverExpenseCategory`. خدمات `SalesService` (سعر قابل للتعديل) و`CashBoxService` (رصيد نقدي/شيكات، سحب كاش، مصروفات).
  - **الواجهة:** POS موبايل أولاً (دوائر إحصائية بألوان الهوية، سعر قابل للتعديل، عدّاد `+/−`، تصفير الكمية بعد البيع)، صفحات مستقلة للتحصيل والمصروفات، **شريط تنقّل سفلي** (المبيعات/التحصيل/المصروفات)، حصر السائق في صفحات البيع فقط (بلا قائمة جانبية).
  - **الصلاحيات/الوسائط:** Gates (`record-sales`, `manage-cash-handover`, `manage-driver-expenses`, `manage-drivers`, `manage-inventory`, `manage-daily-prices`)؛ Middleware `RestrictDriverToSales` (`driver.sales`) و`BlockSalesModule` (`block.sales`).
  - **التقارير:** 6 تقارير غاز (مبيعات/تحصيلات/صندوق السائق/أرصدة/حركات/أداء) بفلترة تواريخ وتصدير CSV؛ إعادة بناء `/financial-summary` للتدفّق النقدي الفعلي.
  - **العلامة التجارية:** إزالة كل بقايا «إنتاج إعلامي وتقارير تشغيلية» وإعادة التسمية إلى «غاز اليمين» + الشعار.
  - **توثيق:** `docs/14_GAS_DRIVER_INVENTORY_SYSTEM_AR.md`.
- **الأدوات:** Laravel 12، Livewire 3، Alpine.js، Tailwind، MySQL، Vite.
- **تنبيه:** أي أصناف Tailwind جديدة (قيم تعسّفية / تموضع `fixed`) تتطلّب `npm run build && php artisan view:cache` وإلا لا تظهر (حادثة الشريط السفلي المخفي). بيانات المخزون التجريبية تُنظّف قبل الإنتاج، وكلمة مرور المدير الافتراضية `password` تُغيَّر قبل النشر.

---

## [2026-07-27 11:19] - نمط الأجر اليومي للموظفين (مكتمل ✅)
- **الهدف:** دعم موظف على «يومية» — أجرة يوم ثابتة، وآخر الشهر يُدخل عدد أيام العمل فيُحسب الأساسي = أجرة اليوم × الأيام.
- **التغييرات:**
  - `Employee`: ثابت جديد `PAY_FREQUENCY_DAILY = 'daily'` بعنوان «يومية» + `isDailyWage()`.
  - ترحيل `2026_07_27_100001_add_daily_wage_fields_to_salary_payments`: عمودان `worked_days` (tinyint) و`daily_rate` (decimal 15,4) — nullable (توافق خلفي كامل).
  - `SalaryPayment`: إضافة الحقلين للـ`fillable`/`casts`.
  - `SalaryPaymentForm`: كشف الموظف اليومي، إظهار حقلي أجرة اليوم/عدد الأيام، حساب الأساسي **مقفلاً** (`recomputeDailyBase`)، وتحقّق مشروط (`worked_days` مطلوب 0–31 لليومي).
  - العرض: عمود «أيام العمل × أجرة اليوم» في تقرير الرواتب، ملف الموظف، CSV، و PDF.
  - توثيق: تحديث `docs/10_EMPLOYEES_AND_PAYROLL_AR.md`.
- **قرارات المستخدم:** أجرة اليوم = نفس حقل الراتب الأساسي (لا حقل جديد على الموظف)؛ أيام صحيحة فقط؛ الأساسي مقفل؛ إظهار الأعمدة في التقارير.
- **الأدوات:** Laravel migrations، Livewire، Blade.
- **تنبيه:** الترحيل يضيف أعمدة فقط (آمن) — `php artisan migrate` كافٍ، لا حاجة لإعادة بناء الأصول (لا أصناف Tailwind جديدة). `daily_rate` لقطة تاريخية لا تتأثر بتغيير أجرة الموظف لاحقاً.

---

## [2026-07-27 11:45] - تتبّع مواقع السائقين على الخريطة (مرحلة 1 ويب) (مكتمل ✅)
- **الهدف:** عرض مواقع السائقين للإدارة على خريطة Google أثناء الوردية، بدون APK.
- **التغييرات:**
  - جدول `driver_locations` + نموذج `DriverLocation` + خدمة `DriverLocationService`.
  - صفحة السائق `/my-location` (بدء/إيقاف مشاركة + إرسال GPS كل ~30ث عبر Geolocation API).
  - صفحة الإدارة `/drivers/map` (Google Maps + قائمة السائقين + تحديث كل 10ث).
  - Gates: `share-location`, `view-driver-locations`؛ السماح بـ `location.share` في middleware السائق؛ تبويب «موقعي» في الشريط السفلي؛ رابط «خريطة السائقين» في القائمة وصفحة السائقين.
  - إعداد: `GOOGLE_MAPS_API_KEY` في `.env` عبر `config/services.php`.
  - توثيق: `docs/15_DRIVER_LOCATION_TRACKING_AR.md`.
- **الأدوات:** Laravel, Livewire 3, Alpine.js, Google Maps JavaScript API.
- **تنبيه:** قيّد مفتاح Google بنطاقات HTTP فوراً (المفتاح ظهر في المحادثة). المرحلة 1 تتطلب بقاء صفحة السائق مفتوحة. APK/مسار تاريخي مؤجّل.

---
