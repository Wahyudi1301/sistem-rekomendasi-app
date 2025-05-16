<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order; // Diubah dari Booking
use App\Models\Payment;
use App\Models\Item;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Midtrans\Config as MidtransConfig;
use Midtrans\Notification as MidtransNotification;
use Carbon\Carbon;

class MidtransController extends Controller
{
public function handleNotification(Request $request)
{
    $rawPayload = $request->getContent();
    Log::info('Midtrans Webhook - RAW Payload Received:', ['payload' => $rawPayload]);
    Log::info('Midtrans Webhook - Request Headers:', $request->headers->all());

    $serverKey = config('midtrans.server_key');
    $isProduction = config('midtrans.is_production');

    Log::info('Midtrans Webhook - Using Server Key: ' . substr($serverKey, 0, 10) . '... (masked)');
    Log::info('Midtrans Webhook - Is Production: ' . ($isProduction ? 'true' : 'false'));

    if (empty($serverKey)) {
        Log::error('Midtrans Webhook: Server Key is empty in config. Cannot proceed.');
        return response()->json(['status' => 'error', 'message' => 'Server key configuration missing on our end.'], 200);
    }

    MidtransConfig::$serverKey = $serverKey;
    MidtransConfig::$isProduction = $isProduction;

    $notification = null;
    try {
        Log::info('Midtrans Webhook: Attempting to instantiate MidtransNotification...');
        $notification = new MidtransNotification();
        Log::info('Midtrans Webhook: MidtransNotification instantiated and validated successfully.');
    } catch (\Exception $e) {
        Log::error('Midtrans Webhook - Exception during MidtransNotification instantiation or signature verification: ' . $e->getMessage(), [
            'trace' => substr($e->getTraceAsString(), 0, 1000),
        ]);
        return response()->json(['status' => 'error', 'message' => 'Invalid notification data or signature: ' . $e->getMessage()], 400);
    }

    $transactionStatus = $notification->transaction_status;
    $fraudStatus = $notification->fraud_status ?? null;
    $gatewayReferenceId = $notification->order_id; // Ini adalah order_id yang kita kirim ke Midtrans
    $paymentMethodGateway = $notification->payment_type ?? null; // Sesuai dengan 'payment_type' dari Midtrans
    $gatewayTransactionId = $notification->transaction_id ?? null;
    $transactionTime = $notification->transaction_time ?? null;
    $settlementTime = $notification->settlement_time ?? null;
    // $grossAmountFromMidtrans = $notification->gross_amount ?? null; // Bisa dipakai untuk verifikasi tambahan

    Log::info("Midtrans Webhook - Processing for Gateway Reference ID: {$gatewayReferenceId}, Status: {$transactionStatus}, Fraud: {$fraudStatus}");

    $payment = Payment::where('gateway_reference_id', $gatewayReferenceId)->latest()->first();

    if (!$payment) {
        Log::error("Midtrans Webhook: Payment record not found for gateway_reference_id: {$gatewayReferenceId}. Notification ignored, but sending 200 OK to Midtrans.");
        return response()->json(['status' => 'ok', 'message' => 'Payment record for order_id not found, notification data logged.'], 200);
    }

    $order = $payment->order; // Mengambil relasi order dari model Payment
    if (!$order) {
        Log::error("Midtrans Webhook: Associated order not found for payment_id: {$payment->id}, Gateway Ref ID: {$gatewayReferenceId}.");
        return response()->json(['status' => 'ok', 'message' => 'Associated order not found, notification logged for payment update.'], 200);
    }

    DB::beginTransaction();
    try {
        $payment->gateway_transaction_id = $gatewayTransactionId ?? $payment->gateway_transaction_id;
        $payment->payment_method_gateway = $paymentMethodGateway ?? $payment->payment_method_gateway;
        // Jika ada 'va_numbers' atau 'permata_va_number', Anda bisa menyimpannya di 'payment_channel'
        if (isset($notification->va_numbers) && is_array($notification->va_numbers) && count($notification->va_numbers) > 0) {
            $payment->payment_channel = $notification->va_numbers[0]['bank'] . '_va'; // Contoh: bca_va
        } elseif (isset($notification->permata_va_number)) {
            $payment->payment_channel = 'permata_va';
        }
        $payment->transaction_status = $transactionStatus;
        if ($transactionTime) {
            $payment->transaction_time = Carbon::parse($transactionTime)->setTimezone(config('app.timezone'));
        }
        if ($settlementTime) { // Pastikan kolom settlement_time ada di tabel payments
            $payment->settlement_time = Carbon::parse($settlementTime)->setTimezone(config('app.timezone'));
        }
        $payment->gateway_response_payload = json_decode($request->getContent(), true);
        $payment->save();
        Log::info("Webhook: Payment record ID {$payment->id} updated. Status: {$payment->transaction_status}");

        if (!in_array($order->payment_status, ['paid', 'failed', 'cancelled', 'expired', 'refunded', 'challenge'])) {
            if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
                if (($fraudStatus ?? 'accept') == 'accept') {
                    $order->payment_status = 'paid';
                    // Sesuaikan order_status setelah pembayaran berhasil
                    if ($order->delivery_method == 'pickup') {
                        $order->order_status = 'ready_for_pickup';
                    } else { // delivery
                        $order->order_status = 'processing'; // Atau 'preparing_shipment'
                    }
                    $this->decreaseItemStock($order);
                    Log::info("Webhook: Order {$order->order_code} status updated to PAID and Order Status to {$order->order_status}.");
                } elseif ($fraudStatus == 'challenge') {
                    $order->payment_status = 'challenge';
                    $order->order_status = 'payment_review';
                    Log::info("Webhook: Order {$order->order_code} payment is CHALLENGED.");
                }
            } elseif ($transactionStatus == 'pending') {
                $order->payment_status = 'pending';
                // order_status mungkin tidak perlu diubah jika masih 'pending_payment'
                Log::info("Webhook: Order {$order->order_code} payment is PENDING.");
            } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'])) {
                $order->payment_status = $transactionStatus;
                $order->order_status = 'cancelled_payment_issue'; // Status order dibatalkan karena masalah pembayaran
                Log::info("Webhook: Order {$order->order_code} payment {$transactionStatus}.");
            }
            $order->save();
            Log::info("Webhook: Order record {$order->order_code} updated. Payment Status: {$order->payment_status}, Order Status: {$order->order_status}");
        } else {
            Log::info("Webhook: Order {$order->order_code} already has a final payment status ({$order->payment_status}). Notification for {$transactionStatus} (Gateway Ref ID: {$gatewayReferenceId}) was for an existing payment record update, order status not re-processed.");
        }

        DB::commit();
        Log::info("Midtrans Webhook - Successfully processed and DB committed for Gateway Ref ID: {$gatewayReferenceId}");
        return response()->json(['status' => 'ok', 'message' => 'Notification processed successfully'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Midtrans Webhook - DB Update Error for Gateway Ref ID: {$gatewayReferenceId}. Error: " . $e->getMessage(), ['trace' => substr($e->getTraceAsString(), 0, 1000)]);
        return response()->json(['status' => 'error', 'message' => 'Internal server error during DB update, notification logged.'], 200);
    }
}

protected function decreaseItemStock(Order $order): void // Diubah ke Order
{
    try {
        $order->loadMissing('items');
        foreach ($order->items as $itemPivot) {
            $itemMaster = Item::find($itemPivot->id);
            if ($itemMaster) {
                if ($itemMaster->stock >= $itemPivot->pivot->quantity) {
                    $itemMaster->decrement('stock', $itemPivot->pivot->quantity);
                    Log::info("Webhook: Stock for item ID {$itemMaster->id} ('{$itemMaster->name}') decremented by {$itemPivot->pivot->quantity} for order {$order->order_code}. New stock: {$itemMaster->stock}");
                } else {
                    Log::error("Webhook: CRITICAL - Stock for item ID {$itemMaster->id} ('{$itemMaster->name}') is insufficient ({$itemMaster->stock}) to decrement {$itemPivot->pivot->quantity} for order {$order->order_code}. Order Payment was successful. MANUAL INTERVENTION REQUIRED.");
                }
            } else {
                Log::error("Webhook: Item master with ID {$itemPivot->id} not found during stock decrement for order {$order->order_code}.");
            }
        }
    } catch (\Exception $e) {
        Log::error("Webhook: Exception during stock decrement for order {$order->order_code}. Error: " . $e->getMessage(), ['trace' => substr($e->getTraceAsString(), 0, 500)]);
    }
}
}
