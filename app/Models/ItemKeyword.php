<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Hashidable;
use Vinkla\Hashids\Facades\Hashids;

class ItemKeyword extends Model
{
    use HasFactory, Hashidable;

    protected $table = 'item_keywords';

    protected $fillable = [
        'item_id',
        'keyword_name',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

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
