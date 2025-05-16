<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vinkla\Hashids\Facades\Hashids;

class Payment extends Model
{
    use HasFactory, Hashidable;

    protected $table = 'payments';

    protected $fillable = [
        'order_id',                     // DIUBAH dari booking_id
        'customer_id',
        'gateway_reference_id',       // DIUBAH dari payment_gateway_order_id
        'gateway_transaction_id',     // DIUBAH dari midtrans_transaction_id
        'payment_method_gateway',     // DIUBAH dari payment_type (menyimpan metode dari gateway, misal: qris, bank_transfer)
        'payment_channel',            // BARU (menyimpan detail channel, misal: bca_va, gopay, instore untuk cash)
        'transaction_status',
        'amount',                     // DIUBAH dari gross_amount
        'transaction_time',
        'settlement_time',            // BARU (jika ada dari gateway)
        'expiry_time',                // BARU (untuk VA, QRIS, dll.)
        'gateway_response_payload',   // DIUBAH dari midtrans_response_payload
        'fraud_status',               // BARU (jika ada dari gateway)
        'snap_token',                 // BARU (untuk menyimpan snap_token Midtrans)
        // 'notes' // Jika Anda masih memerlukan kolom notes di tabel payment untuk catatan admin, tambahkan di sini dan di migrasi.
        // Untuk sekarang saya hilangkan karena tidak ada di migrasi terakhir.
    ];

    protected $casts = [
        'amount' => 'decimal:2',        // DIUBAH dari gross_amount
        'transaction_time' => 'datetime',
        'settlement_time' => 'datetime',  // BARU
        'expiry_time' => 'datetime',      // BARU
        'gateway_response_payload' => 'array', // DIUBAH dari midtrans_response_payload
    ];

    /**
     * Mendapatkan order yang terkait dengan pembayaran ini.
     */
    public function order(): BelongsTo // DIUBAH dari booking()
    {
        return $this->belongsTo(Order::class, 'order_id'); // Relasi ke Order model, foreign key 'order_id'
    }

    /**
     * Mendapatkan customer yang terkait dengan pembayaran ini.
     * (Bisa juga diakses melalui $payment->order->customer)
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Method untuk Route Model Binding {payment:hashid} tetap sama
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'hashid') {
            $decodedId = Hashids::decode($value);
            if (empty($decodedId)) {
                return null;
            }
            return $this->find($decodedId[0]);
        }
        return parent::resolveRouteBinding($value, $field);
    }

    public function getRouteKeyName()
    {
        return 'id';
    }
}
