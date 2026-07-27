<?php

namespace App\Livewire;

use App\Enums\WarehouseType;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class WarehouseForm extends Component
{
    public ?int $warehouseId = null;

    public string $name = '';

    public string $code = '';

    public string $type = 'fixed';

    public ?int $assigned_user_id = null;

    public string $vehicle_plate = '';

    public bool $is_active = true;

    public string $notes = '';

    /** @var array<int, int> معرّفات الأصناف المسموحة في المخزن */
    public array $allowedProductIds = [];

    public function mount(?Warehouse $warehouse = null): void
    {
        if ($warehouse && $warehouse->exists) {
            Gate::authorize('update', $warehouse);
            $this->warehouseId = $warehouse->id;
            $this->name = $warehouse->name;
            $this->code = $warehouse->code ?? '';
            $this->type = $warehouse->type->value;
            $this->assigned_user_id = $warehouse->assigned_user_id;
            $this->vehicle_plate = $warehouse->vehicle_plate ?? '';
            $this->is_active = (bool) $warehouse->is_active;
            $this->notes = $warehouse->notes ?? '';
            $this->allowedProductIds = $warehouse->allowedProducts()->pluck('products.id')->all();
        } else {
            Gate::authorize('create', Warehouse::class);
        }
    }

    public function save(): void
    {
        if ($this->warehouseId) {
            Gate::authorize('update', Warehouse::findOrFail($this->warehouseId));
        } else {
            Gate::authorize('create', Warehouse::class);
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'nullable', 'string', 'max:64',
                Rule::unique('warehouses', 'code')->ignore($this->warehouseId)->whereNull('deleted_at'),
            ],
            'type' => ['required', Rule::in(array_keys(WarehouseType::options()))],
            'assigned_user_id' => 'nullable|exists:users,id',
            'vehicle_plate' => 'nullable|string|max:32',
            'notes' => 'nullable|string|max:5000',
            'allowedProductIds' => 'array',
            'allowedProductIds.*' => 'integer|exists:products,id',
        ], [], [
            'name' => 'اسم المخزن',
            'code' => 'الرمز',
            'type' => 'النوع',
            'assigned_user_id' => 'السائق',
            'vehicle_plate' => 'رقم اللوحة',
        ]);

        if ($this->type === WarehouseType::Vehicle->value && ! $this->assigned_user_id) {
            $this->addError('assigned_user_id', 'يجب تعيين سائق للسيارة.');

            return;
        }

        $wasEditing = $this->warehouseId !== null;

        DB::transaction(function () use ($wasEditing) {
            $data = [
                'name' => $this->name,
                'code' => trim($this->code) !== '' ? trim($this->code) : null,
                'type' => $this->type,
                'assigned_user_id' => $this->type === WarehouseType::Vehicle->value ? $this->assigned_user_id : null,
                'vehicle_plate' => $this->type === WarehouseType::Vehicle->value && trim($this->vehicle_plate) !== ''
                    ? trim($this->vehicle_plate) : null,
                'is_active' => $this->is_active,
                'notes' => trim($this->notes) !== '' ? trim($this->notes) : null,
            ];

            if ($wasEditing) {
                $warehouse = Warehouse::query()->findOrFail($this->warehouseId);
                $warehouse->update($data);
            } else {
                $data['recorded_by_user_id'] = auth()->id();
                $warehouse = Warehouse::query()->create($data);
            }

            $warehouse->allowedProducts()->sync($this->allowedProductIds);
        });

        session()->flash('toast', $wasEditing ? 'تم تحديث المخزن' : 'تم إضافة المخزن');

        $this->redirect(route('warehouses.index'), navigate: true);
    }

    public function render()
    {
        $drivers = User::query()
            ->where('role', 'driver')
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $products = Product::query()
            ->stockTracked()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.warehouse-form', [
            'drivers' => $drivers,
            'products' => $products,
            'typeOptions' => WarehouseType::options(),
        ]);
    }
}
