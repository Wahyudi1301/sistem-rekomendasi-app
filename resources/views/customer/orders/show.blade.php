@extends('customer.layouts.master')

@section('page-title', 'Detail Order ' . $order->order_code)

@push('styles')
    <style>
        .detail-label {
            font-weight: 600;
            color: #555;
        }

        .item-list img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }

        .status-badge {
            font-size: 0.9em;
        }
    </style>
@endpush

@section('content')
    <div class="page-heading mb-4">
        <h3>Detail Order</h3>
        <p class="text-subtitle text-muted">Informasi lengkap untuk order <span
                class="text-primary fw-bold">{{ $order->order_code }}</span>.</p>
    </div>

    <div class="page-content">
        @include('customer.partials.alerts')

        <section class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h4 class="card-title">Informasi Order</h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-sm-4 detail-label">Kode Order:</div>
                            <div class="col-sm-8 fw-bold">{{ $order->order_code }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 detail-label">Status Pembayaran:</div>
                            <div class="col-sm-8">
                                @php
                                    $paymentStatus = $order->payment_status ?? 'unknown';
                                    $paymentColor = 'secondary';
                                    if ($paymentStatus == 'pending') {
                                        $paymentColor = 'warning';
                                    } elseif ($paymentStatus == 'paid') {
                                        $paymentColor = 'success';
                                    } elseif (in_array($paymentStatus, ['failed', 'cancelled', 'expired', 'deny'])) {
                                        $paymentColor = 'danger';
                                    }
                                @endphp
                                <span
                                    class="badge bg-light-{{ $paymentColor }} status-badge fs-6">{{ ucwords(str_replace('_', ' ', $paymentStatus)) }}</span>
                                @if (
                                    $order->payment_method === 'qris' &&
                                        $order->payment_status == 'pending' &&
                                        !str_contains($order->order_status, 'cancelled'))
                                    <a href="{{ route('customer.payment.initiate', ['order_hashid' => $order->hashid]) }}"
                                        class="btn btn-sm btn-success ms-2">
                                        <i class="bi bi-qr-code"></i> Bayar Sekarang (QRIS)
                                    </a>
                                @elseif (
                                    $order->payment_method === 'cash' &&
                                        $order->payment_status == 'pending' &&
                                        !str_contains($order->order_status, 'cancelled'))
                                    <span class="ms-2 fst-italic text-muted">Menunggu pembayaran tunai di toko.</span>
                                @endif
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 detail-label">Status Order:</div>
                            <div class="col-sm-8">
                                @php
                                    $orderStatus = $order->order_status ?? 'unknown';
                                    $orderColor = 'secondary';
                                    if (
                                        in_array($orderStatus, [
                                            'pending_payment',
                                            'processing',
                                            'awaiting_pickup_payment',
                                        ])
                                    ) {
                                        $orderColor = 'warning';
                                    } elseif (
                                        in_array($orderStatus, [
                                            'ready_for_pickup',
                                            'out_for_delivery',
                                            'delivered_pending_installation',
                                            'installation_scheduled',
                                        ])
                                    ) {
                                        $orderColor = 'info';
                                    } elseif ($orderStatus == 'completed') {
                                        $orderColor = 'success';
                                    } elseif (str_contains($orderStatus, 'cancelled')) {
                                        $orderColor = 'danger';
                                    }
                                @endphp
                                <span
                                    class="badge bg-light-{{ $orderColor }} status-badge fs-6">{{ ucwords(str_replace('_', ' ', $orderStatus)) }}</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 detail-label">Tanggal Order Dibuat:</div>
                            <div class="col-sm-8">{{ $order->created_at->format('d M Y, H:i') }}</div>
                        </div>

                        <hr class="my-3">
                        <h5 class="mb-3">Detail Pengiriman & Pembayaran</h5>
                        <div class="row mb-2">
                            <div class="col-sm-4 detail-label">Metode Pengiriman:</div>
                            <div class="col-sm-8">{{ $order->delivery_method == 'pickup' ? 'Ambil di Tempat' : 'Di Antar' }}
                            </div>
                        </div>
                        @if ($order->delivery_method == 'delivery')
                            <div class="row mb-2">
                                <div class="col-sm-4 detail-label">Opsi Pengantaran:</div>
                                <div class="col-sm-8">
                                    {{ $order->delivery_option == 'delivery_install' ? 'Antar + Pasang' : 'Hanya Antar' }}
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 detail-label">Tanggal Pengiriman:</div>
                                <div class="col-sm-8">
                                    {{ $order->preferred_delivery_date ? $order->preferred_delivery_date->format('d M Y') : '-' }}
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 detail-label">Alamat Pengiriman:</div>
                                <div class="col-sm-8">{{ $order->delivery_address ?: '-' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 detail-label">No. HP Penerima:</div>
                                <div class="col-sm-8">{{ $order->customer_phone_for_delivery ?: '-' }}</div>
                            </div>
                        @endif
                        <div class="row mb-2">
                            <div class="col-sm-4 detail-label">Metode Pembayaran:</div>
                            <div class="col-sm-8">{{ ucwords($order->payment_method) }}</div>
                        </div>

                        <hr class="my-3">
                        <h5 class="mb-3">Rincian Biaya</h5>
                        <div class="row mb-2">
                            <div class="col-sm-4 detail-label">Subtotal Barang:</div>
                            <div class="col-sm-8">Rp{{ number_format($order->total_item_price, 0, ',', '.') }}</div>
                        </div>
                        @if ($order->shipping_cost > 0)
                            <div class="row mb-2">
                                <div class="col-sm-4 detail-label">Biaya Pengiriman:</div>
                                <div class="col-sm-8">Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}</div>
                            </div>
                        @endif
                        @if ($order->installation_cost > 0)
                            <div class="row mb-2">
                                <div class="col-sm-4 detail-label">Biaya Pemasangan:</div>
                                <div class="col-sm-8">Rp{{ number_format($order->installation_cost, 0, ',', '.') }}</div>
                            </div>
                        @endif
                        <div class="row mb-2">
                            <div class="col-sm-4 detail-label">Total Pembayaran:</div>
                            <div class="col-sm-8 text-primary fw-bold fs-5">
                                Rp{{ number_format($order->total_amount, 0, ',', '.') }}</div>
                        </div>

                        @if ($order->customer_notes)
                            <hr class="my-3">
                            <div class="row mb-2">
                                <div class="col-sm-4 detail-label">Catatan dari Anda:</div>
                                <div class="col-sm-8">{{ $order->customer_notes }}</div>
                            </div>
                        @endif
                        @if ($order->admin_notes)
                            <hr class="my-3">
                            <div class="row mb-2">
                                <div class="col-sm-4 detail-label">Catatan dari Admin:</div>
                                <div class="col-sm-8 fst-italic bg-light p-2 rounded">{!! nl2br(e($order->admin_notes)) !!}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h4 class="card-title">Item yang Dipesan</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover item-list">
                                <thead>
                                    <tr>
                                        <th style="width:10%">Gambar</th>
                                        <th>Nama Item</th>
                                        <th>Jumlah</th>
                                        <th>Harga Satuan (saat order)</th>
                                        <th>Subtotal Item</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($order->items as $item)
                                        @php
                                            $pivotData = $item->pivot;
                                            $quantityOrdered = $pivotData->quantity;
                                            $priceWhenOrdered = $pivotData->price_per_item;
                                            $itemSubtotal = $priceWhenOrdered * $quantityOrdered;
                                        @endphp
                                        <tr>
                                            <td>
                                                @if ($item->img && File::exists(public_path('assets/compiled/items/' . $item->img)))
                                                    <img src="{{ asset('assets/compiled/items/' . $item->img) }}"
                                                        alt="{{ $item->name }}">
                                                @else
                                                    <img src="{{ asset('assets/compiled/svg/no-image.svg') }}"
                                                        alt="No image" class="bg-light p-1">
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('customer.catalog.show', ['item_hash' => $item->hashid]) }}"
                                                    class="text-dark text-decoration-none fw-bold">{{ $item->name }}</a><br>
                                                <small class="text-muted">{{ optional($item->brand)->name }} /
                                                    {{ optional($item->category)->name }}</small>
                                            </td>
                                            <td class="text-center">{{ $quantityOrdered }}</td>
                                            <td class="text-end">Rp{{ number_format($priceWhenOrdered, 0, ',', '.') }}</td>
                                            <td class="text-end">Rp{{ number_format($itemSubtotal, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Tidak ada item dalam order
                                                ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4 class="card-title">Riwayat Transaksi Pembayaran</h4>
                    </div>
                    <div class="card-body">
                        @if ($order->payments->isEmpty() && $order->payment_method !== 'cash')
                            <p class="text-muted text-center">Belum ada riwayat pembayaran.</p>
                            @if (
                                $order->payment_method === 'qris' &&
                                    $order->payment_status == 'pending' &&
                                    !str_contains($order->order_status, 'cancelled'))
                                <div class="text-center mt-3">
                                    <a href="{{ route('customer.payment.initiate', ['order_hashid' => $order->hashid]) }}"
                                        class="btn btn-success">
                                        <i class="bi bi-qr-code"></i> Lakukan Pembayaran QRIS
                                    </a>
                                </div>
                            @endif
                        @elseif($order->payments->isEmpty() && $order->payment_method === 'cash' && $order->payment_status == 'pending')
                            <p class="text-muted text-center">Menunggu pembayaran tunai di toko.</p>
                        @elseif($order->payments->isEmpty() && $order->payment_method === 'cash' && $order->payment_status == 'paid')
                            <p class="text-success text-center fw-bold">Pembayaran tunai telah diterima.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach ($order->payments()->orderBy('created_at', 'desc')->get() as $payment)
                                    <li class="list-group-item px-0">
                                        <div class="d-flex justify-content-between">
                                            <span>{{ $payment->transaction_time ? $payment->transaction_time->format('d M Y H:i') : $payment->created_at->format('d M Y H:i') }}</span>
                                            @php
                                                $pStatus = $payment->transaction_status ?? 'unknown';
                                                $pColor = 'secondary';
                                                if ($pStatus == 'pending') {
                                                    $pColor = 'warning';
                                                } elseif (
                                                    $pStatus == 'settlement' ||
                                                    $pStatus == 'capture' ||
                                                    $pStatus == 'paid'
                                                ) {
                                                    $pColor = 'success';
                                                } elseif (in_array($pStatus, ['deny', 'cancel', 'expire', 'failure'])) {
                                                    $pColor = 'danger';
                                                }
                                            @endphp
                                            <span
                                                class="badge bg-light-{{ $pColor }}">{{ ucwords(str_replace('_', ' ', $pStatus)) }}</span>
                                        </div>
                                        <small class="text-muted">Metode:
                                            {{ $payment->payment_method_gateway ? Str::upper($payment->payment_method_gateway) : (Str::upper($order->payment_method) == 'CASH' ? 'Tunai di Toko' : '-') }}</small><br>
                                        @if ($payment->gateway_transaction_id)
                                            <small class="text-muted">ID Transaksi Gateway:
                                                {{ $payment->gateway_transaction_id }}</small><br>
                                        @endif
                                        <small class="text-muted">Referensi Gateway:
                                            {{ $payment->gateway_reference_id ?? '-' }}</small><br>
                                        <span class="fw-bold">Jumlah:
                                            Rp{{ number_format($payment->amount, 0, ',', '.') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
