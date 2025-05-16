<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap as MidtransSnap;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:customer');
    }

    public function initiatePayment($order_hashid)
    {
        $decodedOrderId = Hashids::decode($order_hashid);
        if (empty($decodedOrderId)) {
            return redirect()->route('customer.dashboard')->with('error', 'Order untuk pembayaran tidak valid.');
        }
        $orderId = $decodedOrderId[0];
        $customer = Auth::guard('customer')->user();
        /** @var \App\Models\Customer $customer */

        try {
            $order = $customer->orders()->with('items')->findOrFail($orderId);

            if ($order->payment_method !== 'qris') {
                return redirect()->route('customer.orders.show', ['order_hashid' => $order->hashid])
                    ->with('info', 'Metode pembayaran untuk order ini bukan QRIS.');
            }
            if ($order->payment_status !== 'pending') {
                return redirect()->route('customer.orders.show', ['order_hashid' => $order->hashid])
                    ->with('info', 'Pembayaran untuk order ini sudah diproses atau order dibatalkan.');
            }

            $payment = $order->payments()
                ->where('transaction_status', 'pending')
                ->where('payment_method_gateway', 'qris')
                ->latest()
                ->first();

            if (!$payment) {
                Log::error("Payment record for QRIS not found or already processed for order {$order->order_code} during initiation by customer.");
                // Buat record payment baru jika tidak ada yang pending (mungkin karena percobaan sebelumnya gagal total sebelum Snap token dibuat)
                $gatewayReferenceId = $order->order_code . '-QRIS-' . time();
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                    'gateway_reference_id' => $gatewayReferenceId,
                    'payment_method_gateway' => 'qris',
                    'transaction_status' => 'pending',
                    'amount' => $order->total_amount,
                    'expiry_time' => Carbon::now()->addHours(config('midtrans.expiry_duration_hours', 2)),
                ]);
                Log::info("New payment record created for order {$order->order_code} as none pending was found.");
            }


            if ($payment->snap_token && $payment->expiry_time && $payment->expiry_time > now()) {
                Log::info("Using existing valid SnapToken for Order {$order->order_code}. Gateway Ref ID: {$payment->gateway_reference_id}.");
                return redirect()->route('customer.payment.show', ['order_hashid' => $order->hashid])
                    ->with('snap_token', $payment->snap_token);
            }

            $itemDetailsMidtrans = [];
            foreach ($order->items as $itemPivot) {
                $itemDetailsMidtrans[] = [
                    'id'       => $itemPivot->hashid ?? $itemPivot->id, // Fallback ke id jika hashid tidak ada
                    'price'    => (int) $itemPivot->pivot->price_per_item,
                    'quantity' => (int) $itemPivot->pivot->quantity,
                    'name'     => Str::limit($itemPivot->name, 45)
                ];
            }
            if ($order->shipping_cost > 0) {
                $itemDetailsMidtrans[] = ['id' => 'SHIPPING', 'price' => (int) $order->shipping_cost, 'quantity' => 1, 'name' => 'Biaya Pengiriman'];
            }
            if ($order->installation_cost > 0) {
                $itemDetailsMidtrans[] = ['id' => 'INSTALLATION', 'price' => (int) $order->installation_cost, 'quantity' => 1, 'name' => 'Biaya Pemasangan'];
            }
            if (empty($itemDetailsMidtrans) && (int)$order->total_amount > 0) {
                $itemDetailsMidtrans[] = ['id' => $order->order_code, 'price' => (int) $order->total_amount, 'quantity' => 1, 'name' => 'Total Pembelian ' . $order->order_code];
            }

            MidtransConfig::$serverKey = config('midtrans.server_key');
            MidtransConfig::$isProduction = config('midtrans.is_production');
            MidtransConfig::$isSanitized = config('midtrans.is_sanitized');
            MidtransConfig::$is3ds = config('midtrans.is_3ds');

            $gatewayReferenceIdToMidtrans = $payment->gateway_reference_id; // Gunakan yang sudah ada atau baru dibuat

            $midtransParams = [
                'transaction_details' => [
                    'order_id' => $gatewayReferenceIdToMidtrans,
                    'gross_amount' => (int) $order->total_amount,
                ],
                'customer_details' => [
                    'first_name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone_number,
                ],
                'callbacks' => [
                    'finish' => route('customer.payment.finished', $order->hashid)
                ],
                'expiry' => [
                    'start_time' => now()->format('Y-m-d H:i:s O'),
                    'unit' => 'hour',
                    'duration' => config('midtrans.expiry_duration_hours', 2),
                ],
            ];
            if (!empty($itemDetailsMidtrans)) {
                $midtransParams['item_details'] = $itemDetailsMidtrans;
            }

            $snapToken = MidtransSnap::getSnapToken($midtransParams);

            if (!$snapToken) {
                throw new \Exception('Gagal mendapatkan token pembayaran baru dari Midtrans.');
            }

            $payment->snap_token = $snapToken;
            $payment->expiry_time = Carbon::now()->addHours(config('midtrans.expiry_duration_hours', 2));
            $payment->save();

            Log::info("New Midtrans SnapToken generated for Order {$order->order_code}. Gateway Ref ID: {$gatewayReferenceIdToMidtrans}. Expiry updated.");
            return redirect()->route('customer.payment.show', ['order_hashid' => $order->hashid])
                ->with('snap_token', $snapToken);
        } catch (ModelNotFoundException $e) {
            Log::error("Order not found during payment initiation.", ['order_hashid' => $order_hashid, 'customer_id' => $customer->id]);
            return redirect()->route('customer.dashboard')->with('error', 'Order tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Error initiating payment: ' . $e->getMessage(), ['order_hashid' => $order_hashid, 'customer_id' => $customer->id, 'trace' => substr($e->getTraceAsString(), 0, 1000)]);
            return redirect()->route('customer.orders.show', ['order_hashid' => $order_hashid])
                ->with('error', 'Gagal memulai proses pembayaran: ' . $e->getMessage());
        }
    }

    public function showPaymentPage($order_hashid)
    {
        $snapToken = session('snap_token');
        $order = null;
        $decodedOrderId = Hashids::decode($order_hashid);

        if (!empty($decodedOrderId)) {
            $orderId = $decodedOrderId[0];
            $customer = Auth::guard('customer')->user();
            /** @var \App\Models\Customer $customer */
            $order = $customer->orders()->find($orderId);
        }

        if (!$order) {
            return redirect()->route('customer.dashboard')->with('error', 'Order tidak ditemukan.');
        }

        if ($order->payment_method !== 'qris') {
            return redirect()->route('customer.orders.show', ['order_hashid' => $order->hashid])
                ->with('info', 'Metode pembayaran untuk order ini bukan QRIS.');
        }

        if (!$snapToken) {
            if ($order->payment_status == 'pending') {
                $latestPaymentAttempt = $order->payments()
                    ->whereNotNull('snap_token')
                    ->where('transaction_status', 'pending')
                    ->where('payment_method_gateway', 'qris')
                    ->where('expiry_time', '>', now())
                    ->latest()
                    ->first();

                if ($latestPaymentAttempt && $latestPaymentAttempt->snap_token) {
                    $snapToken = $latestPaymentAttempt->snap_token;
                    Log::info("Retrieved snap token from DB for payment page: " . $order->order_code);
                } else {
                    Log::warning("Snap token missing or expired for pending payment. Redirecting to order detail to re-initiate.", ['order_code' => $order->order_code]);
                    return redirect()->route('customer.orders.show', ['order_hashid' => $order->hashid])
                        ->with('warning', 'Sesi pembayaran tidak valid atau kedaluwarsa. Silakan klik tombol bayar lagi dari detail order Anda.');
                }
            } else {
                return redirect()->route('customer.orders.show', ['order_hashid' => $order->hashid])
                    ->with('info', 'Status pembayaran untuk order ini sudah tidak menunggu pembayaran.');
            }
        }
        return view('customer.payment.show', compact('snapToken', 'order'));
    }


    public function paymentFinished(Request $request, $order_hashid)
    {
        Log::info('Midtrans Finish Callback Hit:', ['order_hashid' => $order_hashid, 'query_params' => $request->query()]);

        $status = $request->query('transaction_status');
        $message = 'Proses pembayaran Anda telah diarahkan kembali.';
        $messageType = 'info';

        if ($status === 'settlement' || $status === 'capture') {
            $message = 'Pembayaran Anda berhasil! Status order akan segera diperbarui setelah konfirmasi dari server.';
            $messageType = 'success';
        } elseif ($status === 'pending') {
            $message = 'Pembayaran Anda sedang menunggu konfirmasi dari pihak penyedia layanan.';
            $messageType = 'info';
        } elseif (in_array($status, ['expire', 'cancel', 'deny', 'failure'])) {
            $message = 'Pembayaran Anda dibatalkan, gagal, atau telah kedaluwarsa. Silakan coba lagi atau hubungi support.';
            $messageType = 'error';
        }
        return redirect()->route('customer.orders.show', ['order_hashid' => $order_hashid])
            ->with($messageType, $message);
    }
}
