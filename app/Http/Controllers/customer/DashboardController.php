<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
// use Illuminate\Http\Request; // Tidak digunakan di method index saat ini
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Customer; // Model Customer sudah di-import
use App\Models\Order;    // DIUBAH: Import Order model
// use App\Models\Item;  // Tidak secara langsung digunakan di query utama, tapi via relasi Order->items

class DashboardController extends Controller
{
    public function __construct()
    {
        // Pastikan middleware auth:customer diterapkan jika belum
        $this->middleware('auth:customer');
    }

    public function index()
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            // Ini seharusnya tidak terjadi jika middleware auth:customer aktif,
            // tapi sebagai fallback.
            return redirect()->route('customer.login')->with('error', 'Sesi Anda tidak valid. Silakan login kembali.');
        }
        /** @var \App\Models\Customer $customer */

        $activeOrdersCount = 0;
        $completedOrdersCount = 0;
        $recentOrders = collect(); // Inisialisasi sebagai collection kosong
        $errorMessage = null;

        try {
            // Mengambil jumlah order yang "sedang diproses" atau "aktif"
            // Definisi "aktif" bisa bervariasi, contoh di bawah:
            // - Pembayaran pending
            // - Pembayaran sudah paid TAPI order statusnya belum 'completed' atau 'cancelled'
            $activeOrdersCount = $customer->orders() // Menggunakan relasi orders()
                ->where(function ($query) {
                    $query->where('payment_status', 'pending')
                        ->orWhere(function ($subQuery) {
                            $subQuery->where('payment_status', 'paid')
                                ->whereNotIn('order_status', [
                                    'completed',
                                    'cancelled', // Atau status cancel spesifik: 'cancelled_by_customer', 'cancelled_by_admin', 'cancelled_payment_issue'
                                ]);
                        });
                })
                ->count();

            // Mengambil jumlah order yang sudah "selesai"
            // Definisi "selesai": order_status = 'completed' DAN payment_status = 'paid'
            $completedOrdersCount = $customer->orders() // Menggunakan relasi orders()
                ->where('order_status', 'completed')
                ->where('payment_status', 'paid') // Pastikan sudah dibayar juga
                ->count();

            // Mengambil beberapa order terbaru
            $recentOrders = $customer->orders() // Menggunakan relasi orders()
                ->with('items') // Eager load item-item dalam order
                ->latest()      // Urutkan berdasarkan created_at terbaru
                ->take(3)       // Ambil 3 order teratas
                ->get();
        } catch (\Exception $e) {
            Log::error('Error fetching customer dashboard data for customer ID ' . $customer->id . ': ' . $e->getMessage(), [
                'trace' => substr($e->getTraceAsString(), 0, 1000) // Batasi panjang trace
            ]);
            $errorMessage = 'Gagal memuat data order Anda saat ini. Silakan coba beberapa saat lagi atau hubungi support.';
        }

        $data = [
            'customer' => $customer,
            'activeOrdersCount' => $activeOrdersCount,         // Nama variabel diubah
            'completedOrdersCount' => $completedOrdersCount,   // Nama variabel diubah
            'recentOrders' => $recentOrders,                   // Nama variabel diubah
            'errorMessage' => $errorMessage,
        ];

        return view('customer.dashboard.index', $data);
    }
}
