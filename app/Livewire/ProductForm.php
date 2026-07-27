<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductCurrencyPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Business Purpose: إنشاء/تعديل منتج غاز بأسعار شيكل (ILS) فقط — بدون منطق خدمات متعددة العملات.
 */
class ProductForm extends Component
{
    public const PRICING_CURRENCY = 'ILS';

    public ?int $productId = null;

    public string $name = '';

    public string $product_code = '';

    public string $description = '';

    public bool $is_stock_tracked = true;

    public string $unit = '';

    public string $capacity_kg = '';

    public string $category = '';

    public string $service_cost_price = '';

    public string $min_sale_price = '';

    public string $sale_price = '';

    public function mount(?Product $product = null): void
    {
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

            $row = $product->currencyPrices()
                ->where('currency_code', self::PRICING_CURRENCY)
                ->first();

            if ($row !== null) {
                $this->service_cost_price = (string) $row->service_cost_price;
                $this->min_sale_price = (string) $row->min_sale_price;
                $this->sale_price = (string) $row->sale_price;
            }
        } else {
            Gate::authorize('create', Product::class);
            $this->is_stock_tracked = true;
        }
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
            'service_cost_price' => 'required|numeric|min:0',
            'min_sale_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
        ], [], [
            'name' => 'اسم المنتج',
            'product_code' => 'رمز المنتج',
            'description' => 'الوصف',
            'unit' => 'الوحدة',
            'capacity_kg' => 'السعة (كغ)',
            'category' => 'التصنيف',
            'service_cost_price' => 'تكلفة المنتج (شيكل)',
            'min_sale_price' => 'الحد الأدنى للبيع (شيكل)',
            'sale_price' => 'سعر البيع (شيكل)',
        ]);

        $min = (float) $this->min_sale_price;
        $sale = (float) $this->sale_price;
        if ($min > $sale) {
            $this->addError('min_sale_price', 'الحد الأدنى للبيع يجب أن يكون أقل أو يساوي سعر البيع');

            return;
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

            ProductCurrencyPrice::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'currency_code' => self::PRICING_CURRENCY,
                ],
                [
                    'service_cost_price' => (float) $this->service_cost_price,
                    'min_sale_price' => (float) $this->min_sale_price,
                    'sale_price' => (float) $this->sale_price,
                ],
            );
        });

        session()->flash('toast', $wasEditing ? 'تم تحديث المنتج' : 'تم إضافة المنتج');

        $this->redirect(route('products.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.product-form');
    }
}
