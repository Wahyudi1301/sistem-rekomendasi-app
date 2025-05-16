<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\Hashidable;
use App\Models\Order; // DIUBAH dari Booking
use App\Models\Payment;
use App\Models\CartItem;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable, Hashidable;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email', // Umumnya email juga sebagai login
        'phone_number',
        'password',
        'address',
        'gender',
        'status', // Misal: active, inactive, banned
    ];

    protected $hidden = [
        'password',
        // 'remember_token', // Jika Anda menggunakan remember token
    ];

    protected $casts = [
        // 'email_verified_at' => 'datetime', // Jika ada verifikasi email
    ];

    /**
     * Mendapatkan semua order yang dimiliki oleh customer.
     */
    public function orders(): HasMany // DIUBAH dari bookings()
    {
        return $this->hasMany(Order::class); // Relasi ke Order::class
    }

    /**
     * Mendapatkan semua pembayaran yang dimiliki oleh customer melalui order-ordernya.
     * Ini adalah relasi HasManyThrough.
     */
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasManyThrough // Tambahkan return type hint
    {
        // Customer -> hasMany -> Order -> hasMany -> Payment
        return $this->hasManyThrough(Payment::class, Order::class); // Melalui Order::class
    }

    /**
     * Mendapatkan semua item keranjang yang dimiliki oleh customer.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // Method resolveRouteBinding dan getRouteKeyName tetap sama, sudah baik.
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
