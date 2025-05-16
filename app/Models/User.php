<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\Hashidable;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany; // Ditambahkan untuk relasi Order

class User extends Authenticatable // implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, Hashidable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_STAFF = 'staff';

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'address',
        'gender',
        'status',
        'role',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => 'string',
    ];

    /**
     * Mendapatkan semua order yang di-handle oleh user (admin/staff) ini.
     */
    public function handledOrders(): HasMany // Diubah dari handledBookings
    {
        return $this->hasMany(Order::class, 'user_id'); // Relasi ke Order::class
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    protected function roleDisplay(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => isset($attributes['role']) ? ucfirst($attributes['role']) : 'N/A',
        );
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'hashid') {
            $decodedId = Hashids::decode($value);
            if (empty($decodedId)) {
                return null;
            }
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
