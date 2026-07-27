# PDF المستندات — Browsershot (تطابق 100% مع الطباعة)

> **الحالة:** مُفعَّل على الإنتاج (`gaz.baitpait.space`) — يولّد PDF من **نفس قالب Blade** المستخدم في معاينة الطباعة.

---

## 1) الهدف التجاري

المستخدم يرى في **طباعة** و**PDF** نفس التنسيق (خط Cairo، ألوان الهوية، تخطيط A4) دون صيانة قالبين منفصلين.

| قبل | بعد |
|-----|-----|
| قالب طباعة (Cairo + Flexbox) + قالب mPDF منفصل | قالب واحد → Headless Chrome مع `emulateMedia('print')` |

---

## 2) المستندات المشمولة

| المستند | مسار الطباعة | مسار PDF | اسم المسار |
|---------|-------------|----------|------------|
| فاتورة عميل | `/invoices/{id}/print` | `/invoices/{id}/pdf` | `invoices.print` / `invoices.pdf` |
| أمر شراء | `/purchase-orders/{id}/print` | `/purchase-orders/{id}/pdf` | `purchase-orders.print` / `purchase-orders.pdf` |
| سند دفع عميل | `/payments/{id}/print` | `/payments/{id}/pdf` | `payments.print` / `payments.pdf` |
| سند دفع مورد | `/supplier-payments/{id}/print` | `/supplier-payments/{id}/pdf` | `supplier-payments.print` / `supplier-payments.pdf` |

**الواجهة:**
- عمود **إجراءات** في القوائم: `<x-document-export-buttons>` (طباعة + PDF).
- صفحة الطباعة: `<x-print-page-actions>` (زرّا طباعة وPDF جنب بعض، يُخفيان عند الطباعة وداخل PDF).

---

## 3) البنية التقنية

```
Controller (InvoicePdfController, …)
    → PrintViewPdfRenderer::streamFromView()
        → view('invoices.print', …, exportMode => 'pdf')
        → inlineLocalPublicAssets()  # شعار base64 — يمنع deadlock مع php artisan serve
        → Browsershot::html() + emulateMedia('print') + Headless Chrome
```

| ملف | الدور |
|-----|------|
| `app/Services/Documents/PrintViewPdfRenderer.php` | توليد PDF |
| `app/Services/Documents/InvoiceDocumentService.php` | بيانات فاتورة + `pdfUrl` |
| `app/Services/Documents/PurchaseOrderDocumentService.php` | بيانات أمر شراء |
| `app/Services/Documents/PaymentVoucherService.php` | بيانات سند دفع |
| `config/browsershot.php` | Node، Chrome، cache، sandbox |
| `config/view.php` | مسار compiled views (بدون `realpath`) |

**اعتماديات:** `spatie/browsershot` (Composer)، `puppeteer@23` (npm — متوافق Node 20).

**أوامر تشخيص:**
- `php artisan browsershot:check` — يختبر Node + Chrome + PDF تجريبي.
- `php artisan storage:doctor` — يختبر صلاحيات `storage/framework/views`.

---

## 4) متغيرات `.env` (الإنتاج)

```env
BROWSERSHOT_NODE=/usr/bin/node
PUPPETEER_CACHE_DIR=/home/sarfesak/public_html/gaz/storage/app/puppeteer-cache
BROWSERSHOT_NO_SANDBOX=true
```

اختياري:

```env
BROWSERSHOT_TEMP_PATH=/home/sarfesak/public_html/gaz/storage/app/browsershot-tmp
BROWSERSHOT_PDF_DELAY_MS=1500
BROWSERSHOT_PHP_TIMEOUT=120
```

بعد أي تعديل: `php artisan config:cache`.

---

## 5) نشر كامل على السيرفر (مرجع سريع)

```bash
cd /home/sarfesak/public_html/gaz
git pull origin main
composer install --no-dev --optimize-autoloader

mkdir -p storage/app/puppeteer-cache
export PUPPETEER_CACHE_DIR=/home/sarfesak/public_html/gaz/storage/app/puppeteer-cache
npm ci
npm run browsershot:install
npm run build

chown -R sarfesak:sarfesak storage bootstrap/cache node_modules
chmod -R ug+rwx storage bootstrap/cache

php artisan route:cache
php artisan config:cache
php artisan browsershot:check
php artisan storage:doctor
```

> **ملكية الملفات:** على Webuzo مالك الموقع **`baitpait`** وليس `webuzo`. راجع `INCIDENT-004`.

تفاصيل Chromium على Ubuntu 24.04: `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md` §11.

---

## 6) إصلاحات معروفة أثناء التطوير

| المشكلة | السبب | الحل |
|---------|--------|------|
| PDF لا يُحمّل محلياً (30 ثانية) | deadlock: Chrome يطلب الشعار من نفس `artisan serve` | تضمين الشعار base64 في `PrintViewPdfRenderer` |
| `Route [invoices.pdf] not defined` | كاش مسارات قديم | `php artisan route:clear && route:cache` |
| PDF 500 على الإنتاج | Chromium أو مكتبات Linux ناقصة | `npm run browsershot:install` + حزم `t64` على Ubuntu 24.04 |
| أزرار طباعة/PDF فوق بعض | `position: fixed` مكرر في القوالب | موحّد في `<x-print-page-actions>` |
| 500 `tempnam()` على `/invoices` | ملكية `storage` لـ root/webuzo بدلمستخدم نظام التشغيل | `sarfesak` | `chown baitpait` + `config/view.php` + `App\Filesystem\Filesystem` |

---

## 7) اختبارات

```bash
php artisan test tests/Feature/DocumentPdfTest.php
```

تتخطى تلقائياً إن لم يتوفر Chrome (مثلاً CI بدون Puppeteer).

---

## 8) قوالب قديمة (mPDF)

`resources/views/pdf/documents/` — لم تعد تُستدعى من الـ controllers بعد اعتماد Browsershot. يمكن حذفها لاحقاً بعد تأكيد الإنتاج.

---

## 9) Commits مرجعية

| Commit | الوصف |
|--------|--------|
| `a435cd5` | PDF من قالب الطباعة عبر Browsershot |
| `0f09fa6` | تشديد إنتاج Linux + `browsershot:check` |
| `c298f37` | `config/view.php` + `storage:doctor` |
| `76073fb` | إصلاح `tempnam` PHP 8.4 + `App\Filesystem\Filesystem` |

---

## 10) مراجع

- `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md` — §11 نشر PDF
- `docs/troubleshooting/INCIDENT-004-tempnam-storage-ownership-php84.md` — RCA صلاحيات storage
- `docs/PROJECT_LOG.md` — سجل الجلسة الكامل
