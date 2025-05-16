<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order; // Diubah dari Booking
use App\Models\Item;
use App\Models\Store;
use App\Models\Payment; // Ditambahkan untuk update payment jika cash
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller // Nama kelas diubah
{
    // Status order yang bisa di-set oleh admin (disesuaikan)
    private function getEditableOrderStatuses(Order $order): array
    {
        if ($order->delivery_method == 'pickup') {
            return [
                // 'pending_payment' // Biasanya otomatis dari sistem
                // 'awaiting_pickup_payment' // Biasanya otomatis dari sistem
                'processing'         => 'Processing (Sedang Disiapkan)',
                'ready_for_pickup'   => 'Ready for Pickup (Siap Diambil)',
                'completed'          => 'Completed (Sudah Diambil/Selesai)',
                'cancelled_by_admin' => 'Cancelled by Admin',
            ];
        } else { // delivery
            return [
                // 'pending_payment' // Biasanya otomatis dari sistem
                'processing'         => 'Processing (Sedang Disiapkan)',
                'out_for_delivery'   => 'Out for Delivery (Sedang Diantar)',
                'delivered'          => 'Delivered (Sudah Sampai)', // Bisa jadi tahap sebelum 'completed' jika ada instalasi
                // 'installation_scheduled' => 'Installation Scheduled', // Jika ada proses instalasi
                'completed'          => 'Completed (Selesai)',
                'cancelled_by_admin' => 'Cancelled by Admin',
            ];
        }
    }

    public function index(): View
    {
        return view('admin.orders.index'); // Path view diubah
    }

    public function getData(Request $request)
    {
        // Menggunakan model Order
        $orders = Order::with(['customer:id,name,email', 'user:id,name']) // user adalah admin yg proses
            ->select('orders.*'); // Pastikan alias jika ada join

        return DataTables::of($orders)
            ->addIndexColumn()
            ->addColumn('customer_name', fn($order) => optional($order->customer)->name ?? 'N/A')
            ->addColumn('admin_handler', fn($order) => optional($order->user)->name ?? 'N/A')
            ->editColumn('created_at', fn($order) => $order->created_at ? Carbon::parse($order->created_at)->format('d M Y H:i') : '-')
            ->editColumn('preferred_delivery_date', function ($order) {
                return $order->preferred_delivery_date ? Carbon::parse($order->preferred_delivery_date)->format('d M Y') : ($order->delivery_method == 'pickup' ? 'Ambil di Tempat' : 'Belum Ditentukan');
            })
            ->addColumn('delivery_method_display', function ($order) {
                return $order->delivery_method == 'pickup' ? 'Ambil di Tempat' : ($order->delivery_option == 'delivery_install' ? 'Antar + Pasang' : 'Di Antar');
            })
            ->addColumn('payment_method_display', function ($order) {
                // Ambil dari payment record terakhir terkait order ini
                $latestPayment = $order->payments()->latest()->first();
                return ucwords(str_replace('_', ' ', $latestPayment->payment_method_gateway ?? $order->payment_method ?? '-'));
            })
            ->editColumn('total_amount', fn($order) => 'Rp' . number_format($order->total_amount, 0, ',', '.'))
            ->editColumn('payment_status', function ($order) {
                $status = $order->payment_status ?? 'unknown';
                $color = 'secondary';
                if ($status == 'pending') $color = 'warning';
                elseif ($status == 'paid') $color = 'success';
                elseif (in_array($status, ['failed', 'cancelled', 'expired', 'deny'])) $color = 'danger';
                elseif ($status == 'challenge') $color = 'info';
                return '<span class="badge bg-light-' . $color . '">' . ucwords(str_replace('_', ' ', $status)) . '</span>';
            })
            ->editColumn('order_status', function ($order) { // Menggunakan order_status langsung
                $status = $order->order_status ?? 'unknown';
                $color = 'secondary';
                if (in_array($status, ['pending_payment', 'processing', 'awaiting_pickup_payment', 'payment_review'])) $color = 'warning';
                elseif (in_array($status, ['ready_for_pickup', 'out_for_delivery', 'delivered', 'installation_scheduled'])) $color = 'info';
                elseif ($status == 'completed') $color = 'success';
                elseif (str_contains($status, 'cancelled')) $color = 'danger';
                return '<span class="badge bg-light-' . $color . '">' . ucwords(str_replace('_', ' ', $status)) . '</span>';
            })
            ->addColumn('action', function ($order) {
                $showUrl = route('admin.orders.show', $order->hashid); // Menggunakan nama route order
                $editUrl = route('admin.orders.editStatus', $order->hashid); // Menggunakan nama route order
                $printUrl = route('admin.orders.print', $order->hashid);

                $buttons = '<div class="btn-group" role="group">';
                $buttons .= '<a href="' . $showUrl . '" class="btn btn-sm btn-outline-info" title="Lihat Detail"><i class="bi bi-eye-fill"></i></a>';
                // Tombol edit status hanya jika order belum selesai atau cancel
                if (!in_array($order->order_status, ['completed', 'cancelled_by_admin', 'cancelled_payment_issue', 'cancelled_by_customer'])) {
                    $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-info" title="Ubah Status Order"><i class="bi bi-pencil-square"></i></a>';
                }
                $buttons .= '<a href="' . $printUrl . '" class="btn btn-sm btn-outline-secondary" target="_blank" title="Cetak Order"><i class="bi bi-printer-fill"></i></a>';
                $buttons .= '</div>';
                return $buttons;
            })
            ->rawColumns(['payment_status', 'order_status', 'action'])
            ->make(true);
    }

    public function show(Order $order): View // Menggunakan model Order
    {
        $order->load(['customer', 'user', 'items.brand', 'items.category', 'payments' => fn($q) => $q->orderBy('created_at', 'desc')]);
        $latestPayment = $order->payments()->latest()->first(); // Ambil payment method dari payment record
        return view('admin.orders.show', compact('order', 'latestPayment')); // Path view diubah
    }

    public function editStatus(Order $order): View // Menggunakan model Order
    {
        $statuses = $this->getEditableOrderStatuses($order); // Ambil status berdasarkan metode pengiriman
        return view('admin.orders.edit_status', compact('order', 'statuses')); // Path view diubah
    }

    public function updateStatus(Request $request, Order $order) // Menggunakan model Order
    {
        $editableStatuses = $this->getEditableOrderStatuses($order);
        $validated = $request->validate([
            'order_status' => ['required', 'string', Rule::in(array_keys($editableStatuses))],
            'admin_notes'  => ['nullable', 'string', 'max:2000'],
            // Tambahkan validasi untuk 'payment_confirmation' jika admin bisa konfirmasi pembayaran cash
            'confirm_cash_payment' => ['nullable', 'boolean'],
        ]);

        $newOrderStatus = $validated['order_status'];
        $oldOrderStatus = $order->order_status;
        $adminUser = Auth::user();

        // Cek apakah ada perubahan atau admin notes diisi
        $hasChanged = $newOrderStatus !== $oldOrderStatus ||
            !empty($validated['admin_notes']) ||
            ($adminUser && $order->user_id !== $adminUser->id) ||
            ($request->has('confirm_cash_payment') && $order->payment_status === 'pending');


        if (!$hasChanged) {
            return redirect()->route('admin.orders.show', $order->hashid)
                ->with('info', 'Tidak ada perubahan yang dilakukan.');
        }

        DB::beginTransaction();
        try {
            $order->order_status = $newOrderStatus;
            $order->user_id = $adminUser->id; // Admin yang memproses

            if (!empty($validated['admin_notes'])) {
                $adminName = $adminUser->name;
                $newNoteEntry = "Note by {$adminName} (" . now()->format('d/m/Y H:i') . "): " . $validated['admin_notes'];
                $order->admin_notes = $order->admin_notes ? $order->admin_notes . "\n\n" . $newNoteEntry : $newNoteEntry;
            }

            // Logika jika admin mengkonfirmasi pembayaran tunai
            if ($request->input('confirm_cash_payment') && $order->payment_status === 'pending') {
                $payment = $order->payments()->where('payment_method_gateway', 'cash')->where('transaction_status', 'pending')->latest()->first();
                if ($payment) {
                    $payment->transaction_status = 'paid'; // atau 'settlement'
                    $payment->transaction_time = now();
                    $payment->settlement_time = now();
                    // $payment->gateway_transaction_id = 'CASH-' . $order->order_code . '-' . $payment->id; // Opsional ID internal
                    $payment->save();

                    $order->payment_status = 'paid';
                    Log::info("Admin Update: Cash payment confirmed for Order {$order->order_code}. Payment ID: {$payment->id}. Processed by Admin ID: {$adminUser->id}");

                    // Jika setelah bayar cash dan ambil di toko, langsung set jadi 'ready_for_pickup' atau 'processing'
                    if ($newOrderStatus === 'awaiting_pickup_payment' || $oldOrderStatus === 'awaiting_pickup_payment') {
                        $order->order_status = 'processing'; // atau 'ready_for_pickup' jika barang sudah pasti siap
                    }
                } else {
                    Log::warning("Admin Update: No pending cash payment record found to confirm for Order {$order->order_code}.");
                }
            }


            // PENTING: Logika pengurangan stok item saat order diproses (misal, saat status menjadi 'processing' dan pembayaran 'paid')
            if (
                $newOrderStatus === 'processing' && $order->payment_status === 'paid' &&
                !in_array($oldOrderStatus, ['processing', 'ready_for_pickup', 'out_for_delivery', 'delivered', 'completed'])
            ) { // Hanya kurangi sekali
                $this->decreaseItemStock($order);
            }

            // TIDAK ADA penambahan stok kembali di sini karena ini jual beli, bukan sewa.
            // Penambahan stok kembali hanya jika ada proses refund/pembatalan yang mengembalikan barang.

            $order->save();
            DB::commit();

            return redirect()->route('admin.orders.show', $order->hashid)
                ->with('success', 'Status order berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Admin Order Update Error: " . $e->getMessage(), [
                'order_id' => $order->id,
                'admin_id' => $adminUser->id,
                'request_data' => $request->all(),
                'trace' => substr($e->getTraceAsString(), 0, 1000)
            ]);
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui order: ' . $e->getMessage());
        }
    }

    // Method decreaseItemStock tetap sama seperti di MidtransController (atau bisa dijadikan Trait)
    protected function decreaseItemStock(Order $order): void
    {
        try {
            $order->loadMissing('items');
            foreach ($order->items as $itemPivot) {
                $itemMaster = Item::find($itemPivot->id);
                if ($itemMaster) {
                    if ($itemMaster->stock >= $itemPivot->pivot->quantity) {
                        $itemMaster->decrement('stock', $itemPivot->pivot->quantity);
                        Log::info("Stock for item ID {$itemMaster->id} ('{$itemMaster->name}') decremented by {$itemPivot->pivot->quantity} for order {$order->order_code}. New stock: {$itemMaster->stock}");
                    } else {
                        Log::error("CRITICAL - Stock for item ID {$itemMaster->id} ('{$itemMaster->name}') is insufficient ({$itemMaster->stock}) to decrement {$itemPivot->pivot->quantity} for order {$order->order_code}. Order Payment was successful. MANUAL INTERVENTION REQUIRED.");
                    }
                } else {
                    Log::error("Item master with ID {$itemPivot->id} not found during stock decrement for order {$order->order_code}.");
                }
            }
        } catch (\Exception $e) {
            Log::error("Exception during stock decrement for order {$order->order_code}. Error: " . $e->getMessage(), ['trace' => substr($e->getTraceAsString(), 0, 500)]);
        }
    }


    public function printOrder(Order $order): View // Diubah dari printBooking
    {
        $order->load(['customer', 'user', 'items.brand', 'items.category', 'payments']);
        $storeDetails = Store::first(); // Ambil data toko pertama, atau sesuaikan logikanya
        $latestPayment = $order->payments()->latest()->first();

        if (!$storeDetails) {
            $storeDetails = new Store([
                'name' => config('app.name', 'Nama Toko Anda'),
                'address' => 'Alamat Toko Belum Diatur',
                'phone_number' => '-', // Sesuaikan dengan nama kolom di Store Anda
                'email' => '-',
            ]);
        }
        return view('admin.orders.print', compact('order', 'storeDetails', 'latestPayment')); // Path view diubah
    }
}
