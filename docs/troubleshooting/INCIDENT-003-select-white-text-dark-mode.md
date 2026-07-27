# INCIDENT-003: قوائم `<select>` تظهر «بيضاء/فارغة» على الإنتاج

**الحالة:** مُغلق (مُصلَح)  
**التاريخ:** 2026-06-29  
**Commit الإصلاح:** `2d18e7c` — `fix: force readable select text in dark mode browsers`  
**مثال:** قائمة المورد في `/supplier-adjustments` — `wire:model="supplier_id"`

---

## 1) الملخص التنفيذي

على السيرفر، حقل `<select class="input">` يظهر بخلفية بيضاء **بدون نص مرئي** رغم وجود الخيارات في HTML.

**ما ليس السبب:** بيانات فارغة، فشل Livewire، أو غياب `npm run build` (تم التحقق: `app-*.css` يُرجع HTTP 200).

**السبب الجذري:** تعارض **Dark Mode** في المتصفح/النظام مع `.input { background: #fff }` — المتصفح يفرض لون نص فاتح على `<select>` المغلق فيصبح النص أبيض على أبيض.

**الإصلاح:** في `resources/css/app.css`:

```css
select.input {
    color-scheme: light;
    color: var(--color-brand-grey);
    background-color: #fff;
}
select.input option {
    color: var(--color-brand-grey);
    background-color: #fff;
}
```

ثم `npm run build` على السيرفر.

---

## 2) التشخيص

| الخطوة | الأمر / الإجراء |
|--------|-----------------|
| التحقق من CSS | `curl -I https://gaz.baitpait.space/build/assets/app-*.css` → 200 |
| Computed styles | F12 → `color` على `<select>` — إن كان `rgb(255,255,255)` → Dark Mode |
| اختبار سريع | نفس الصفحة بوضع فاتح في المتصفح |

---

## 3) النشر

```bash
cd /home/sarfesak/public_html/gaz
git pull origin main
npm ci && npm run build
php artisan view:cache
```

> `public/build` **غير متتبّع في Git** — البناء على السيرفر إلزامي بعد تغيير CSS.

---

## 4) مراجع

- `resources/css/app.css` — طبقة `@layer components`
- `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md` — جدول استكشاف الأخطاء
