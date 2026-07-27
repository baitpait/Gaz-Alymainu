# دليل النشر والتشغيل — غاز اليمين

> هذا الملف يجمع كل ما يخص نشر التطبيق على الإنتاج (gaz.baitpait.space)، الاستيراد من ERP القديم، النسخ الاحتياطي، استكشاف الأخطاء الشائعة، وإدارة Git على السيرفر.

---

## 1) معلومات الاستضافة والدومين

| البند | القيمة |
|-------|--------|
| الدومين | `https://gaz.baitpait.space` |
| مستخدم نظام التشغيل | `sarfesak` |
| **جذر الويب (Document root)** | `/home/sarfesak/public_html/gaz/public` |
| **جذر Laravel (artisan + composer)** | `/home/sarfesak/public_html/gaz` |
| قاعدة بيانات Laravel | `sarfesak_gaz` (MySQL/MariaDB) — عدّل الاسم من لوحة الاستضافة إن اختلف |
| مستودع GitHub | [`baitpait/Gaz-Alymainu`](https://github.com/baitpait/Gaz-Alymainu) |

> ملاحظة: قاعدة ERP القديمة (`legacy-erp:import`) اختيارية وغير مطلوبة لنشر غاز اليمين النظيف.

---

## 2) أول نشر (Initial Deployment)

```bash
cd /home/sarfesak/public_html
# إن وُجد مجلد gaz قديم احتفظ بنسخة:
# mv gaz "gaz_backup_$(date +%Y%m%d_%H%M)"

git clone https://github.com/baitpait/Gaz-Alymainu.git gaz
cd /home/sarfesak/public_html/gaz

# 1) تبعيات PHP (إنتاج)
composer install --no-dev --optimize-autoloader

# 2) ملف البيئة
cp .env.example .env
nano .env   # اضبط APP_URL=https://gaz.baitpait.space و APP_ENV=production و APP_DEBUG=false و DB_*

# 3) مفتاح التطبيق
php artisan key:generate --force

# 4) الجداول
php artisan migrate --force

# 5) رابط التخزين
php artisan storage:link

# 6) أصول الواجهة (يحتاج Node على السيرفر)
npm ci
npm run build

# 7) كاش الإنتاج
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8) صلاحيات
chown -R sarfesak:sarfesak storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
```

في لوحة الاستضافة: اضبط Document Root على `/home/sarfesak/public_html/gaz/public`.

---

## 3) تحديث لاحق من GitHub

```bash
cd /home/sarfesak/public_html/gaz

# 1) سحب التحديثات
git remote -v
git pull origin main

# 2) لو تغيّر composer.json:
composer install --no-dev --optimize-autoloader

# 3) لو وُجدت migrations جديدة:
php artisan migrate --force

# 4) إن تغيّرت أصول Vite (CSS/JS):
npm ci && npm run build

# 5) دائماً بعد أي pull:
php artisan view:clear && php artisan view:cache
php artisan route:clear && php artisan route:cache
php artisan config:clear && php artisan config:cache
```

سطر مختصر للصق:

```bash
cd /home/sarfesak/public_html/gaz && git pull origin main && \
  php artisan view:clear && php artisan route:clear && php artisan config:clear && \
  php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## 4) قاعدة البيانات على السيرفر

### 4.1 المايغريشن
- لأول نشر، أو إذا فشل `migrate` بسبب «Table already exists» (جداول من محاولة سابقة):
  ```bash
  php artisan migrate:fresh --force
  ```
  **تحذير:** يحذف كل الصفوف. تأكد أن القاعدة فارغة بياناتاً قبله.

### 4.2 استيراد بيانات (INSERT فقط)
- الملف يُولَّد محلياً:
  ```bash
  php artisan export:mysql-data --output=~/Desktop/profile_media_DATA_ONLY_inserts.sql
  ```
- يُرفع للسيرفر عبر **phpMyAdmin** (تبويب «استيراد») في قاعدة `sarfesak_gaz`.
- أو من سطر الأوامر:
  ```bash
  scp ~/Desktop/profile_media_DATA_ONLY_inserts.sql root@104.207.65.64:/tmp/
  mysql -u sarfesak_gaz -p sarfesak_gaz < /tmp/profile_media_DATA_ONLY_inserts.sql
  ```

### 4.3 الترحيل من ERP القديم
- استورد dump ERP في قاعدة **منفصلة** (`sarfesak_gazMedia`):
  - استخدم الملف **`*_phpmyadmin_clean.sql`** (السطران الأولان حُذفا لتفادي خطأ phpMyAdmin).
- في `.env` (على السيرفر أو محلياً):
  ```env
  LEGACY_ERP_DRIVER=mysql
  LEGACY_ERP_HOST=127.0.0.1
  LEGACY_ERP_DATABASE=sarfesak_gazMedia
  LEGACY_ERP_USERNAME=...
  LEGACY_ERP_PASSWORD=...
  ```
- ثم:
  ```bash
  php artisan legacy-erp:import --dry-run   # عرض الأعداد
  php artisan legacy-erp:import             # تنفيذ فعلي
  ```

---

## 5) النسخ الاحتياطي (انظر أيضاً `DATABASE_BACKUP_AND_RESTORE_AR.md`)

| المصدر | الأمر |
|--------|-------|
| SQLite محلي | `cp database/database.sqlite database/backups/laravel_local_$(date +%Y-%m-%d_%H%M%S).sqlite` |
| تصدير لـ MySQL | `php artisan export:mysql-data` (يكتب على سطح المكتب افتراضياً) |
| MySQL إنتاج | `mysqldump -u sarfesak_gaz -p sarfesak_gaz > backup_$(date +%Y%m%d).sql` |

من الواجهة (مدير): **الإدارة → نسخ احتياطي** — الملفات في `storage/app/database-backups/` (نسخ قديمة قد تبقى في `database/backups/`). **مستثناة من Git** — انسخها لقرص خارجي/سحابة.

---

## 6) حسابات الدخول الافتراضية بعد الترحيل

| البريد | الدور | ملاحظة |
|--------|------|--------|
| `gaz@baitpait.com` | manager | حساب الإنتاج الحالي (يُفضّل تدوير كلمة المرور دورياً) |
| `admin@baitpait.com` | manager (إن بقي) | قديم بعد الترحيل — تحقق من القاعدة |
| `legacy-erp-import@localhost` | manager (تقني) | لا يُستخدم للدخول؛ موجود لربط `recorded_by_user_id` بسجلات ERP. |

### إعادة تعيين كلمة المرور (صحيح — Bcrypt)

**لا تستخدم** `User::where()->update(['password'=>…])` — يتجاوز الـ cast ويكتب النص صريحاً فيسبب:

`This password does not use the Bcrypt algorithm` عند تسجيل الدخول.

**الصحيح:**

```bash
cd /home/sarfesak/public_html/gaz && php artisan tinker --execute='$u=\App\Models\User::where("email","gaz@baitpait.com")->first(); $u->password="كلمة_جديدة_قوية"; $u->save(); echo "ok";'
```

أو عبر الملف الشخصي في الواجهة: قائمة الحساب → **الملف الشخصي**.

---

## 7) ملف `.env` الإنتاج (مرجع)

> القالب الكامل: `.env.production` (محلي، مستثنى من Git).

أهم المفاتيح للإنتاج:

```env
APP_NAME="غاز اليمني"
COMPANY_DISPLAY_NAME="Gaz Al-Yamani"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gaz.baitpait.space
APP_LOCALE=ar

DB_CONNECTION=mysql
DB_HOST=127.0.0.1            # أو localhost حسب المضيف
DB_DATABASE=sarfesak_gaz
DB_USERNAME=sarfesak_gaz
DB_PASSWORD="..."

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

MAIL_MAILER=log              # log أثناء التشغيل التجريبي، smtp لاحقاً
```

**تحذير:** لا تشارك `.env` ولا ترفعه إلى Git. غيّر كلمة مرور قاعدة البيانات من لوحة الاستضافة بعد أول نشر ناجح.

**⚠️ `APP_DEBUG`:** يجب **`false`** على الإنتاج. إن كان `true`، أي خطأ Livewire (مثل تعديل الفاتورة) يعرض **كود PHP للزبون** — راجع `INCIDENT-005`. بعد التغيير: `php artisan config:cache`.

---

## 8) استكشاف الأخطاء الشائعة

| الخطأ | السبب | الحل |
|-------|------|------|
| `HTTP 500` على الصفحة الرئيسية | غالباً صلاحيات أو cache قديم | `chmod -R ug+rwx storage bootstrap/cache` ثم `php artisan optimize:clear` ثم `config:cache` |
| `Vite manifest not found` | لم يُبنَ `public/build/manifest.json` | `npm ci && npm run build` على السيرفر (أو ارفع `public/build` من المحلي) |
| `Table 'users' already exists` عند `migrate` | جداول من محاولة سابقة و`migrations` فارغ | `php artisan migrate:fresh --force` (يحذف الكل) |
| `Identifier name ... is too long` (MySQL) | اسم فهرس تلقائي > 64 حرف (مثلاً `client_balance_adjustments_...`) | اسحب آخر `main` (commit `93f88b0+`) ثم `php artisan migrate --force`؛ الترحيل idempotent. إن بقي جدول بلا فهرس: `DROP TABLE client_balance_adjustments, supplier_balance_adjustments` ثم `migrate --force` |
| `Foreign key constraint is incorrectly formed` | استيراد dump ERP داخل قاعدة Laravel | افصل: ERP في `sarfesak_gazMedia`، Laravel في `sarfesak_gaz` |
| `1064 ... '/usr/bin/mysqldump: Deprecated'` في phpMyAdmin | السطر الأول في dump رسالة تحذير وليس SQL | استخدم نسخة `*_phpmyadmin_clean.sql` (بعد `sed '1,2d'`) |
| `APP_NAME` لا يظهر | القالب كان يحوي نصاً ثابتاً | بعد commit `46eb03d` صار يقرأ `config('app.name')` — أعد `php artisan config:cache && view:cache` |
| `fatal: 'Gaz-Alymainu' أو remote قديم does not appear to be a git repository` | اسم الـ remote على السيرفر مختلف | تحقق `git remote -v`، عادة الاسم `origin` |
| `Permission denied` عند نسخ قاعدة البيانات من الواجهة | PHP لا يكتب في `database/` | اسحب آخر `main` (يحفظ في `storage/app/database-backups`) ثم `chmod -R ug+rwx storage` |
| الفلاتر/البحث في القوائم لا يتفاعل | Livewire/Vite غير محمّل أو تهيئة مزدوجة | `npm ci && npm run build` ثم `php artisan config:clear && php artisan config:cache` (يجب `inject_assets=false` في `config/livewire.php`)؛ F12 → Network → `/livewire/update` = 200 |
| البحث يعمل في صفحة ولا في أخرى (مثلاً عملاء ✓ / موردون ✗) | **UTF-8 BOM** في بداية `.blade.php` يكسر جذر `[wire:id]` | `xxd resources/views/livewire/<file>.blade.php \| head -1` — إن ظهر `efbb bf` أزل BOM واحفظ UTF-8 بدون BOM؛ `php artisan view:clear`. راجع `docs/troubleshooting/INCIDENT-001-supplier-list-utf8-bom-livewire.md` |
| `The selected طريقة الدفع is invalid` عند تعديل دفعة | `method` مخزّن بنص عربي أو `طريقة #N` من استيراد قديم | اسحب `50ceee1+` ثم `php artisan payments:normalize-methods`. راجع `docs/troubleshooting/INCIDENT-002-payment-method-invalid-on-edit.md` |
| `<select>` يظهر أبيض/فارغ على الإنتاج (البيانات موجودة في HTML) | Dark Mode + خلفية بيضاء للحقل | اسحب `2d18e7c+` ثم `npm run build && php artisan view:cache`. راجع `docs/troubleshooting/INCIDENT-003-select-white-text-dark-mode.md` |
| `تعذّر إنشاء PDF من قالب الطباعة` (500 على `/pdf`) | Chromium غير محمّل، Node غير متاح لـ PHP، أو صلاحيات `root` على `node_modules` | راجع **§11.2**: `PUPPETEER_CACHE_DIR`، `npm run browsershot:install`، `BROWSERSHOT_NODE`، `chown` لمستخدم PHP، ثم `php artisan browsershot:check` |
| `Route [invoices.pdf] not defined` (أو أي مسار PDF جديد) بعد `git pull` | **كاش المسارات قديم** — نفّذت `config:cache` دون `route:cache` | `php artisan route:clear && php artisan route:cache` (أو `php artisan optimize:clear` ثم أعد الكاش). تحقق: `php artisan route:list --name=invoices.pdf` |
| `tempnam(): file created in the system's temporary directory` (500 على `/invoices` وغيرها) | `storage` مملوك لـ `root`/`webuzo` بينما PHP للموقع = **`sarfesak`** | `chown -R sarfesak:sarfesak storage bootstrap/cache` ثم `php artisan optimize:clear` وإعادة الكاش. راجع `INCIDENT-004` |
| `APP_DEBUG=true` على الإنتاج | أخطاء Livewire تظهر كود PHP للزبون | في `.env`: `APP_DEBUG=false` ثم `php artisan config:cache` |
| `updatedLines(): Argument #2 ($key) must be of type string, null given` | Livewire يحدّث `$lines` كاملة عند إضافة بند | اسحب `44be136+` (nullable `$key`). راجع `INCIDENT-005` |

---

## 11) PDF المستندات (Browsershot — تطابق 100% مع الطباعة)

منذ اعتماد **Browsershot**، ملفات PDF (فواتير، أوامر شراء، سندات دفع) تُولَّد من **نفس قالب Blade للطباعة** عبر Headless Chrome مع `emulateMedia('print')` — أي ما تراه في `/print` هو ما يُحمَّل في `/pdf`.

### 11.1 المتطلبات على السيرفر

| البند | التفاصيل |
|-------|----------|
| Node.js | نفس إصدار التطوير تقريباً (لـ `npm ci`) |
| Puppeteer | يُثبَّت تلقائياً عبر `npm ci` (`devDependency`) |
| Chromium | يُحمَّل مع Puppeteer في `node_modules/puppeteer` |
| PHP | حزمة `spatie/browsershot` عبر Composer |

### 11.2 النشر بعد التحديث

```bash
cd /home/sarfesak/public_html/gaz
git pull origin main
composer install --no-dev --optimize-autoloader

# Puppeteer 23 متوافق مع Node 20 — لا تستخدم puppeteer 25 على السيرفر الحالي
mkdir -p storage/app/puppeteer-cache
export PUPPETEER_CACHE_DIR=/home/sarfesak/public_html/gaz/storage/app/puppeteer-cache
npm ci
npm run browsershot:install    # تحميل Chromium إلى storage (مرة بعد كل npm ci)
npm run build
```

أضف إلى `.env` على Linux المشترك:

```env
BROWSERSHOT_NODE=/usr/bin/node
PUPPETEER_CACHE_DIR=/home/sarfesak/public_html/gaz/storage/app/puppeteer-cache
BROWSERSHOT_NO_SANDBOX=true
```

**صلاحيات (مهم — Webuzo):** مالك موقع `gaz.baitpait.space` هو **`sarfesak`** (وليس `webuzo` من pool عام):

```bash
chown -R sarfesak:sarfesak storage bootstrap/cache node_modules
chmod -R ug+rwx storage bootstrap/cache
```

**تحذير:** تشغيل `php artisan view:cache` كـ `root` ثم فتح الموقع يسبب 500:
`tempnam(): file created in the system's temporary directory` — راجع `INCIDENT-004`.

```bash
su -s /bin/bash sarfesak -c 'cd /home/sarfesak/public_html/gaz && php artisan optimize:clear && php artisan config:cache && php artisan route:cache'
chown -R sarfesak:sarfesak storage bootstrap/cache
```

ثم:

```bash
php artisan route:cache
php artisan config:cache
php artisan view:cache
php artisan browsershot:check    # يجب أن ينجح قبل تجربة PDF من المتصفح
```

### 11.2.1 تبعيات Linux لـ Chromium (إن فشل `browsershot:check`)

**Ubuntu 24.04 (Noble)** — أسماء الحزم تنتهي بـ `t64`:

```bash
apt-get update
apt-get install -y \
  ca-certificates fonts-liberation fonts-noto-color-emoji \
  libasound2t64 libatk-bridge2.0-0t64 libatk1.0-0t64 libc6 libcairo2 \
  libcups2t64 libdbus-1-3 libdrm2 libexpat1 libfontconfig1 libgbm1 \
  libglib2.0-0t64 libgtk-3-0t64 libnspr4 libnss3 libpango-1.0-0 \
  libpangocairo-1.0-0 libatspi2.0-0t64 libx11-6 libx11-xcb1 libxcb1 \
  libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 \
  libxi6 libxrandr2 libxrender1 libxss1 libxtst6 wget xdg-utils
```

**Ubuntu 22.04 وأقدم:**

```bash
apt-get install -y ca-certificates fonts-liberation libasound2 libatk-bridge2.0-0 \
  libatk1.0-0 libcups2 libdbus-1-3 libdrm2 libgbm1 libgtk-3-0 libnspr4 libnss3 \
  libx11-xcb1 libxcomposite1 libxdamage1 libxfixes3 libxrandr2 xdg-utils
```

تحقق بعد التثبيت:

```bash
CHROME=/home/sarfesak/public_html/gaz/storage/app/puppeteer-cache/chrome/linux-*/chrome-linux64/chrome
ldd "$CHROME" | grep "not found"    # يجب ألا يطبع شيئاً
"$CHROME" --version
php artisan browsershot:check
```

### 11.3 التحقق

1. افتح مثلاً `/invoices/{id}/print` — تأكد من التنسيق.
2. افتح `/invoices/{id}/pdf` — يجب أن يطابق الطباعة (خط Cairo، ألوان، تخطيط).
3. من القوائم: أزرار **طباعة** و**PDF** في عمود الإجراءات.

### 11.4 المسارات والكود

| المستند | طباعة | PDF |
|---------|-------|-----|
| فاتورة عميل | `invoices.print` | `invoices.pdf` |
| أمر شراء | `purchase-orders.print` | `purchase-orders.pdf` |
| سند دفع عميل | `payments.voucher-print` | `payments.pdf` |
| سند دفع مورد | `supplier-payments.print` | `supplier-payments.pdf` |

الخدمة: `App\Services\Documents\PrintViewPdfRenderer` — الإعداد: `config/browsershot.php`.

**ملاحظة:** قوالب `resources/views/pdf/documents/` (mPDF القديمة) لم تعد تُستخدم من الـ controllers؛ يمكن حذفها لاحقاً بعد التأكد من الإنتاج.

---

## 9) Git على السيرفر — تذكير

- بيئة العمل المعتمدة: محلياً ندفع إلى `baitpait/Gaz-Alymainu` (main)، السيرفر يسحب بـ `git pull origin main`.
- لا تعديل مباشر على السيرفر إن أمكن؛ إن حصل (كما حدث في commit `93986f5` بتعديل «ميدا» → «ميديا»): اسحبه محلياً قبل أي push جديد:
  ```bash
  git pull --rebase origin main
  ```

---

## 10) مراجع ذات صلة

- `README.md` — البداية السريعة، الأدوار، السياسات.
- `docs/00_CONSTITUTION_UI_AR.md` — قواعد الواجهة العربية.
- `docs/03_DATABASE_SPEC.md` — مواصفات الجداول.
- `docs/04_REPORTS_AND_UI_MATRIX.md` — التقارير والشاشات.
- `docs/07_SYSTEM_OVERVIEW_AR.md` — مخطط شامل (Mermaid ERD).
- `docs/DATABASE_BACKUP_AND_RESTORE_AR.md` — تفاصيل النسخ الاحتياطي.
- `docs/12_PAYMENT_METHODS_AND_LEGACY_NORMALIZATION_AR.md` — أكواد طرق الدفع وتطبيع البيانات القديمة.
- `docs/13_DOCUMENT_PDF_BROWSERSHOT_AR.md` — PDF المستندات (Browsershot، نشر، تشخيص).
- `docs/decisions/` — ADRs (قرارات معمارية).
- `docs/troubleshooting/INCIDENT-001-supplier-list-utf8-bom-livewire.md` — RCA: BOM + Livewire + بحث الأطراف.
- `docs/troubleshooting/INCIDENT-002-payment-method-invalid-on-edit.md` — RCA: طريقة دفع غير معيارية عند التعديل.
- `docs/troubleshooting/INCIDENT-003-select-white-text-dark-mode.md` — RCA: select أبيض في Dark Mode.
- `docs/troubleshooting/INCIDENT-004-tempnam-storage-ownership-php84.md` — RCA: tempnam + ملكية storage على Webuzo.
- `docs/troubleshooting/INCIDENT-005-invoice-edit-updatedlines-app-debug.md` — RCA: كود PHP ظاهر على تعديل الفاتورة + APP_DEBUG.
