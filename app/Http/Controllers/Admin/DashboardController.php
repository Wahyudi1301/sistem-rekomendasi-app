<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use App\Models\Item;
use App\Models\Customer;
use App\Models\Order; // DIUBAH dari Booking
use App\Models\Brand;
use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
// use Carbon\Carbon; // Tidak digunakan secara langsung di sini lagi

class DashboardController extends Controller
{
    public function index(): View
    {
        $loggedInUser = Auth::user();
        /** @var \App\Models\User $loggedInUser */
        $data = [];

        // Data Umum yang selalu ada untuk Admin dan Staff
        $data['totalItems'] = Item::count();

        // Menggunakan model Order
        $data['pendingOrders'] = Order::whereIn('order_status', ['pending_payment', 'processing', 'awaiting_pickup_payment', 'payment_review'])->count();
        $data['activeOrders'] = Order::whereIn('order_status', ['ready_for_pickup', 'out_for_delivery', 'delivered_pending_installation', 'installation_scheduled'])->count();
        $data['completedOrders'] = Order::where('order_status', 'completed')->where('payment_status', 'paid')->count();

        $data['recentOrders'] = Order::with([
            'customer:id,name', // Hanya pilih kolom yang dibutuhkan dari customer
            'items' => function ($query) {
                $query->select('items.id'); // Hanya perlu ID untuk count atau relasi, bukan semua data item
            }
        ])
            ->select('id', 'order_code', 'customer_id', 'created_at', 'total_amount', 'payment_status', 'order_status', 'delivery_method') // Sesuaikan kolom
            ->latest() // Berdasarkan created_at
            ->take(5)
            ->get();


        // Data spesifik berdasarkan Role
        if ($loggedInUser && $loggedInUser->isAdmin()) {
            $data['totalCustomers'] = Customer::count();
            $data['totalBrands'] = Brand::count();
            $data['totalCategories'] = Category::count();
            $data['totalUsers'] = User::count();
            $data['verifiedPayments'] = Payment::whereIn('transaction_status', ['settlement', 'capture', 'paid'])->count(); // Tambahkan 'paid' jika ada pembayaran cash manual
            $data['pendingPayments'] = Payment::where('transaction_status', 'pending')->count();
        } elseif ($loggedInUser && $loggedInUser->isStaff()) {
            $data['totalCustomers'] = Customer::count();
            $data['totalBrands'] = Brand::count();
            $data['totalCategories'] = Category::count();
            $data['verifiedPayments'] = Payment::whereIn('transaction_status', ['settlement', 'capture', 'paid'])->count();
            $data['pendingPayments'] = Payment::where('transaction_status', 'pending')->count();
        }

        return view('admin.dashboard.index', $data);
    }
}
