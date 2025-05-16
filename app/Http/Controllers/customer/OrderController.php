<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Item;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\ServiceCost;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:customer');
    }

    public function processOrder(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        /** @var \App\Models\Customer $customer */

        $validatedData = $request->validate([
            'selected_items'    => 'required|array|min:1',
            'selected_items.*'  => 'required|string',
            'quantities'        => 'required|array',
            'quantities.*'      => 'required|integer|min:1',
            'delivery_method'   => 'required|string|in:pickup,delivery',
            'delivery_option'   => 'nullable|string|in:delivery_only,delivery_install|required_if:delivery_method,delivery',
            'preferred_delivery_date' => 'nullable|date|after_or_equal:today|required_if:delivery_method,delivery',
            'payment_method'    => 'required|string|in:qris,cash', // Ini tetap divalidasi dari form
            'customer_notes'    => 'nullable|string|max:1000',
        ], [
            'selected_items.required' => 'Anda harus memilih setidaknya satu item untuk dibeli.',
            'delivery_method.required' => 'Metode pengiriman harus dipilih.',
            'delivery_option.required_if' => 'Opsi pengantaran harus dipilih jika memilih diantar.',
            'preferred_delivery_date.required_if' => 'Tanggal pengiriman harus diisi jika memilih diantar.',
            'payment_method.required' => 'Metode pembayaran harus dipilih.',
        ]);

        // Simpan metode pembayaran yang dipilih customer untuk digunakan saat membuat record Payment
        $chosenPaymentMethod = $validatedData['payment_method'];

        if ($validatedData['delivery_method'] === 'delivery') {
            if (empty($customer->address) || empty($customer->phone_number)) {
                return back()->withInput()->with('error', 'Alamat lengkap dan nomor telepon Anda di profil harus diisi untuk metode pengiriman diantar. Silakan update profil Anda.');
            }
            if ($chosenPaymentMethod === 'cash') {
                return back()->withInput()->with('error', 'Pembayaran tunai tidak tersedia untuk metode pengiriman diantar.');
            }
        }

        $selectedCartItemHashes = $validatedData['selected_items'];
        $inputQuantities = $validatedData['quantities'];
        $itemsForOrderPivot = [];
        $totalItemPrice = 0;

        DB::beginTransaction();
        try {
            foreach ($selectedCartItemHashes as $cartItemHash) {
                // ... (logika validasi item dan stok) ...
                $decodedCartItemId = Hashids::decode($cartItemHash);
                if (empty($decodedCartItemId)) throw new \Exception("Format ID keranjang tidak valid: {$cartItemHash}.");
                $cartItemId = $decodedCartItemId[0];
                $quantityForItem = $inputQuantities[$cartItemHash] ?? null;
                if (is_null($quantityForItem) || !is_numeric($quantityForItem) || $quantityForItem < 1) throw new \Exception("Jumlah item keranjang tidak valid.");
                $quantityForItem = (int) $quantityForItem;
                $cartItem = $customer->cartItems()->with('item')->find($cartItemId);
                if (!$cartItem || !$cartItem->item) throw new \Exception("Item keranjang (ID:{$cartItemId}) tidak ditemukan/item asli dihapus.");
                $itemMaster = $cartItem->item;
                if ($itemMaster->status !== 'available') throw new \Exception("Item '{$itemMaster->name}' tidak tersedia.");
                if ($quantityForItem > $itemMaster->stock) throw new \Exception("Stok '{$itemMaster->name}' tidak cukup (diminta: {$quantityForItem}, tersedia: {$itemMaster->stock}).");
                if ($quantityForItem > CartItem::MAX_QUANTITY) throw new \Exception("Jumlah maks '{$itemMaster->name}' adalah " . CartItem::MAX_QUANTITY . ".");
                $salePrice = $itemMaster->price;
                $subTotal = $salePrice * $quantityForItem;
                $totalItemPrice += $subTotal;
                $itemsForOrderPivot[$itemMaster->id] = ['quantity' => $quantityForItem, 'price_per_item' => $salePrice];
            }

            if (empty($itemsForOrderPivot)) {
                DB::rollBack();
                return redirect()->route('customer.cart.index')->with('error', 'Tidak ada item valid yang dipilih untuk diproses.');
            }

            $costShippingOnlyFromDB = ServiceCost::getCostByName('shipping_delivery_only', 0);
            $costShippingInstallTotalFromDB = ServiceCost::getCostByName('shipping_delivery_install', 0);
            $shippingCost = 0;
            $installationCost = 0;

            if ($validatedData['delivery_method'] === 'delivery') {
                if ($validatedData['delivery_option'] === 'delivery_install') {
                    $shippingCost = $costShippingOnlyFromDB;
                    $currentInstallationCost = $costShippingInstallTotalFromDB - $costShippingOnlyFromDB;
                    $installationCost = ($currentInstallationCost > 0) ? $currentInstallationCost : 0;
                } else {
                    $shippingCost = $costShippingOnlyFromDB;
                }
            }

            $totalAmount = $totalItemPrice + $shippingCost + $installationCost;

            $order = Order::create([
                'order_code'        => 'ORDER-' . strtoupper(Str::random(8)) . '-' . substr(time(), -4),
                'customer_id'       => $customer->id,
                'total_item_price'  => $totalItemPrice,
                'shipping_cost'     => $shippingCost,
                'installation_cost' => $installationCost,
                'total_amount'      => $totalAmount,
                'payment_status'    => 'pending',
                'order_status'      => $chosenPaymentMethod === 'cash' ? 'awaiting_pickup_payment' : 'pending_payment',
                'delivery_method'   => $validatedData['delivery_method'],
                'delivery_option'   => $validatedData['delivery_option'] ?? null,
                'preferred_delivery_date' => isset($validatedData['preferred_delivery_date']) ? Carbon::parse($validatedData['preferred_delivery_date']) : null,
                // Kolom delivery_address, customer_phone_for_delivery, payment_method tidak disimpan di tabel 'orders' lagi
                'customer_notes'    => $validatedData['customer_notes'] ?? null,
            ]);
            $order->items()->attach($itemsForOrderPivot);

            $gatewayReferenceIdForPayment = $order->order_code . '-' . ($chosenPaymentMethod === 'cash' ? 'CASH' : 'QRIS') . '-' . time();
            Payment::create([
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'gateway_reference_id' => $gatewayReferenceIdForPayment,
                'payment_method_gateway' => $chosenPaymentMethod, // Simpan metode pembayaran di sini
                'payment_channel' => $chosenPaymentMethod === 'cash' ? 'instore' : null,
                'transaction_status' => 'pending',
                'amount' => $order->total_amount,
                'expiry_time' => $chosenPaymentMethod === 'qris' ? Carbon::now()->addHours(config('midtrans.expiry_duration_hours', 2)) : null,
            ]);

            $cartItemIdsToDelete = [];
            foreach ($selectedCartItemHashes as $hash) {
                $decoded = Hashids::decode($hash);
                if (!empty($decoded)) $cartItemIdsToDelete[] = $decoded[0];
            }
            if (!empty($cartItemIdsToDelete)) {
                $customer->cartItems()->whereIn('id', $cartItemIdsToDelete)->delete();
            }

            DB::commit();

            if ($chosenPaymentMethod === 'qris') {
                Log::info("Order {$order->order_code} (ID: {$order->id}) created by customer ID: {$customer->id} with QRIS. Redirecting to initiate payment.");
                return redirect()->route('customer.payment.initiate', ['order_hashid' => $order->hashid]);
            } else { // Cash
                Log::info("Order {$order->order_code} (ID: {$order->id}) created by customer ID: {$customer->id} with Cash. Redirecting to order detail page.");
                return redirect()->route('customer.orders.show', ['order_hashid' => $order->hashid])
                    ->with('success', 'Pesanan Anda (#' . $order->order_code . ') telah dibuat! Silakan lakukan pembayaran tunai saat pengambilan barang di toko.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Order Checkout Process Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine(), [
                'customer_id' => $customer->id,
                'request_data' => $request->except(['_token'])
            ]);
            return redirect()->route('customer.cart.index')->with('error', 'Checkout Gagal: ' . $e->getMessage());
        }
    }

    public function myOrders(): View
    {
        return view('customer.orders.index');
    }

    public function getMyOrdersData(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        /** @var \App\Models\Customer $customer */

        try {
            // Kita perlu join dengan tabel payments untuk mendapatkan payment_method,
            // atau menggunakan accessor di model Order jika hanya ingin menampilkan dari payment terakhir.
            // Untuk DataTables server-side, join lebih efisien jika Anda akan filter/sort berdasarkan payment_method.
            // Jika tidak, accessor di model Order sudah cukup.

            $ordersQuery = $customer->orders()
                ->leftJoin('payments', function ($join) {
                    // Ambil payment terakhir yang 'pending' atau 'paid' (atau sesuai logika Anda) untuk setiap order
                    // Ini bisa kompleks, cara lebih mudah adalah menggunakan subquery atau mengambil payment method setelahnya.
                    // Untuk sekarang, kita ambil saja payment method dari payment TERAKHIR yang dibuat untuk order itu.
                    // Atau, jika Anda HANYA menyimpan metode pilihan customer di tabel 'payments' (payment_method_gateway), itu lebih sederhana.
                    // Anggap $order->payment_method (accessor) akan mengambil dari payment terkait.
                    $join->on('orders.id', '=', 'payments.order_id')
                        ->whereRaw('payments.id = (select max(id) from payments where payments.order_id = orders.id)'); // Ambil payment record terakhir
                })
                ->select([
                    'orders.id', // Pastikan alias tabel jika ada nama kolom yang sama
                    'orders.order_code',
                    'orders.created_at',
                    'orders.preferred_delivery_date',
                    'orders.delivery_method',
                    'payments.payment_method_gateway as payment_method', // Ambil dari tabel payments
                    'orders.total_amount',
                    'orders.payment_status',
                    'orders.order_status',
                ])
                ->withCount('items');

            return DataTables::of($ordersQuery)
                ->addIndexColumn()
                ->editColumn('created_at', function ($order) {
                    return $order->created_at ? Carbon::parse($order->created_at)->format('d M Y H:i') : '-';
                })
                ->editColumn('preferred_delivery_date', function ($order) {
                    return $order->preferred_delivery_date ? Carbon::parse($order->preferred_delivery_date)->format('d M Y') : ($order->delivery_method == 'pickup' ? 'Ambil di Tempat' : 'Belum Ditentukan');
                })
                ->editColumn('total_amount', function ($order) {
                    return 'Rp' . number_format($order->total_amount, 0, ',', '.');
                })
                ->editColumn('payment_method', function ($order) {
                    // $order->payment_method sekarang berasal dari join tabel payments
                    return ucwords(str_replace('_', ' ', $order->payment_method ?? '-'));
                })
                ->editColumn('payment_status', function ($order) {
                    $status = $order->payment_status ?? 'unknown';
                    $color = 'secondary';
                    if ($status == 'pending') $color = 'warning';
                    elseif ($status == 'paid') $color = 'success';
                    elseif (in_array($status, ['failed', 'cancelled', 'expired', 'deny'])) $color = 'danger';
                    return '<span class="badge bg-light-' . $color . '">' . ucwords(str_replace('_', ' ', $status)) . '</span>';
                })
                ->addColumn('order_status_display', function ($order) {
                    $status = $order->order_status ?? 'unknown';
                    $color = 'secondary';
                    if (in_array($status, ['pending_payment', 'processing', 'awaiting_pickup_payment'])) $color = 'warning';
                    elseif (in_array($status, ['ready_for_pickup', 'out_for_delivery', 'delivered_pending_installation', 'installation_scheduled'])) $color = 'info';
                    elseif ($status == 'completed') $color = 'success';
                    elseif (str_contains($status, 'cancelled')) $color = 'danger';
                    return '<span class="badge bg-light-' . $color . '">' . ucwords(str_replace('_', ' ', $status)) . '</span>';
                })
                ->addColumn('action', function ($order) {
                    if (!$order || !$order->id) {
                        Log::warning("Invalid order object or missing ID in getMyOrdersData.", ['order_object' => $order]);
                        return '<span class="text-danger">Error Data</span>';
                    }
                    $orderHash = Hashids::encode($order->id); // Encode ID secara manual jika hashid tidak otomatis dari model karena join
                    if (empty($orderHash)) {
                        Log::error("HashID kosong untuk Order ID: {$order->id} di getMyOrdersData. Pastikan Trait Hashidable ada di Model Order dan 'id' di-select.");
                        return '<span class="text-danger">Error ID</span>';
                    }
                    $detailUrl = route('customer.orders.show', ['order_hashid' => $orderHash]);
                    $payUrlHtml = '';
                    // Ambil metode pembayaran dari kolom yang di-join
                    if ($order->payment_method === 'qris' && $order->payment_status == 'pending' && !str_contains($order->order_status, 'cancelled')) {
                        $payUrl = route('customer.payment.initiate', ['order_hashid' => $orderHash]);
                        $payUrlHtml = '<a href="' . $payUrl . '" class="btn btn-sm btn-success ms-1" title="Bayar Sekarang (QRIS)"><i class="bi bi-qr-code"></i></a>';
                    }
                    return '<a href="' . $detailUrl . '" class="btn btn-sm btn-outline-info" title="Lihat Detail"><i class="bi bi-eye-fill"></i></a>' . $payUrlHtml;
                })
                ->rawColumns(['payment_status', 'order_status_display', 'action'])
                ->make(true);
        } catch (\Exception $e) {
            Log::error("Error in getMyOrdersData: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return response()->json(['error' => 'Terjadi kesalahan saat mengambil data order.'], 500);
        }
    }

    public function showMyOrder($order_hashid): View|\Illuminate\Http\RedirectResponse
    {
        // ... (showMyOrder tetap sama, pastikan ia mengambil payment_method dari relasi Payment jika perlu ditampilkan) ...
        // Untuk menampilkan payment_method di halaman detail, Anda bisa menggunakan accessor di model Order
        // atau mengambil $order->payments->first()->payment_method_gateway (dengan pengecekan null)
        $decodedOrderId = Hashids::decode($order_hashid);
        if (empty($decodedOrderId)) {
            return redirect()->route('customer.dashboard')->with('error', 'Format ID Order tidak valid.');
        }
        $orderId = $decodedOrderId[0];
        $customer = Auth::guard('customer')->user();
        /** @var \App\Models\Customer $customer */

        try {
            $order = $customer->orders()
                ->with([
                    'items',
                    'items.brand',
                    'items.category',
                    'payments' => fn($q) => $q->orderBy('created_at', 'desc') // Untuk menampilkan riwayat payment
                ])
                ->findOrFail($orderId);

            // Untuk payment method di halaman show:
            // $paymentMethodForDisplay = $order->payment_method; // Menggunakan accessor jika ada
            // atau:
            // $latestPayment = $order->payments->first();
            // $paymentMethodForDisplay = $latestPayment ? $latestPayment->payment_method_gateway : 'Tidak diketahui';

            return view('customer.orders.show', compact('order'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('customer.orders.index')
                ->with('error', 'Order yang Anda cari tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error("Error showing customer order detail: " . $e->getMessage(), ['order_id' => $orderId, 'customer_id' => $customer->id]);
            return redirect()->route('customer.orders.index')
                ->with('error', 'Terjadi kesalahan saat menampilkan detail order.');
        }
    }
}
