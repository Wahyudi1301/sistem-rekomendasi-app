<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use App\Traits\Hashidable; // Opsional jika Anda butuh hashid untuk pivot record

class OrderItem extends Pivot
{
    use HasFactory; // , Hashidable (jika pakai)

    protected $table = 'order_items';
    public $incrementing = true;

    protected $fillable = [
        'order_id',
        'item_id',
        'quantity',
        'price_per_item',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_per_item' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
