<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Item extends Model
{
    use HasFactory, Hashidable;

    protected $table = 'items';

    protected $fillable = [
        'name',
        'price',
        'description',
        'category_id',
        'brand_id',
        'stock',
        'img',
        'status',
        'sku',
        // Kolom spesifik AC tambahan untuk filter dan Case-Based
        'btu_capacity',
        'power_consumption_watt',
        'is_inverter',
        'freon_type',
        'room_size_min_sqm',         // BARU
        'room_size_max_sqm',         // BARU
        'warranty_compressor_years', // BARU
        'warranty_parts_years',      // BARU
        // Kolom untuk atribut utama generik (Case-Based)
        'main_attribute_1_name',
        'main_attribute_1_value',
        'main_attribute_2_name',
        'main_attribute_2_value',
        'main_attribute_3_name',
        'main_attribute_3_value',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'btu_capacity' => 'integer',
        'power_consumption_watt' => 'integer',
        'is_inverter' => 'boolean',
        'room_size_min_sqm' => 'integer',         // BARU
        'room_size_max_sqm' => 'integer',         // BARU
        'warranty_compressor_years' => 'integer', // BARU
        'warranty_parts_years' => 'integer',      // BARU
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_items')
            ->withPivot('quantity', 'price_per_item')
            ->withTimestamps();
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(ItemKeyword::class);
    }

    // Method Hashidable
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'hashid') {
            $decodedId = Hashids::decode($value);
            if (empty($decodedId)) { return null; }
            $id = $decodedId[0];
            return $this->find($id);
        }
        return parent::resolveRouteBinding($value, $field);
    }

    public function getRouteKeyName()
    {
        return 'id';
    }
}
