<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    protected $fillable = [
        'purchase_order_id', 'product_id', 'line_order', 'title', 'description',
        'unit_price', 'quantity', 'line_total', 'source_row_json',
    ];

    protected $casts = [
        'unit_price' => 'decimal:4',
        'quantity' => 'decimal:4',
        'line_total' => 'decimal:4',
        'source_row_json' => 'array',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
