# نسخ احتياطي لقاعدة البيانات — غاز اليمين

## ما الذي يُنسخ؟

| المصدر | الوصف |
|--------|--------|
| **Laravel محلي (SQLite)** | الملف `database/database.sqlite` — كل جداول التطبيق على الجهاز. |
| **تصدير MySQL (بيانات فقط)** | أوامر `INSERT` فقط، بدون `CREATE TABLE` — للاستيراد على السيرفر بعد `migrate`. |
| **ERP القديم (MariaDB)** | ملف dump في `database/backups/legacy_erp_baitpait_profileMedia_2026-05-12.sql` والنسخة النظيفة لـ phpMyAdmin: `*_phpmyadmin_clean.sql`. |

## نسخ يدوية سريعة (محلي)

### 1) نسخة ملف SQLite (استرجاع كامل للوكل)

```bash
cd "/path/to/profile_media"
cp database/database.sqlite "database/backups/laravel_local_$(date +%Y-%m-%d_%H%M%S).sqlite"
```

للاسترجاع: أوقف التطبيق، انسخ النسخة فوق `database/database.sqlite`، ثم شغّل التطبيق.

### 2) ملف SQL لـ MySQL (بيانات فقط)

```bash
php artisan export:mysql-data --output=database/backups/laravel_mysql_data_only_$(date +%Y-%m-%d_%H%M%S).sql
```

أو إلى سطح المكتب:

```bash
php artisan export:mysql-data --output=~/Desktop/profile_media_DATA_ONLY_inserts.sql
```

**قبل التصدير من نسخة SQLite قديمة:**

```bash
php artisan export:mysql-data --sqlite=/المسار/database.sqlite --output=~/Desktop/export.sql
```

## السيرفر (MySQL)

بعد تشغيل المشروع على الإنتاج، خذ نسخة دورية:

```bash
mysqldump -u baitpait_profile -p baitpait_profile > backup_profile_$(date +%Y%m%d).sql
```

(يُفضّل جدولة عبر cron أو أداة النسخ في لوحة الاستضافة.)

## ERP القديم (`baitpait_profileMedia`)

- الاستيراد إلى قاعدة **منفصلة** عن `baitpait_profile`.
- استخدم الملف `*_phpmyadmin_clean.sql` في phpMyAdmin لتفادي أول سطرين غير SQL.
- ترحيل البيانات إلى Laravel: `php artisan legacy-erp:import` بعد ضبط `LEGACY_ERP_*` في `.env`.

## تسجيل الدخول (بعد ترحيل ERP نموذجياً)

| البريد | ملاحظة |
|--------|--------|
| `admin@baitpait.com` | غيّر كلمة المرور بعد أول دخول؛ قد تُضبط مؤقتاً عند التصدير. |
| `legacy-erp-import@localhost` | حساب تقني لـ `recorded_by`؛ يُفضّل إعادة تعيين كلمة المرور أو الاعتماد على `admin`. |

## تحذيرات

- **لا** تشغّل `php artisan migrate:fresh` على بيئة فيها بيانات حقيقية دون نسخة احتياطية.
- ملفات `database/backups/*.sql` و`laravel_local_*.sqlite` **مستثناة من Git** (حجم وخصوصية) — احتفظ بنسخ خارج المستودع أو على قرص/سحابة.

## مجلد النسخ في المشروع

- `database/backups/README_AR.txt` — تعليمات مختصرة لملف ERP.
- نسخ آلية بأسماء زمنية: `laravel_local_YYYY-MM-DD_HHMMSS.sqlite` و`laravel_mysql_data_only_*.sql` (محلية فقط، غير مرفوعة لـ Git).
