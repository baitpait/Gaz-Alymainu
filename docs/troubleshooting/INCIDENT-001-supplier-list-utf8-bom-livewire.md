# INCIDENT-001: بحث الموردين لا يعمل — UTF-8 BOM في قالب Livewire

**الحالة:** مُغلق (مُصلَح)  
**التاريخ:** 2026-05-25  
**Commit الإصلاح:** `d0260ae` — `fix: live name search for clients and suppliers, remove supplier-list BOM`

---

## 1) الملخص التنفيذي

بحث بالاسم في قائمة **العملاء** كان يعمل، بينما نفس البحث في قائمة **الموردين** لم يكن يربط حقل الإدخال بمكوّن Livewire — أي أن الكتابة في الحقل لا تُحدّث الحالة ولا تُصفّي النتائج.

**السبب الجذري:** وجود **UTF-8 BOM** (`EF BB BF`) في بداية ملف `resources/views/livewire/supplier-list.blade.php`، مما كسر حدود جذر مكوّن Livewire.

**الإصلاح:** إزالة BOM من القالب + استبدال نظام الفلاتر القديم (زر «تطبيق») ببحث مباشر بالاسم عبر `wire:model.live.debounce`.

---

## 2) الأعراض

| الصفحة | السلوك |
|--------|--------|
| `/clients` | البحث بالاسم يعمل؛ الرابط يتحدّث (`?q=...`)؛ النتائج تُصفّى |
| `/suppliers` | حقل البحث يظهر لكن لا يرتبط بـ Livewire؛ العدد يبقى كاملاً (مثلاً 66 مورد) |

---

## 3) منهجية التشخيص (Root Cause Analysis)

### 3.1 استبعاد طبقة قاعدة البيانات

```bash
php artisan tinker --execute="echo App\Models\Supplier::query()->partyNameSearch('مطبعة')->count();"
```

الاستعلام يُرجع نتائج صحيحة → المشكلة **ليست** في Eloquent أو `HasDirectorySearch`.

### 3.2 فحص حدود DOM لمكوّن Livewire (Runtime inspection)

عبر **Chrome DevTools Protocol (CDP)** — `Runtime.evaluate` في المتصفح:

```javascript
const input = document.querySelector('input[placeholder="بحث بالاسم..."]');
const wireRoot = input?.closest('[wire\\:id]');
JSON.stringify({ inputInWireRoot: wireRoot?.contains(input) });
```

| الصفحة | `inputInWireRoot` |
|--------|-------------------|
| العملاء | `true` |
| الموردين (قبل الإصلاح) | `false` |

**الاستنتاج:** حقل البحث **خارج** جذر `[wire:id]` في صفحة الموردين → `wire:model` لا يعمل.

### 3.3 فحص سداسي عشري للملف (Hex dump analysis)

```bash
xxd resources/views/livewire/supplier-list.blade.php | head -2
```

**قبل الإصلاح:**

```
00000000: efbb bf3c 6469 763e ...    ...<div>...
```

**بعد الإصلاح:**

```
00000000: 3c64 6976 2078 2d64 ...    <div x-d...
```

البايتات `EF BB BF` = **UTF-8 BOM** (Unicode `U+FEFF` — *Byte Order Mark*).

### 3.4 آلية الكسر التقنية

1. BOM يُخرَج في HTML **قبل** `<div>` الجذر للمكوّن.
2. Livewire يعتبر أن للمكوّن **جذرين** أو حدوداً مشوّهة.
3. العنوان وحقل البحث يقعان **خارج** `[wire:id]`.
4. الجدول و`wire:click` يبقيان **داخل** المكوّن.
5. النتيجة: واجهة تبدو سليمة بصرياً لكن ربط `wire:model` على البحث **معطّل**.

---

## 4) الإصلاح

### 4.1 إزالة BOM

```bash
python3 -c "
p = 'resources/views/livewire/supplier-list.blade.php'
with open(p, 'rb') as f:
    data = f.read()
if data.startswith(b'\\xef\\xbb\\xbf'):
    with open(p, 'wb') as f:
        f.write(data[3:])
    print('BOM removed')
"
php artisan view:clear
```

### 4.2 تحسين البحث (مرافق للإصلاح)

| قبل | بعد |
|-----|-----|
| `party-directory-filters.blade.php` + زر «تطبيق الفلاتر» | `party-name-search.blade.php` + `wire:model.live.debounce.300ms` |
| `FiltersPartyDirectory` / `UsesCommittedPartyDirectoryFilters` | `ListsPartyDirectory` trait |
| `applyPartyFilters()` | `updatedSearch()` + `partyNameSearch()` scope |

**الملفات الرئيسية:**

- `app/Livewire/Concerns/ListsPartyDirectory.php`
- `resources/views/livewire/partials/party-name-search.blade.php`
- `app/Models/Concerns/HasDirectorySearch.php` → `scopePartyNameSearch()`
- `tests/Feature/PartyDirectoryListTest.php`

---

## 5) التحقق

```bash
php artisan test --filter=PartyDirectoryListTest
```

في المتصفح بعد الإصلاح:

- `inputInWireRoot: true` على `/suppliers`
- البحث بـ «مطبعة» → `?q=مطبعة` ونتيجة واحدة بدل العدد الكامل

---

## 6) النشر

```bash
cd /home/sarfesak/public_html/gaz
git pull origin main   # يجب أن يتضمن commit d0260ae+
php artisan view:clear
php artisan view:cache
```

لا حاجة لـ `npm run build` — التغيير Blade/PHP فقط.

---

## 7) الوقاية

| الممارسة | السبب |
|----------|--------|
| حفظ ملفات Blade بـ **UTF-8 بدون BOM** | BOM غير مرئي في المحرّر ويكسر Livewire/Alpine |
| فحص دوري: `xxd file.blade.php \| head -1` | اكتشاف `efbb bf` قبل النشر |
| في VS Code/Cursor: `"files.encoding": "utf8"` (بدون BOM) | يمنع إعادة إدخال BOM |
| CI (اختياري): script يرفض أي `.blade.php` يبدأ بـ `\xEF\xBB\xBF` | Fail-fast |

**قاعدة Livewire:** كل عنصر يحمل `wire:model` أو `wire:click` يجب أن يكون **داخل** عنصر واحد جذر `[wire:id]` — أي نص أو حرف قبل `<div>` الأول في القالب يكسر ذلك.

---

## 8) المصطلحات (مرجع سريع)

| المصطلح | المعنى |
|---------|--------|
| **RCA** (Root Cause Analysis) | تحليل السبب الجذري — منهجية التشخيص الكاملة |
| **Hex dump analysis** | فحص بايتات الملف سداسياً (`xxd`) |
| **UTF-8 BOM** | `EF BB BF` — علامة ترتيب بايتات خفية في بداية الملف |
| **Component root boundary inspection** | التحقق من أن عناصر Livewire داخل `[wire:id]` |

---

## 9) مراجع

- `docs/08_DEPLOYMENT_AND_OPERATIONS_AR.md` — §8 استكشاف الأخطاء
- `docs/PROJECT_LOG.md` — سجل [2026-05-25] بحث الأطراف
- [Livewire — Troubleshooting](https://livewire.laravel.com/docs/troubleshooting)
