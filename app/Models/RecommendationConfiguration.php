<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;
use Vinkla\Hashids\Facades\Hashids;
// use Illuminate\Database\Eloquent\Relations\BelongsTo; // Tidak perlu jika user_id dihapus

class RecommendationConfiguration extends Model
{
    use HasFactory, Hashidable;

    protected $table = 'recommendation_configurations';

    protected $fillable = [
        'parameter_name',
        'parameter_value',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
