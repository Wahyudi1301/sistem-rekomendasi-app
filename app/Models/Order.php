<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Casts\Attribute; // Ditambahkan untuk Accessor

class Order extends Model
{
    use HasFactory, Hashidable;

    protected $table = 'orders';

    protected $fillable = [
        'order_code',
        'customer_id',
        'user_id', // Jika admin/staff memproses order, tambahkan ini dan di migrasi
        'total_item_price',
        'shipping_cost',
        'installation_cost',
        'total_amount',
        'delivery_method',
        'delivery_option',
        'preferred_delivery_date',
        // 'delivery_address', // Dihapus karena tidak ada di migrasi terakhir Anda
        // 'customer_phone_for_delivery', // Dihapus karena tidak ada di migrasi terakhir Anda
        // 'payment_method', // Dihapus (disimpan di tabel Payment)
        'payment_status',
        'order_status',
        'customer_notes',
        'admin_notes',
    ];

    protected $casts = [
        'total_item_price' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'installation_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'preferred_delivery_date' => 'date:Y-m-d',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    // Relasi ke User (Admin/Staff yang memproses)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id'); // Asumsi ada kolom user_id
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'order_items')
            ->withPivot('quantity', 'price_per_item')
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    // Accessor untuk mendapatkan metode pembayaran dari record payment terakhir
    // Nama accessor harusnya paymentMethod (camelCase) agar bisa diakses sebagai $order->payment_method
    protected function paymentMethod(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $latestPayment = $this->payments()->latest()->first();
                return $latestPayment ? $latestPayment->payment_method_gateway : null;
            }
        );
    }

    // Accessor untuk mendapatkan alamat pengiriman dari customer jika tidak disimpan di order
    protected function deliveryAddress(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->delivery_method === 'delivery' ? $this->customer->address : null
        );
    }

    // Accessor untuk mendapatkan nomor telepon pengiriman dari customer jika tidak disimpan di order
    protected function customerPhoneForDelivery(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->delivery_method === 'delivery' ? $this->customer->phone_number : null
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
