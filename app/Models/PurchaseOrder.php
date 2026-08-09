<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_id', 'legacy_po_no',
        'document_date', 'due_date',
        'currency_code', 'discount_amount', 'total_amount',
        'notes', 'status', 'recorded_by_user_id', 'source_row_json',
        'receiving_warehouse_id', 'inventory_posted_at', 'is_inventory_valuation',
    ];

    protected $casts = [
        'document_date' => 'date',
        'due_date' => 'date',
        'discount_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'source_row_json' => 'array',
        'inventory_posted_at' => 'datetime',
        'is_inventory_valuation' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class)->orderBy('line_order');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function receivingWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'receiving_warehouse_id');
    }
}
