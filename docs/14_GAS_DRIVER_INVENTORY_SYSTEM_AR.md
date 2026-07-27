# نظام السائقين والمخزون ونقطة البيع — غاز اليمين

> **الغرض التجاري:** إدارة توزيع أسطوانات (جرّات) الغاز عبر سائقين يملكون سيارات تعمل كـ«مخازن متنقلة»،
> مع تحميل البضاعة من المخازن الثابتة، تسعير يومي مرن، تسجيل مبيعات نقدية/على الحساب،
> تحصيل وسحب كاش من السائق إلى الصندوق الرئيسي، ومصروفات خاصة بالسائق/السيارة.

آخر تحديث: 2026-07-20. يُقرأ مع: `03_DATABASE_SPEC.md`، `07_SYSTEM_OVERVIEW_AR.md`، `08_DEPLOYMENT_AND_OPERATIONS_AR.md`.

---

## 1. المراحل (Phasing)

| المرحلة | النطاق | الحالة |
|--------|--------|--------|
| **Phase 1** | المخازن + التحميل/التحويل + التسعير اليومي + أرصدة وحركات المخزون | ✅ مكتمل |
| **Phase 2** | نقطة البيع (POS) + المبيعات + التحصيل + سحب الكاش + مصروفات السائق + تقارير الغاز | ✅ مكتمل |
| **مؤجّل** | محاسبة ذمم السائق التفصيلية / تتبّع الجرّات الفارغة والتأمين | ⏳ لاحقاً |

**قرارات تصميمية أساسية:**
- السيارة = **مخزن** نوعه `vehicle` (توحيد منطق التحميل والأرصدة بدل جدول منفصل).
- لا تتبّع للجرّات الفارغة ولا تأمين في هذه المرحلة — تُتتبّع **الكميات الممتلئة فقط**.
- سعر بيع **يومي واحد لكل صنف** بالشيكل (ILS) كاقتراح من الإدارة، **قابل للتعديل** لحظة البيع في POS.

---

## 2. نموذج البيانات (Data Model)

### الجداول (Migrations — `database/migrations/2026_07_20_*`)

| الملف | الجدول | الغرض |
|------|--------|-------|
| `100001_add_driver_role_to_users` | `users` | إضافة دور `driver` |
| `100002_add_user_id_to_employees` | `employees` | ربط الموظف بحساب مستخدم |
| `100003_add_gas_fields_to_products` | `products` | حقول الغاز (السعة/الوحدة… `is_gas`) |
| `100004_create_warehouses_table` | `warehouses` | المخازن الثابتة + السيارات |
| `100005_create_warehouse_product_table` | `warehouse_product` | الأصناف المسموح بها لكل مخزن |
| `100006_create_stock_balances_table` | `stock_balances` | الرصيد الحالي لكل (مخزن × صنف) |
| `100007_create_stock_movements_table` | `stock_movements` | سجل كل حركة مخزون |
| `100008_create_product_daily_prices_table` | `product_daily_prices` | سعر البيع اليومي لكل صنف |
| `110001_extend_stock_movement_types` | `stock_movements` | إضافة `transfer` و`sale_out` لـ ENUM |
| `110002_create_sales_table` | `sales` | تسجيل المبيعات (بدون بيانات زبون) |
| `110003_create_cash_handovers_table` | `cash_handovers` | تسليم الكاش من السائق للرئيسي |
| `120001_create_collections_table` | `collections` | التحصيلات (نقدي/شيك) |
| `130001_add_method_to_cash_handovers_table` | `cash_handovers` | إضافة `method` + `cheque_number` |
| `140001_create_driver_expenses_table` | `driver_expenses` | مصروفات السائق/السيارة |

### النماذج (`app/Models`)
`Warehouse`، `StockBalance`، `StockMovement`، `ProductDailyPrice`، `Sale`، `CashHandover`، `Collection`، `DriverExpense`.

### الـ Enums (`app/Enums`)

| Enum | القيم | الاستخدام |
|------|-------|----------|
| `WarehouseType` | `fixed` / `vehicle` | نوع المخزن |
| `StockMovementType` | `in` / `out` / `transfer` / `sale_out` | نوع حركة المخزون (`manualOptions()` للحركات اليدوية) |
| `SalePaymentType` | `cash` / `credit` | نوع بيع نقدي/على الحساب |
| `CollectionMethod` | `cash` / `cheque` | طريقة التحصيل والتسليم |
| `DriverExpenseCategory` | `fuel` / `maintenance` / `other` | تصنيف مصروف السائق |

---

## 3. الخدمات (Business Logic — `app/Services`)

- **`InventoryService`** — كل تعديلات المخزون داخل `DB::transaction` (ACID):
  - `transfer()` — تحويل كميات بين مخزنين (يشمل التحميل من مخزن ثابت إلى سيارة، والإرجاع).
  - `saleOut()` — خصم الكمية المباعة من مخزن السائق.
- **`DailyPriceService`** — `priceFor($productId, $date, $currency)`؛ العملة الافتراضية `ILS`.
- **`SalesService`** — `recordSale(..., ?float $unitPriceOverride = null)`: يستخدم السعر المُدخل في POS، ويرجع لسعر اليوم كافتراضي. يرفض البيع بسعر ≤ 0.
- **`CashBoxService`** — صندوق كل سائق حسب العملة:
  - `balance()` = مبيعات نقدية + تحصيلات نقدية − كاش مُسلّم − مصروفات السائق.
  - `chequeBalance()` — رصيد الشيكات لدى السائق.
  - `handOver(..., CollectionMethod $method, ?string $chequeNumber)` — تسليم كاش/شيك للرئيسي.
  - `mainBoxByCurrency()` — تجميع الصندوق الرئيسي.
  - `recordExpense(...)` — تسجيل مصروف سائق (يتحقق ألا يتجاوز الرصيد النقدي).

---

## 4. التدفّق المالي (Cash Flow)

```
مبيعات نقدية ─┐
تحصيلات نقدية ─┼─▶ صندوق السائق (نقدي) ──(سحب كاش)──▶ الصندوق الرئيسي (حسب العملة)
مصروفات السائق ◀┘ (تُخصم من صندوقه)

تحصيلات/تسليمات شيكات ──▶ تظهر في الحركات المالية مع ملاحظاتها (لا تدخل الرصيد النقدي)
مبيعات على الحساب ──▶ تخصم من مخزون السائق فقط (لا تدخل الصندوق النقدي)
```

صفحة `/financial-summary` تعرض: الصندوق الرئيسي (نقدي + شيكات مُسلّمة)، صناديق السائقين (غير المُسلّمة)، حركات الشيكات، وآخر مصروفات السائقين.

---

## 5. الصلاحيات (Gates — `AppServiceProvider`)

| Gate | من يملكها |
|------|-----------|
| `manage-inventory` / `manage-daily-prices` | المحاسب فأعلى |
| `view-inventory` / `view-sales` | أي مستخدم فعّال |
| `manage-drivers` | المدير |
| `record-sales` | السائق أو المحاسب |
| `manage-cash-handover` | السائق أو المحاسب |
| `manage-driver-expenses` | السائق أو المحاسب |

---

## 6. المسارات وواجهة الاستخدام

### المسارات الرئيسية (`routes/web.php`)
المجموعة محميّة بـ `['auth', 'driver.sales', 'block.sales']`:
- `/pos` (`pos.index`) — نقطة البيع.
- `/collections` (`collections.index`) — التحصيل المستقل.
- `/driver-expenses` (`driver-expenses.index`) — مصروفات السائق.
- `/cash-handovers` (`cash-handovers.index`) — **سحب الكاش**.
- `/sales`, `/warehouses`, `/drivers`, `/daily-prices`, `/stock-movements`.
- تقارير الغاز: `/reports/gas-{sales|collections|driver-cash|stock-balances|stock-movements|driver-performance}`.

### الوسائط (Middleware)
- **`RestrictDriverToSales`** (`driver.sales`): يحصر السائق في `pos.index`, `collections.index`, `driver-expenses.index`, `logout` فقط.
- **`BlockSalesModule`** (`block.sales`): يحجب مسارات المبيعات القديمة (نظام الوسائط الإعلامي السابق).

### واجهة موبايل أولاً (Mobile-first)
- POS بتصميم موبايل: بطاقات أصناف بها **عدّاد متبقٍ**، سعر قابل للتعديل، عدّاد كمية `+/−`، وزرّا **بيع نقدي / على الحساب**. الكمية تُصفَّر بعد كل بيع.
- **دوائر إحصائية** بألوان الهوية أعلى POS (نقدي اليوم، على الحساب، مجموع التحصيلات، كاش الصندوق).
- **شريط تنقّل سفلي ثابت** (للموبايل) لمن يملك `record-sales`: المبيعات · التحصيل · المصروفات.
- السائق **بلا قائمة جانبية** إطلاقاً.

---

## 7. التقارير (`resources/views/livewire/reports/gas`)
مبيعات الغاز، التحصيلات، صندوق السائق (يشمل المصروفات والصافي)، أرصدة المخزون، حركات المخزون، أداء السائقين. كلها بفلترة تواريخ وتصدير CSV.

---

## 8. ملاحظات تشغيلية (Operational)

- **بناء الأصول إلزامي:** أي أصناف Tailwind جديدة (خصوصاً القيم التعسّفية مثل `shadow-[...]`, `text-[11px]`, `bottom-0`, `inset-x-0`, `z-40`) لا تظهر حتى تشغيل:
  ```bash
  npm run build && php artisan view:cache
  ```
  > حادثة موثّقة: الشريط السفلي كان يُصيَّر في DOM لكنه غير مرئي لأن `bottom-0/inset-x-0/z-40` لم تكن مبنية في CSS.
- **بيانات تجريبية:** أُدخل مخزون تجريبي في كل المخازن للاختبار (يُنظّف قبل الإنتاج).
- **حسابات الدخول الحالية (محلي):** المدير `admin@gaz.local` / `password` — **غيّرها قبل الإنتاج**. السائق: `aa@aa.com`.
- **قواعد الدستور ذات الصلة:** أسماء الحقول التي يستخدمها التطبيق لا تُعاد تسميتها (توافق خلفي)؛ الحذف منطقي (`is_deleted`/`softDeletes`)؛ التوقيتات UTC عبر Timestamp.
