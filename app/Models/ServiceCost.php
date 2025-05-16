<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable; // <--- PASTIKAN INI DI-IMPORT

class ServiceCost extends Model
{
    use HasFactory, Hashidable;

    protected $table = 'service_costs';

    protected $fillable = [
        'name',
        'label',
        'cost',
        'description',
        'is_active',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public static function getCostByName(string $name, float $default = 0): float
    {
        // Tambahkan Log untuk debug jika perlu
        // Log::info("Fetching ServiceCost by name: {$name}");
        $setting = self::where('name', $name)->where('is_active', true)->first();
        // if (!$setting) {
        //     Log::warning("ServiceCost '{$name}' not found or not active, using default: {$default}");
        // }
        return $setting ? (float) $setting->cost : $default;
    }
}
