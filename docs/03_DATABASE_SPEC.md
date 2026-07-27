# مواصفة قاعدة البيانات المفاهيمية — غاز اليمين

> **ملاحظة:** هذا مخطط **منطقي** مستقل عن محرك SQL/NoSQL. عند اختيار التقنية، تُترجم الجداول إلى migrations مع فهارس وقيود.

## 1) كيانات أساسية

### `users` — مستخدمو النظام
| الحقل | النوع المنطقي | الوصف |
|--------|----------------|--------|
| id | UUID أو BIGINT | مفتاح. |
| email | نص | فريد؛ تسجيل الدخول. |
| password_hash | نص | بترميز آمن (لا يُخزّن نصاً صريحاً). |
| full_name | نص | |
| role | نص/مرجع | ربط بدور صلاحيات. |
| is_active | منطقي | |
| created_at / updated_at | UTC | |

### `staffs` أو دمج مع `users`
- **قرار التنفيذ:** جدول `employees` منفصل عن `users` — لا ربط `user_id` على الموظف.
- التفاصيل: `docs/10_EMPLOYEES_AND_PAYROLL_AR.md`

### `employees` — الموظفون (HR)
| الحقل | الوصف |
|--------|--------|
| employee_code | اختياري؛ فريد. |
| full_name | إلزامي. |
| department, job_title | |
| base_salary_amount, base_salary_currency | الراتب الأساسي المرجعي. |
| is_active | |
| recorded_by_user_id | من أضاف السجل. |
| deleted_at | soft delete. |

### `salary_payments` — سجل الرواتب
| الحقل | الوصف |
|--------|--------|
| employee_id | FK |
| period_year, period_month | شهر الراتب. |
| base_amount, bonus_amount, deduction_amount, net_amount | |
| currency_code | |
| status | draft / paid / cancelled |
| paid_at, method | عند الدفع. |
| recorded_by_user_id | |
| **فريد:** | employee + year + month + currency |

### `clients` — العملاء
| الحقل | الوصف |
|--------|--------|
| legacy_number | اختياري؛ للربط ببيانات قديمة. |
| legacy_match_key | فريد عند وجود تكرار أرقام قديمة + هوية منشأة. |
| business_name, names, email, phones, address, country_code | |
| notes | نص حر. |
| assigned_user_id | اختياري. |
| is_deleted | soft delete. |
| source_row_json | اختياري؛ أرشيف ترحيل. |

### `client_contacts`
- جهات اتصال إضافية؛ `client_id` FK.

### `suppliers` / `supplier_contacts`
- مطابق لمنطق العملاء.

---

## 2) المستندات التجارية (نص حر)

### `invoices`
| الحقل | الوصف |
|--------|--------|
| client_id | FK |
| legacy_invoice_no | فريد عند الترحيل. |
| document_date, issue_date, due_date | |
| currency_code | عملة المستند. |
| discount_amount, total_amount | نص عشري أو DECIMAL حسب المحرك. |
| status | draft / issued / void |
| recorded_by_user_id | |
| source_row_json | أرشيف XML/قديم. |

### `invoice_lines`
| الحقل | الوصف |
|--------|--------|
| invoice_id | |
| line_order | ترتيب. |
| title | **نص حر** (لا كتالوج إجباري). |
| description | اختياري. |
| unit_price, quantity, line_total | |
| source_row_json | اختياري. |

### `purchase_orders` / `purchase_order_lines`
- نفس المنطق مع `supplier_id`.

---

## 3) الدفعات (على الطرف — ليس على الفاتورة)

### `client_payments`
| الحقل | الوصف |
|--------|--------|
| client_id | |
| amount | |
| currency_code | قد تختلف عن فاتورة معيّنة. |
| paid_at | UTC. |
| method | **كود معياري:** `cash` \| `bank` \| `check` \| `transfer` — لا تخزين نص عربي. التفاصيل: `docs/12_PAYMENT_METHODS_AND_LEGACY_NORMALIZATION_AR.md` |
| bank_reference, notes | |
| recorded_by_user_id | |
| is_deleted | |

### `supplier_payments`
- مطابق للمورد؛ نفس قواعد `method`.

### `client_balance_adjustments`
| الحقل | الوصف |
|--------|--------|
| client_id | |
| amount | **موجب دائماً** — خصم/إعفاء على الذمة |
| currency_code | |
| adjustment_date | |
| type | `settlement_discount` \| `write_off` \| `other` |
| reason, notes | |
| recorded_by_user_id | |
| deleted_at | soft delete |

**فهرس:** `cba_client_cur_date_idx` على `(client_id, currency_code, adjustment_date)`.

### `supplier_balance_adjustments`
- مطابق للمورد؛ فهرس `sba_supplier_cur_date_idx`.

> **كشف الحساب:** يُحسب **لكل عملة** على حدة:
> - **عميل:** مجموع الفواتير الصادرة − مجموع الدفعات − **مجموع التسويات**
> - **مورد:** مجموع أوامر الشراء الصادرة − مجموع الدفعات − **مجموع التسويات**
>
> التفاصيل: `docs/09_BALANCE_ADJUSTMENTS_AND_STATEMENTS_AR.md`

---

## 4) اليومية النصية

### `expenses`
- `description` إلزامي (نص مركزي)، `amount`, `currency_code`, `expense_date`, `notes`, `recorded_by`.

### `income_entries`
- إيرادات عامة لا تصنّف كدفعة عميل.

---

## 5) أرشيف كتالوج قديم (اختياري)

### `legacy_catalog_products` / `legacy_catalog_projects`
- للحفاظ على بيانات XML قديمة دون إجبار المنطق الجديد على كتالوج.

---

## 6) فهارس مقترحة
- `(client_id, currency_code, document_date)` على الفواتير.
- `(client_id, paid_at)` على الدفعات.
- `cba_client_cur_date_idx` على `(client_id, currency_code, adjustment_date)` في `client_balance_adjustments`.
- `sba_supplier_cur_date_idx` على `(supplier_id, currency_code, adjustment_date)` في `supplier_balance_adjustments`.
- `(is_deleted)` حيث يُستخدم بكثرة في التصفية.

---

## 7) ارتباط بمشروع الترحيل الحالي
- يوجد مرجع عملي مُرحَّل في مسار آخر: `database/business_v1.sqlite` + سكربتات الترحيل — يمكن اعتباره **مرجعاً** عند بناء migrations للمشروع النهائي.
