# سجل المشروع — بروفايل ميدا

<!-- الصيغة الموحّدة عند كل تحديث مهم:

## [YYYY-MM-DD HH:MM] - عنوان المهمة
- **الهدف:** ...
- **التغييرات:** ...
- **الأدوات:** ...
- **تنبيه:** ...
---
-->

## [2026-05-10] - تهيئة حزمة المشروع والدستور
- **الهدف:** تأسيس مجلد مستقل باسم بروفايل ميدا مع دستور معاد صياغته وتقارير ووثائق وبرومبت مبرمج.
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

## [2026-05-12] - ترحيل ERP القديم + نشر أول مرة على profile.baitpait.com
- **الهدف:** نشر التطبيق على الإنتاج، استيراد بيانات ERP القديمة (`baitpait_profileMedia`) إلى مخطط Laravel، وتجهيز ملف SQL جاهز لاستيراد phpMyAdmin.
- **التغييرات:**
  - `app/Console/Commands/ExportLocalDataToMysqlFileCommand.php` (أمر `export:mysql-data` يدعم `--sqlite` و`--output`، يصدّر INSERT فقط بدون سكيما).
  - `app/Services/LegacyErpImport/LegacyErpImportService.php` + `app/Console/Commands/ImportLegacyErpCommand.php` (ترحيل من ERP بـ idempotency عبر `legacy_match_key`, `legacy_invoice_no`, ...).
  - `config/legacy_erp_import.php` و`config/database.php` (اتصال `legacy_erp`).
  - `database/seeders/DemoDataSeeder.php` لبيانات تجريبية اختيارية (`SEED_DEMO_DATA=true`).
  - `database/backups/` لنسخ SQL و SQLite (مستثناة من Git).
  - `docs/DATABASE_BACKUP_AND_RESTORE_AR.md` و`docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md`.
  - إعادة تسمية هجرة `purchase_orders` إلى `094927` لتفادي خطأ FK في MySQL.
- **الأدوات:** Laravel artisan، MySQL/MariaDB، phpMyAdmin، Git/GitHub (`baitpait/prfile`).
- **تنبيه:** عند `migrate:fresh` على بيئة فيها بيانات، خذ نسخة احتياطية أولاً. ملف ERP الخام لا يُستورد داخل قاعدة Laravel — يبقى في قاعدة منفصلة ويُرحَّل عبر `legacy-erp:import`.

---

## [2026-05-13] - ربط APP_NAME بالقوالب
- **الهدف:** جعل اسم التطبيق في الشريط العلوي + عنوان النافذة + صفحة الدخول قابلاً للتغيير من `.env` بدل النص الثابت.
- **التغييرات:** `resources/views/components/layouts/app.blade.php` و`resources/views/auth/login.blade.php` يقرآن `config('app.name', 'بروفايل ميديا')`.
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
- **الهدف:** تفعيل PDF المطابق للطباعة على `profile.baitpait.com` وإغلاق حوادث النشر.
- **ما تم إنجازه:**

### ميزات PDF والواجهة
- PDF من **نفس قالب الطباعة** (Browsershot + `emulateMedia('print')`) للفواتير، أوامر الشراء، سندات العملاء والموردين.
- أزرار **طباعة + PDF** في قوائم المستندات وصفحات الطباعة (`document-export-buttons`، `print-page-actions`).
- إصلاح تراكب أزرار الطباعة/PDF (`position: fixed` مكرر).
- إصلاح deadlock PDF محلياً: تضمين الشعار `base64` في HTML (لا طلب HTTP لنفس `artisan serve`).

### نشر الإنتاج (`profile.baitpait.com`)
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
| INCIDENT-004 | `tempnam()` 500 على `/invoices` | `chown -R baitpait:baitpait storage bootstrap/cache` (ليس webuzo) + `config/view.php` + `App\Filesystem\Filesystem` |

### أوامر تشخيص جديدة
- `php artisan browsershot:check`
- `php artisan storage:doctor`

### Commits
- `a435cd5` — PDF Browsershot
- `0f09fa6` — تشديد Linux + browsershot:check
- `c298f37` — view config + storage:doctor
- `76073fb` — tempnam PHP 8.4

### توثيق
- `docs/13_DOCUMENT_PDF_BROWSERSHOT_AR.md` (دليل شامل)
- `docs/troubleshooting/INCIDENT-004-tempnam-storage-ownership-php84.md`
- تحديث `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md` §11 و§8

- **تنبيه:** بعد كل نشر كـ root: `chown -R baitpait:baitpait storage bootstrap/cache`. لا تفترض أن `webuzo` = مستخدم الموقع.

---

## [2026-07-04] - إصلاح تعديل الفاتورة: updatedLines + APP_DEBUG
- **الهدف:** إغلاق خطأ 500 على `/invoices/{id}/edit` — ظهور كود PHP للزبون عند تعديل البنود.
- **السبب:** Livewire يمرّر `$key = null` عند تحديث مصفوفة `$lines` كاملة؛ `updatedLines(string $key)` → TypeError. `APP_DEBUG=true` على الإنتاج يعرض مقتطف الكود.
- **التغييرات:** `?string $key` + early return في `InvoiceForm`, `PurchaseOrderForm`, `InvoiceList`. اختبار `addLine`. تحديث `docs/08`.
- **Commit:** (pending)
- **تنبيه:** على السيرفر: `APP_DEBUG=false` في `.env` + `git pull` + `php artisan config:cache`.

---
