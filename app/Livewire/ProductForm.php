<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductCurrencyPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProductForm extends Component
{
    public ?int $productId = null;

    public string $name = '';

    public string $product_code = '';

    public string $description = '';

    public bool $is_stock_tracked = false;

    public string $unit = '';

    public string $capacity_kg = '';

    public string $category = '';

    /**
     * لكل عملة: تكلفة الخدمة، الحد الأدنى للبيع، سعر البيع (نصوص عشرية للنموذج).
     *
     * @var array<string, array{service_cost_price: string, min_sale_price: string, sale_price: string}>
     */
    public array $pricesByCurrency = [];

    public function mount(?Product $product = null): void
    {
        foreach (Product::billingCurrencies() as $cc) {
            $this->pricesByCurrency[$cc] = [
                'service_cost_price' => '',
                'min_sale_price' => '',
                'sale_price' => '',
            ];
        }

        if ($product && $product->exists) {
            Gate::authorize('update', $product);
            $this->productId = $product->id;
            $this->name = $product->name;
            $this->product_code = $product->product_code ?? '';
            $this->description = $product->description ?? '';
            $this->is_stock_tracked = (bool) $product->is_stock_tracked;
            $this->unit = $product->unit ?? '';
            $this->capacity_kg = $product->capacity_kg !== null ? (string) $product->capacity_kg : '';
            $this->category = $product->category ?? '';
            $product->load('currencyPrices');
            foreach ($product->currencyPrices as $row) {
                $cc = $row->currency_code;
                if (! isset($this->pricesByCurrency[$cc])) {
                    continue;
                }
                $this->pricesByCurrency[$cc] = [
                    'service_cost_price' => (string) $row->service_cost_price,
                    'min_sale_price' => (string) $row->min_sale_price,
                    'sale_price' => (string) $row->sale_price,
                ];
            }
        } else {
            Gate::authorize('create', Product::class);
        }
    }

    private function currencyBlockHasAny(string $currencyCode): bool
    {
        $b = $this->pricesByCurrency[$currencyCode] ?? [];

        foreach (['service_cost_price', 'min_sale_price', 'sale_price'] as $k) {
            if (trim((string) ($b[$k] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    public function save(): void
    {
        if ($this->productId) {
            Gate::authorize('update', Product::findOrFail($this->productId));
        } else {
            Gate::authorize('create', Product::class);
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'product_code' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('products', 'product_code')->ignore($this->productId),
            ],
            'description' => 'nullable|string|max:5000',
            'is_stock_tracked' => 'boolean',
            'unit' => 'nullable|string|max:32',
            'capacity_kg' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:64',
        ], [], [
            'name' => 'اسم المنتج',
            'product_code' => 'رمز المنتج',
            'description' => 'الوصف',
            'unit' => 'الوحدة',
            'capacity_kg' => 'السعة (كغ)',
            'category' => 'التصنيف',
        ]);

        foreach (Product::billingCurrencies() as $cc) {
            $b = $this->pricesByCurrency[$cc] ?? [];
            $any = $this->currencyBlockHasAny($cc);
            if (! $any) {
                continue;
            }

            $rules = [
                "pricesByCurrency.$cc.service_cost_price" => 'required|numeric|min:0',
                "pricesByCurrency.$cc.min_sale_price" => 'required|numeric|min:0',
                "pricesByCurrency.$cc.sale_price" => 'required|numeric|min:0',
            ];
            $this->validate($rules, [], [
                "pricesByCurrency.$cc.service_cost_price" => "تكلفة الخدمة ({$cc})",
                "pricesByCurrency.$cc.min_sale_price" => "الحد الأدنى للبيع ({$cc})",
                "pricesByCurrency.$cc.sale_price" => "سعر البيع ({$cc})",
            ]);

            $min = (float) $b['min_sale_price'];
            $sale = (float) $b['sale_price'];
            if ($min > $sale) {
                $this->addError("pricesByCurrency.$cc.min_sale_price", 'الحد الأدنى للبيع يجب أن يكون أقل أو يساوي سعر البيع');

                return;
            }
        }

        $wasEditing = $this->productId !== null;

        DB::transaction(function () use ($wasEditing) {
            $data = [
                'name' => $this->name,
                'product_code' => trim($this->product_code) !== '' ? trim($this->product_code) : null,
                'description' => trim($this->description) !== '' ? trim($this->description) : null,
                'is_stock_tracked' => $this->is_stock_tracked,
                'unit' => trim($this->unit) !== '' ? trim($this->unit) : null,
                'capacity_kg' => trim($this->capacity_kg) !== '' ? (float) $this->capacity_kg : null,
                'category' => trim($this->category) !== '' ? trim($this->category) : null,
            ];

            if ($wasEditing) {
                $product = Product::query()->findOrFail($this->productId);
                $product->update($data);
            } else {
                $product = Product::query()->create($data);
            }

            $product->currencyPrices()->delete();

            foreach (Product::billingCurrencies() as $cc) {
                if (! $this->currencyBlockHasAny($cc)) {
                    continue;
                }
                $b = $this->pricesByCurrency[$cc];
                ProductCurrencyPrice::query()->create([
                    'product_id' => $product->id,
                    'currency_code' => $cc,
                    'service_cost_price' => (float) $b['service_cost_price'],
                    'min_sale_price' => (float) $b['min_sale_price'],
                    'sale_price' => (float) $b['sale_price'],
                ]);
            }
        });

        session()->flash('toast', $wasEditing ? 'تم تحديث المنتج' : 'تم إضافة المنتج');

        $this->redirect(route('products.index'), navigate: true);
    }

    public function render()
    {
        $labels = [
            'ILS' => 'ILS — شيكل',
            'JOD' => 'JOD — دينار أردني',
            'USD' => 'USD — دولار',
            'EUR' => 'EUR — يورو',
        ];

        return view('livewire.product-form', compact('labels'));
    }
}
