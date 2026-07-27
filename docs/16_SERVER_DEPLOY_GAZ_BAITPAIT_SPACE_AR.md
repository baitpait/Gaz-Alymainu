# نشر سريع — gaz.baitpait.space

| البند | القيمة |
|------|--------|
| الدومين | https://gaz.baitpait.space |
| جذر Laravel | `/home/sarfesak/public_html/gaz` |
| Document Root | `/home/sarfesak/public_html/gaz/public` |
| GitHub | https://github.com/baitpait/Gaz-Alymainu |
| مستخدم OS | `sarfesak` |

## أوامر أول نشر (على السيرفر)

```bash
cd /home/sarfesak/public_html
git clone https://github.com/baitpait/Gaz-Alymainu.git gaz
cd gaz
composer install --no-dev --optimize-autoloader
cp .env.example .env
# عدّل .env: APP_URL, DB_*, APP_DEBUG=false, GOOGLE_MAPS_API_KEY
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
npm ci && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
chown -R sarfesak:sarfesak storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
```

## لوحة الاستضافة

- Document Root → `/home/sarfesak/public_html/gaz/public`
- أنشئ قاعدة MySQL واضبط `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` في `.env`

## تحديث لاحق

```bash
cd /home/sarfesak/public_html/gaz && git pull origin main && \
  composer install --no-dev --optimize-autoloader && \
  php artisan migrate --force && \
  npm ci && npm run build && \
  php artisan optimize:clear && \
  php artisan config:cache && php artisan route:cache && php artisan view:cache
```

التفاصيل: `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md`
