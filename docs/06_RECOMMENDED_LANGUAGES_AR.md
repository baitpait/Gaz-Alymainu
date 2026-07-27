# لغات البرمجة والمكدس — غاز اليمين (مُحدَّث)

> **تم الاعتماد:** **Laravel** للباكند.  
> **الفرونت:** التوصية الافتراضية **Blade + Livewire 3 + Alpine.js + Tailwind CSS** — راجع `docs/decisions/ADR-001-backend-laravel-frontend-stack.md`.

## 1) المبدأ (دون تغيير)

- **واجهة المستخدم:** عربي فقط RTL — `docs/00_CONSTITUTION_UI_AR.md`.  
- **أسماء الرموز والكود:** إنجليزي (classes، methods، ملفات).

## 2) المكدس المعتمد

| الطبقة | التقنية | ملاحظات |
|--------|---------|-----------|
| **Backend** | **PHP 8.2+** مع **Laravel** (آخر إصدار مستقر LTS أو الحالي حسب سياسة المشروع) | FormRequest، Policies، Queues، Migrations، Eloquent + SoftDeletes حيث يلزم. |
| **Frontend (افتراضي)** | **Blade** + **Livewire 3** + **Alpine.js** + **Tailwind CSS** | مناسب للتقارير والجداول وRTL؛ تقليل تشتت لغات الواجهة. |
| **Frontend (بديل)** | **Inertia.js + Vue 3** (+ TypeScript اختياري) | عند الحاجة لتفاعل أعمق مع الحفاظ على Laravel للتوجيه والجلسات. |
| **قاعدة البيانات** | **SQL** عبر Laravel Migrations — **MySQL أو PostgreSQL** للإنتاج؛ **SQLite** مقبول للتطوير/عينات | يتوافق مع `database/business_v1.sqlite` كمرجع ترحيل قديم. |
| **التصدير / التقارير** | حزم Laravel شائعة (مثل **Laravel Excel**، **DomPDF** / Snappy) — يُثبت في `composer.json` مع ADR إن لزم | للـ PDF/Excel من التقارير العربية. |
| **الاختبارات** | **PHPUnit** أو **Pest** (PHP) | Feature tests للصلاحيات والتقارير الحساسة. |
| **CI** | **YAML** (GitHub Actions) | |

## 3) معايير جودة PHP/Laravel

- **PSR-12** للتنسيق؛ **PHPStan/Pint** حسب إعداد المشروع.  
- **Controllers نحيفة**؛ منطق الأعمال في **Actions / Services** قابلة للاختبار.  
- **لا أسرار في Git** — `config()` يقرأ من `.env` في التطبيق فقط؛ القيم الحساسة عبر مدير أسرار في الإنتاج.

## 4) ما يزال يحتاج ADR عند الانحراف

- استبدال Livewire بـ Inertia كمسار رئيسي للمشروع كله.  
- إضافة SPA منفصلة بالكامل عن Laravel.
