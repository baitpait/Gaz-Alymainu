# غاز اليمين — Gaz Alymainu

منصة تقارير وواجهات شاملة لإدارة الأعمال الإعلامية (عملاء، موردون، فواتير، دفعات، مصروفات).

---

## المكدس

| الطبقة | التقنية |
|--------|---------|
| Backend | Laravel 12 / PHP 8.2 |
| Frontend | Blade + Livewire 3 + Alpine.js + Tailwind CSS v4 |
| قاعدة البيانات | MySQL (محلي + إنتاج) — SQLite اختياري للتطوير فقط |
| PDF | barryvdh/laravel-dompdf |
| اختبارات | Pest v3 |

---

## التشغيل المحلي

### المتطلبات
- PHP 8.2+
- Composer 2+
- Node.js 18+

### الخطوات

```bash
# 1. نسخ ملف البيئة
cp .env.example .env

# 2. توليد مفتاح التطبيق
php artisan key:generate

# 3. تثبيت تبعيات PHP
composer install

# 4. تثبيت تبعيات Node
npm install

# 5. تشغيل المايغريشن (MySQL — أنشئ القاعدة أولاً وطابق DB_* في .env)
php artisan migrate

# 6. بناء الأصول
npm run build

# 7. تشغيل الخادم
php artisan serve
```

الموقع: http://localhost:8000

---

## نشر الإنتاج — `gaz.baitpait.space`

| البند | المسار أو القيمة |
|--------|------------------|
| الدومين | `https://gaz.baitpait.space` |
| مستخدم نظام التشغيل | `sarfesak` |
| جذر الويب (Document root) | `/home/sarfesak/public_html/gaz/public` |
| جذر Laravel (`artisan` + `composer.json`) | `/home/sarfesak/public_html/gaz` |
| مستودع GitHub | [baitpait/Gaz-Alymainu](https://github.com/baitpait/Gaz-Alymainu) |

**مهم:** أوامر الشل (`php artisan`، `composer install`) تُنفَّذ من جذر Laravel (أعلى من `public`)، وليس من داخل `public` فقط. إذا لم يوجد `artisan` تحت `gaz` فالنسخة ناقصة — ابحث بـ `find /home/sarfesak/public_html -name artisan`.

التفاصيل الكاملة: `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md`.

---

## بيانات تجريبية جاهزة

بعد `php artisan migrate` يمكنك إحدى الطريقتين:

```bash
# 1) مستخدم مدير من .env + بيانات تجريبية (محلي أو بيئة تجارب)
SEED_DEV_ADMIN=true SEED_DEMO_DATA=true php artisan db:seed
```

```bash
# 2) بيانات تجريبية فقط (ينشئ مستخدم demo@baitpait.local / كلمة المرور: password)
php artisan db:seed --class=DemoDataSeeder
```

لا تشغّل البذور على إنتاج حقيقي دون قصد.

---

### تصدير بيانات محلية لاستيرادها يدوياً على MySQL (سطح المكتب)

```bash
php artisan export:mysql-data
```

يُنشئ ملفاً على سطح المكتب: **INSERT فقط** (بدون CREATE TABLE) — مناسب لقاعدة فيها الجداول بعد `migrate`.

- مسار مخصص: `php artisan export:mysql-data --output=/path/to/file.sql`
- **نسخة قديمة من `database.sqlite`:**  
  `php artisan export:mysql-data --sqlite=/المسار/الكامل/database.sqlite --output=/path/to/import.sql`

**نظام السائقين والمخزون ونقطة البيع:** `docs/14_GAS_DRIVER_INVENTORY_SYSTEM_AR.md`  
**تتبّع مواقع السائقين (خريطة):** `docs/15_DRIVER_LOCATION_TRACKING_AR.md`  
**نشر gaz.baitpait.space (أوامر سريعة):** `docs/16_SERVER_DEPLOY_GAZ_BAITPAIT_SPACE_AR.md`  
**تطبيق السائق APK:** `docs/17_DRIVER_APK_AR.md`  
**جلسة واجهة/لوحة/APK (2026-07-27):** `docs/18_SESSION_UI_DASHBOARD_APK_2026_07_27_AR.md`  
**سجل المشروع:** `docs/PROJECT_LOG.md`
**النسخ الاحتياطي والاسترجاع:** `docs/DATABASE_BACKUP_AND_RESTORE_AR.md`  
**النشر والتشغيل (إنتاج):** `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md`  
**PDF المستندات (Browsershot):** `docs/13_DOCUMENT_PDF_BROWSERSHOT_AR.md`  
**طرق الدفع وتطبيع البيانات القديمة:** `docs/12_PAYMENT_METHODS_AND_LEGACY_NORMALIZATION_AR.md`  
**استكشاف أخطاء الإنتاج:** `docs/troubleshooting/` (INCIDENT-001 … INCIDENT-005)

---

## الاختبارات

```bash
php vendor/bin/pest
```

---

## هيكل المجلدات الرئيسية

```
app/
├── Http/Controllers/       ← Controllers
├── Livewire/               ← مكونات Livewire
├── Models/                 ← Eloquent Models (SoftDeletes)
├── Policies/               ← سياسات الصلاحيات
└── Services/               ← منطق الأعمال (ClientStatementService)

database/
├── migrations/             ← مايغريشن كاملة من المواصفة
├── factories/              ← Factories للاختبارات
├── business_v1.sqlite      ← بيانات مرجعية من النظام القديم
└── reference_sqlite_v1_schema.sql

resources/views/
├── layouts/app.blade.php   ← التخطيط الرئيسي (RTL + عربي)
├── auth/                   ← صفحات المصادقة
├── livewire/               ← قوالب Livewire
│   └── client-statement.blade.php
└── pdf/                    ← قوالب PDF
    └── client-statement.blade.php

docs/                       ← دستور المشروع والوثائق
branding/                   ← الشعار والهوية البصرية
```

**مرجع واجهات الفواتير (عملاء + مشتريات):** `docs/ar_invoices_and_purchase_orders_ui.md`  
**تسويات الذمة وكشف الحساب:** `docs/09_BALANCE_ADJUSTMENTS_AND_STATEMENTS_AR.md`

---

## الأدوار والصلاحيات

| الدور | عرض | إنشاء/تعديل | حذف |
|-------|-----|------------|-----|
| viewer | ✅ | ❌ | ❌ |
| accountant | ✅ | ✅ | ❌ |
| manager | ✅ | ✅ | ✅ |

---

## السياسات الحرجة

- **العملات:** لا يُجمع ILS + USD في رقم واحد — كل عملة قسم مستقل في الكشف.
- **الدفعات:** مرتبطة بالعميل مباشرة، لا بفاتورة بعينها.
- **الحذف:** soft delete لجميع البيانات الحرجة.
- **التوقيت:** UTC في التخزين، تحويل محلي في طبقة العرض فقط.

---

## أسئلة مفتوحة (قبل Sprint 1)

- [ ] سياسة دفعة بعملة مختلفة عن فاتورة العميل (مسموح / يدوي فقط؟)
- [ ] بيئة الاستضافة المستهدفة (VPS / Cloud)
- [ ] تأكيد ألوان HEX الدقيقة من الشعار

---

## Git Workflow

المستودع على GitHub: [baitpait/Gaz-Alymainu](https://github.com/baitpait/Gaz-Alymainu)

```
main          ← الفرع الرئيسي (محمي)
develop       ← التطوير
feature/*     ← ميزات
fix/*         ← إصلاحات
```

**Conventional Commits:** `feat:`, `fix:`, `chore:`, `test:`, `docs:`
**Remote الإنتاج:** `alymainu` / `origin` → `https://github.com/baitpait/Gaz-Alymainu.git`
