# INCIDENT-005 — كود PHP ظاهر للزبون على تعديل الفاتورة (`updatedLines` + APP_DEBUG)

**التاريخ:** 2026-07-04  
**البيئة:** `profile.baitpait.com` — Ubuntu 24.04، PHP 8.4.12، Livewire 4  
**التقرير:** زبون (فاتورة #758) — `/invoices/758/edit`  
**الحالة:** **مُغلق** — commit `44be136` + `APP_DEBUG=false` على الإنتاج

---

## 1) الملخص

عند **تعديل فاتورة** (إضافة بند، تعديل سعر/كمية، بحث صنف)، ظهر **نص PHP** داخل الصفحة للزبون — مقتطف من `InvoiceForm.php` (دوال `onProductSearchFocus` / `updatedLines`).

---

## 2) الأعراض

- صفحة `/invoices/{id}/edit` تعمل جزئياً ثم يظهر كود برمجي في منطقة **بنود الفاتورة**
- في `storage/logs/laravel.log`:

```
App\Livewire\InvoiceForm::updatedLines(): Argument #2 ($key) must be of type string, null given
at app/Livewire/InvoiceForm.php:131
```

- `grep APP_DEBUG .env` → **`APP_DEBUG=true`** (إنتاج)

---

## 3) السبب الجذري (طبقتان)

### أ) خطأ برمجي (TypeError)

Livewire عند تحديث خاصية `$lines` **كاملة** (مثلاً `addLine()` يضيف عنصراً للمصفوفة) يستدعي:

```php
updatedLines(Array $value, null $key)
```

الدالة كانت معرّفة:

```php
public function updatedLines(mixed $value, string $key): void
```

→ **TypeError** → HTTP 500 على طلب `/livewire/update`.

**الملفات المتأثرة:** `InvoiceForm`, `PurchaseOrderForm`, `InvoiceList` (نفس النمط).

### ب) تسريب للزبون (APP_DEBUG)

مع **`APP_DEBUG=true`** على الإنتاج، Laravel يعرض **صفحة خطأ Symfony** (مقتطف الكود) — Livewire يحقن HTML الخطأ في DOM → الزبون يرى PHP على الشاشة.

> **storage** كان سليماً (`baitpait:baitpait`, `storage:doctor` OK) — هذه الحادثة **ليست** tempnam (راجع INCIDENT-004).

---

## 4) التشخيص

```bash
cd /home/baitpait/public_html/profile

grep APP_DEBUG .env
tail -80 storage/logs/laravel.log | grep updatedLines

php artisan storage:doctor   # للاستبعاد — tempnam
```

---

## 5) الحل

### أ) الكود (GitHub `main` ≥ `44be136`)

```php
public function updatedLines(mixed $value, ?string $key = null): void
{
    if ($key === null || $key === '') {
        return;
    }
    // ...
}
```

**اختبار:** `tests/Feature/InvoiceFormClientSearchTest.php` — `invoice form add line survives whole lines array sync`.

### ب) الإنتاج

```bash
git pull origin main
sed -i 's/^APP_DEBUG=true/APP_DEBUG=false/' .env
php artisan config:cache
chown -R baitpait:baitpait storage bootstrap/cache
```

---

## 6) التحقق

1. افتح `/invoices/758/edit`
2. «+ إضافة بند» — تعديل سعر/كمية — بحث صنف
3. لا كود PHP، لا 500

```bash
grep "updatedLines" storage/logs/laravel.log | tail -3
# لا إدخالات جديدة بعد التجربة
```

---

## 7) الوقاية

| القاعدة | التفاصيل |
|---------|----------|
| `APP_DEBUG=false` | **إلزامي** على `profile.baitpait.com` |
| Livewire nested hooks | أي `updated{Property}($value, $key)` لمصفوفات — `$key` قد يكون `null` |
| بعد `git pull` | `config:cache` + `chown baitpait` |

---

## 8) Commits

| Commit | الوصف |
|--------|--------|
| `44be136` | `fix: accept null key in Livewire updatedLines hooks` |

---

## 9) مراجع

- `app/Livewire/InvoiceForm.php` — `updatedLines`, `onProductSearchFocus`, `addLine`
- `resources/views/livewire/invoice-form.blade.php` — `wire:focus="onProductSearchFocus({{ $i }})"`
- `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md` §8
- `docs/troubleshooting/INCIDENT-004-tempnam-storage-ownership-php84.md` — حادثة storage منفصلة
