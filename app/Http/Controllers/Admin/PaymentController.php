<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order; // Import Order model
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;

class PaymentController extends Controller
{
    private $editableTransactionStatuses = [
        'pending'    => 'Pending',
        'settlement' => 'Settlement (Berhasil)',
        'capture'    => 'Capture (Berhasil)',
        'paid'       => 'Paid (Manual/Cash)', // Jika admin bisa set ini untuk cash
        'failure'    => 'Failure (Gagal)',
        'expire'     => 'Expire (Kedaluwarsa)',
        'cancel'     => 'Cancel (Dibatalkan)',
        'deny'       => 'Deny (Ditolak Fraud)',
        'refund'     => 'Refund',
        'partial_refund' => 'Partial Refund',
    ];

    public function index(): View
    {
        return view('admin.payments.index');
    }

    public function getData(Request $request)
    {
        // Menggunakan relasi order dari Payment
        $payments = Payment::with(['order.customer', 'order'])
            ->select('payments.*');

        return DataTables::of($payments)
            ->addIndexColumn()
            ->addColumn('order_code', function ($payment) {
                return optional($payment->order)->order_code ?? '<span class="text-muted">N/A</span>';
            })
            ->addColumn('customer_name', function ($payment) {
                return optional(optional($payment->order)->customer)->name ?? '<span class="text-muted">N/A</span>';
            })
            ->addColumn('gateway_reference_id_display', function ($payment) {
                return $payment->gateway_reference_id ?? '-';
            })
            ->addColumn('gateway_transaction_id_display', function ($payment) {
                return $payment->gateway_transaction_id ?? '-';
            })
            ->editColumn('amount', function ($payment) { // Diubah dari gross_amount
                return 'Rp ' . number_format($payment->amount, 0, ',', '.');
            })
            ->editColumn('payment_method_gateway_display', function ($payment) { // Diubah dari payment_type
                return ucwords(str_replace('_', ' ', $payment->payment_method_gateway ?? '-'));
            })
            ->editColumn('transaction_time', function ($payment) {
                return $payment->transaction_time ? Carbon::parse($payment->transaction_time)->format('d M Y, H:i') : '-';
            })
            ->editColumn('transaction_status', function ($payment) {
                $status = $payment->transaction_status ?? 'unknown';
                $color = 'secondary';
                if ($status === 'pending') $color = 'warning';
                elseif (in_array($status, ['settlement', 'capture', 'paid'])) $color = 'success';
                elseif (in_array($status, ['failure', 'expire', 'cancel', 'deny'])) $color = 'danger';
                elseif (in_array($status, ['refund', 'partial_refund'])) $color = 'info';
                return '<span class="badge bg-light-' . $color . '">' . ucwords(str_replace('_', ' ', $status)) . '</span>';
            })
            ->addColumn('action', function ($payment) {
                $showUrl = route('admin.payments.show', $payment->hashid);
                $editUrl = route('admin.payments.edit', $payment->hashid);
                $buttons = '<a href="' . $showUrl . '" class="btn btn-sm btn-outline-success me-1" title="Lihat Detail"><i class="bi bi-eye-fill"></i></a>';
                // Tombol edit hanya jika status belum final (misalnya belum settlement atau refund)
                if (!in_array($payment->transaction_status, ['settlement', 'capture', 'paid', 'refund', 'partial_refund', 'cancel', 'deny'])) {
                    $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-info" title="Edit Status/Notes"><i class="bi bi-pencil-fill"></i></a>';
                }
                return $buttons;
            })
            ->rawColumns(['action', 'transaction_status', 'order_code', 'customer_name'])
            ->make(true);
    }

    public function show(Payment $payment): View
    {
        $payment->load(['order.customer', 'order.items', 'order.items.brand', 'order.items.category']);
        return view('admin.payments.show', compact('payment'));
    }

    public function edit(Payment $payment): View
    {
        $payment->load(['order.customer']);
        $statuses = $this->editableTransactionStatuses;
        return view('admin.payments.edit', compact('payment', 'statuses'));
    }

    public function update(Request $request, Payment $payment)
    {
        $validatedData = $request->validate([
            'transaction_status' => ['required', 'string', Rule::in(array_keys($this->editableTransactionStatuses))],
            // 'notes' // Jika Anda punya kolom notes di tabel payments, validasi di sini
        ]);

        DB::beginTransaction();
        try {
            $oldPaymentTransactionStatus = $payment->transaction_status;
            $newPaymentTransactionStatus = $validatedData['transaction_status'];

            $payment->transaction_status = $newPaymentTransactionStatus;
            // $payment->notes = $validatedData['notes'] ?? $payment->notes; // Jika ada kolom notes

            if (in_array($newPaymentTransactionStatus, ['settlement', 'capture', 'paid']) && is_null($payment->transaction_time)) {
                $payment->transaction_time = now();
                if (is_null($payment->settlement_time)) $payment->settlement_time = now(); // Set settlement time juga
            }
            $payment->save();

            if ($payment->order) {
                $order = $payment->order;
                $oldOrderPaymentStatus = $order->payment_status;

                // Logika update status order berdasarkan status payment
                if (in_array($newPaymentTransactionStatus, ['settlement', 'capture', 'paid'])) {
                    $order->payment_status = 'paid';
                    if (!in_array($order->order_status, ['completed', 'cancelled_by_admin', 'cancelled_payment_issue'])) { // Jangan override status final
                        if ($order->delivery_method == 'pickup') {
                            $order->order_status = 'ready_for_pickup';
                        } else {
                            $order->order_status = 'processing';
                        }
                    }
                    if ($oldOrderPaymentStatus !== 'paid' && $order->payment_status === 'paid') {
                        $this->decreaseItemStockForOrder($order);
                    }
                } elseif ($newPaymentTransactionStatus == 'pending') {
                    // Jika diubah ke pending, mungkin perlu logika rollback status order, tapi hati-hati
                    if ($order->payment_status === 'paid') { // Jika sebelumnya sudah paid
                        // $this->increaseItemStockForOrder($order); // Kembalikan stok jika sebelumnya sudah dikurangi
                    }
                    $order->payment_status = 'pending';
                } elseif (in_array($newPaymentTransactionStatus, ['failure', 'expire', 'cancel', 'deny'])) {
                    if ($order->payment_status === 'paid') { // Jika sebelumnya sudah paid
                        // $this->increaseItemStockForOrder($order);
                    }
                    $order->payment_status = 'failed'; // Atau sesuaikan
                    if (!in_array($order->order_status, ['completed', 'cancelled_by_admin'])) {
                        $order->order_status = 'cancelled_payment_issue';
                    }
                } elseif (in_array($newPaymentTransactionStatus, ['refund', 'partial_refund'])) {
                    if ($order->payment_status === 'paid') {
                        // $this->increaseItemStockForOrder($order);
                    }
                    $order->payment_status = 'refunded'; // Atau 'partially_refunded'
                    // Pertimbangkan status order jika refund
                }
                // Simpan catatan admin di order jika perlu
                // $order->admin_notes = ($order->admin_notes ? $order->admin_notes . "\n" : "") . "Status pembayaran (Payment ID: {$payment->id}) diubah oleh admin ke '{$newPaymentTransactionStatus}' pada " . now()->format('d/m/Y H:i');
                $order->save();
            }

            DB::commit();
            return redirect()->route('admin.payments.index')
                ->with('success', 'Status pembayaran berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin Payment Update Error: ' . $e->getMessage(), ['payment_id' => $payment->id, 'trace' => substr($e->getTraceAsString(), 0, 1000)]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage());
        }
    }

    // Ganti nama method dan parameter ke Order
    protected function decreaseItemStockForOrder(Order $order): void
    {
        try {
            $order->loadMissing('items');
            foreach ($order->items as $itemPivot) {
                $itemMaster = Item::find($itemPivot->id);
                if ($itemMaster) {
                    if ($itemMaster->stock >= $itemPivot->pivot->quantity) {
                        $itemMaster->decrement('stock', $itemPivot->pivot->quantity);
                        Log::info("ADMIN/WEBHOOK: Stock for item ID {$itemMaster->id} ('{$itemMaster->name}') decremented by {$itemPivot->pivot->quantity} for order {$order->order_code}. New stock: {$itemMaster->stock}");
                    } else {
                        Log::error("ADMIN/WEBHOOK: CRITICAL - Stock for item ID {$itemMaster->id} ('{$itemMaster->name}') is insufficient ({$itemMaster->stock}) to decrement {$itemPivot->pivot->quantity} for order {$order->order_code}. MANUAL INTERVENTION REQUIRED.");
                    }
                } else {
                    Log::error("ADMIN/WEBHOOK: Item master with ID {$itemPivot->id} not found during stock decrement for order {$order->order_code}.");
                }
            }
        } catch (\Exception $e) {
            Log::error("ADMIN/WEBHOOK: Exception during stock decrement for order {$order->order_code}. Error: " . $e->getMessage(), ['trace' => substr($e->getTraceAsString(), 0, 500)]);
        }
    }

    // Ganti nama method dan parameter ke Order
    // Method ini mungkin tidak lagi diperlukan jika jual beli (stok tidak kembali kecuali refund barang fisik)
    /*
    protected function increaseItemStockForOrder(Order $order): void
    {
        try {
            $order->loadMissing('items');
            foreach ($order->items as $itemPivot) {
                $itemMaster = Item::find($itemPivot->id);
                if ($itemMaster) {
                    $itemMaster->increment('stock', $itemPivot->pivot->quantity);
                    Log::info("ADMIN/WEBHOOK: Stock for item ID {$itemMaster->id} ('{$itemMaster->name}') incremented by {$itemPivot->pivot->quantity} for order {$order->order_code}. New stock: {$itemMaster->stock}");
                } else {
                    Log::error("ADMIN/WEBHOOK: Item master with ID {$itemPivot->id} not found during stock increment for order {$order->order_code}.");
                }
            }
        } catch (\Exception $e) {
            Log::error("ADMIN/WEBHOOK: Exception during stock increment for order {$order->order_code}. Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }
    */
}
