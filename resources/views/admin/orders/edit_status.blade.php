@extends('admin.layouts.master')

@section('page-title', 'Ubah Status Order: ' . $order->order_code)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.orders.index') }}">Kelola Order</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.orders.show', $order->hashid) }}">{{ $order->order_code }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Ubah Status</li>
@endsection

@section('content')
    <div class="page-content">
        <section class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Ubah Status Order: <span class="text-primary">{{ $order->order_code }}</span>
                        </h4>
                    </div>
                    <div class="card-body">
                        @include('admin.partials.alerts')

                        <div class="mb-4 p-3 border rounded bg-light">
                            <h5>Ringkasan Order</h5>
                            <p><strong>Customer:</strong> {{ optional($order->customer)->name ?? 'N/A' }}</p>
                            <p><strong>Metode Pengiriman:</strong>
                                {{ $order->delivery_method == 'pickup' ? 'Ambil di Tempat' : 'Di Antar' }}</p>
                            @php $latestPaymentForInfo = $order->payments()->latest()->first(); @endphp
                            <p><strong>Metode Pembayaran:</strong>
                                {{ ucwords(str_replace('_', ' ', $latestPaymentForInfo->payment_method_gateway ?? ($order->payment_method ?? '-'))) }}
                            </p>
                            <p><strong>Status Pembayaran Saat Ini:</strong> <span
                                    class="fw-bold">{{ ucwords(str_replace('_', ' ', $order->payment_status)) }}</span></p>
                            <p><strong>Status Order Saat Ini:</strong> <span
                                    class="fw-bold">{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</span></p>
                        </div>

                        <form action="{{ route('admin.orders.updateStatus', $order->hashid) }}" method="POST"
                            id="updateOrderStatusForm">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="order_status" class="form-label">Status Order Baru <span
                                        class="text-danger">*</span></label>
                                <select class="form-select @error('order_status') is-invalid @enderror" id="order_status"
                                    name="order_status" required>
                                    <option value="">Pilih status baru...</option>
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ old('order_status', $order->order_status) == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('order_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Opsi untuk konfirmasi pembayaran cash jika ordernya cash dan status payment masih pending --}}
                            @php
                                $pendingCashPayment = $order
                                    ->payments()
                                    ->where('payment_method_gateway', 'cash')
                                    ->where('transaction_status', 'pending')
                                    ->exists();
                            @endphp
                            @if ($pendingCashPayment && $order->payment_status === 'pending')
                                <div class="form-check mb-3 p-3 border rounded alert-info">
                                    <input class="form-check-input" type="checkbox" value="1" id="confirm_cash_payment"
                                        name="confirm_cash_payment" {{ old('confirm_cash_payment') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="confirm_cash_payment">
                                        Konfirmasi Pembayaran Tunai Telah Diterima
                                    </label>
                                    <small class="d-block text-muted">Ceklis ini akan mengubah status pembayaran menjadi
                                        "Paid" dan memproses order.</small>
                                </div>
                            @endif


                            <hr>
                            <div class="mb-3">
                                <label for="admin_notes" class="form-label">Catatan Admin (Opsional)</label>
                                <textarea class="form-control @error('admin_notes') is-invalid @enderror" id="admin_notes" name="admin_notes"
                                    rows="4" placeholder="Tambahkan catatan terkait perubahan status...">{{ old('admin_notes') }}</textarea>
                                @error('admin_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Catatan ini akan ditambahkan ke catatan admin yang sudah
                                    ada (jika ada).</small>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Perubahan
                                </button>
                                <a href="{{ route('admin.orders.show', $order->hashid) }}"
                                    class="btn btn-secondary ms-2">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
