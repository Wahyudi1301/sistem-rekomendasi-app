@extends('admin.layouts.master')

@section('page-title', 'Detail Order Admin: ' . $order->order_code)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.orders.index') }}">Kelola Order</a></li>
    <li class="breadcrumb-item active" aria-current="page">Detail: {{ $order->order_code }}</li>
@endsection

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
            border-radius: .25rem;
            border: 1px solid #eee;
        }

        .status-badge {
            font-size: 0.85rem;
            padding: .4em .7em;
        }

        .admin-notes-display {
            white-space: pre-wrap;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #e9ecef;
            max-height: 200px;
            overflow-y: auto;
            font-size: 0.9rem;
        }

        .card-title {
            margin-bottom: 0;
        }

        .list-group-item {
            border-left: 0;
            border-right: 0;
        }

        .list-group-item:first-child {
            border-top: 0;
        }

        .list-group-item:last-child {
            border-bottom: 0;
        }
    </style>
@endpush

@section('content')
    <div class="page-heading">
        <div class="d-flex justify-content-between align-items-center">
            <h3>Detail Order: <span class="text-primary">{{ $order->order_code }}</span></h3>
            <div>
                @if (!in_array($order->order_status, ['completed', 'cancelled_by_admin', 'cancelled_payment_issue']))
                    <a href="{{ route('admin.orders.editStatus', $order->hashid) }}" class="btn btn-primary me-1">
                        <i class="bi bi-pencil-square"></i> Ubah Status Order
                    </a>
                @endif
                <a href="{{ route('admin.orders.print', $order->hashid) }}" class="btn btn-secondary" target="_blank">
                    <i class="bi bi-printer-fill"></i> Cetak Invoice
                </a>
            </div>
        </div>
        <p class="text-subtitle text-muted mt-1 mb-3">Customer: {{ optional($order->customer)->name ?? 'N/A' }}</p>
    </div>

    <div class="page-content">
        @include('admin.partials.alerts')

        <section class="row">
            <div class="col-lg-8 col-md-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h4 class="card-title">Informasi Order</h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-sm-4 col-md-3 detail-label">Kode Order:</div>
                            <div class="col-sm-8 col-md-9 fw-bold">{{ $order->order_code }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 col-md-3 detail-label">Customer:</div>
                            <div class="col-sm-8 col-md-9">{{ optional($order->customer)->name }}
                                ({{ optional($order->customer)->email ?? 'Email tidak tersedia' }})</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 col-md-3 detail-label">Status Pembayaran:</div>
                            <div class="col-sm-8 col-md-9">
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
                                    class="badge bg-light-{{ $paymentColor }} status-badge">{{ ucwords(str_replace('_', ' ', $paymentStatus)) }}</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 col-md-3 detail-label">Status Order:</div>
                            <div class="col-sm-8 col-md-9">
                                @php
                                    $orderStatus = $order->order_status ?? 'unknown';
                                    $oColor = 'secondary';
                                    if (
                                        in_array($orderStatus, [
                                            'pending_payment',
                                            'processing',
                                            'awaiting_pickup_payment',
                                            'payment_review',
                                        ])
                                    ) {
                                        $oColor = 'warning';
                                    } elseif (
                                        in_array($orderStatus, [
                                            'ready_for_pickup',
                                            'out_for_delivery',
                                            'delivered',
                                            'installation_scheduled',
                                        ])
                                    ) {
                                        $oColor = 'info';
                                    } elseif ($orderStatus == 'completed') {
                                        $oColor = 'success';
                                    } elseif (str_contains($orderStatus, 'cancelled')) {
                                        $oColor = 'danger';
                                    }
                                @endphp
                                <span
                                    class="badge bg-light-{{ $oColor }} status-badge">{{ ucwords(str_replace('_', ' ', $orderStatus)) }}</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 col-md-3 detail-label">Tanggal Order Dibuat:</div>
                            <div class="col-sm-8 col-md-9">{{ $order->created_at->format('d M Y, H:i') }} WIB</div>
                        </div>

                        <hr class="my-3">
                        <h5 class="mb-3">Detail Pengiriman & Pembayaran</h5>
                        <div class="row mb-2">
                            <div class="col-sm-4 col-md-3 detail-label">Metode Pengiriman:</div>
                            <div class="col-sm-8 col-md-9">
                                {{ $order->delivery_method == 'pickup' ? 'Ambil di Tempat' : 'Di Antar' }}</div>
                        </div>
                        @if ($order->delivery_method == 'delivery')
                            <div class="row mb-2">
                                <div class="col-sm-4 col-md-3 detail-label">Opsi Pengantaran:</div>
                                <div class="col-sm-8 col-md-9">
                                    {{ $order->delivery_option == 'delivery_install' ? 'Antar + Pasang' : 'Hanya Antar' }}
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 col-md-3 detail-label">Tgl. Pengiriman Diinginkan:</div>
                                <div class="col-sm-8 col-md-9">
                                    {{ $order->preferred_delivery_date ? $order->preferred_delivery_date->format('d M Y') : '-' }}
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 col-md-3 detail-label">Alamat Pengiriman:</div>
                                <div class="col-sm-8 col-md-9">
                                    {{ $order->delivery_address ?: (optional($order->customer)->address ?: '-') }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-4 col-md-3 detail-label">No. HP Penerima:</div>
                                <div class="col-sm-8 col-md-9">
                                    {{ $order->customer_phone_for_delivery ?: (optional($order->customer)->phone_number ?: '-') }}
                                </div>
                            </div>
                        @endif
                        @php $latestPaymentForInfo = $order->payments()->latest()->first(); @endphp
                        <div class="row mb-2">
                            <div class="col-sm-4 col-md-3 detail-label">Metode Pembayaran:</div>
                            <div class="col-sm-8 col-md-9">
                                {{ ucwords(str_replace('_', ' ', $latestPaymentForInfo->payment_method_gateway ?? 'Belum ada data')) }}
                            </div>
                        </div>

                        <hr class="my-3">
                        <h5 class="mb-3">Rincian Biaya</h5>
                        <div class="row mb-2">
                            <div class="col-sm-4 col-md-3 detail-label">Subtotal Barang:</div>
                            <div class="col-sm-8 col-md-9">Rp{{ number_format($order->total_item_price, 0, ',', '.') }}
                            </div>
                        </div>
                        @if ($order->shipping_cost > 0)
                            <div class="row mb-2">
                                <div class="col-sm-4 col-md-3 detail-label">Biaya Pengiriman:</div>
                                <div class="col-sm-8 col-md-9">Rp{{ number_format($order->shipping_cost, 0, ',', '.') }}
                                </div>
                            </div>
                        @endif
                        @if ($order->installation_cost > 0)
                            <div class="row mb-2">
                                <div class="col-sm-4 col-md-3 detail-label">Biaya Pemasangan:</div>
                                <div class="col-sm-8 col-md-9">
                                    Rp{{ number_format($order->installation_cost, 0, ',', '.') }}</div>
                            </div>
                        @endif
                        <div class="row mb-2">
                            <div class="col-sm-4 col-md-3 detail-label">Total Pembayaran:</div>
                            <div class="col-sm-8 col-md-9 text-primary fw-bold fs-5">
                                Rp{{ number_format($order->total_amount, 0, ',', '.') }}</div>
                        </div>

                        @if ($order->customer_notes)
                            <hr class="my-3">
                            <div class="row mb-2">
                                <div class="col-sm-4 col-md-3 detail-label">Catatan Customer:</div>
                                <div class="col-sm-8 col-md-9 fst-italic">{{ $order->customer_notes }}</div>
                            </div>
                        @endif
                        @if ($order->admin_notes)
                            <hr class="my-3">
                            <div class="row mb-2">
                                <div class="col-sm-4 col-md-3 detail-label">Catatan Admin:</div>
                                <div class="col-sm-8 col-md-9 admin-notes-display">{!! nl2br(e($order->admin_notes)) !!}</div>
                            </div>
                        @endif
                        @if ($order->user)
                            {{-- Admin yang memproses --}}
                            <hr class="my-3">
                            <div class="row mb-2">
                                <div class="col-sm-4 col-md-3 detail-label">Terakhir Diproses Oleh:</div>
                                <div class="col-sm-8 col-md-9">{{ $order->user->name }} <small class="text-muted">(ID:
                                        {{ $order->user->id }})</small></div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h4 class="card-title">Item yang Dipesan ({{ $order->items->count() }} item)</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover item-list">
                                <thead>
                                    <tr>
                                        <th style="width:10%">Gambar</th>
                                        <th>Nama Item</th>
                                        <th class="text-center">Jumlah</th>
                                        <th class="text-end">Harga Satuan</th>
                                        <th class="text-end">Subtotal Item</th>
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
                                                @if (Route::has('admin.items.edit'))
                                                    <a href="{{ route('admin.items.edit', $item->hashid) }}"
                                                        target="_blank"
                                                        class="text-dark text-decoration-none fw-bold">{{ $item->name }}</a>
                                                @else
                                                    <span class="text-dark fw-bold">{{ $item->name }}</span>
                                                @endif
                                                <br><small class="text-muted">{{ optional($item->brand)->name }} /
                                                    {{ optional($item->category)->name }}</small>
                                            </td>
                                            <td class="text-center">{{ $quantityOrdered }}</td>
                                            <td class="text-end">Rp{{ number_format($priceWhenOrdered, 0, ',', '.') }}
                                            </td>
                                            <td class="text-end">Rp{{ number_format($itemSubtotal, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">Tidak ada item dalam
                                                order ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header py-3">
                        <h4 class="card-title">Riwayat Transaksi Pembayaran</h4>
                    </div>
                    <div class="card-body">
                        @if ($order->payments->isEmpty())
                            <p class="text-muted text-center py-3">Belum ada riwayat pembayaran.</p>
                            @if (
                                $latestPayment &&
                                    $latestPayment->payment_method_gateway === 'qris' &&
                                    $order->payment_status == 'pending' &&
                                    !str_contains($order->order_status, 'cancelled'))
                                <div class="text-center mt-3">
                                    {{-- Tombol bayar QRIS jika diperlukan oleh admin (jarang) --}}
                                </div>
                            @elseif(
                                $latestPayment &&
                                    $latestPayment->payment_method_gateway === 'cash' &&
                                    $order->payment_status == 'pending' &&
                                    !str_contains($order->order_status, 'cancelled'))
                                <p class="alert alert-warning text-center">Menunggu konfirmasi pembayaran tunai.</p>
                                {{-- Tombol untuk admin konfirmasi pembayaran cash bisa diletakkan di halaman edit status --}}
                            @endif
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach ($order->payments as $payment)
                                    <li class="list-group-item px-0 py-3">
                                        <div class="d-flex justify-content-between">
                                            <span
                                                class="fw-semibold">{{ $payment->transaction_time ? $payment->transaction_time->format('d M Y, H:i') : $payment->created_at->format('d M Y, H:i') }}</span>
                                            @php
                                                $pStatus = $payment->transaction_status ?? 'unknown';
                                                $pColor = 'secondary';
                                                if ($pStatus == 'pending') {
                                                    $pColor = 'warning';
                                                } elseif (in_array($pStatus, ['settlement', 'capture', 'paid'])) {
                                                    $pColor = 'success';
                                                } elseif (in_array($pStatus, ['deny', 'cancel', 'expire', 'failure'])) {
                                                    $pColor = 'danger';
                                                }
                                            @endphp
                                            <span
                                                class="badge bg-light-{{ $pColor }} status-badge">{{ ucwords(str_replace('_', ' ', $pStatus)) }}</span>
                                        </div>
                                        <small class="text-muted d-block">Metode:
                                            {{ Str::upper($payment->payment_method_gateway ?? '-') }}
                                            {{ $payment->payment_channel ? '(' . Str::upper(str_replace('_', ' ', $payment->payment_channel)) . ')' : '' }}</small>
                                        @if ($payment->gateway_transaction_id)
                                            <small class="text-muted d-block">ID Transaksi Gateway:
                                                {{ $payment->gateway_transaction_id }}</small>
                                        @endif
                                        @if ($payment->gateway_reference_id)
                                            <small class="text-muted d-block">Referensi Gateway:
                                                {{ $payment->gateway_reference_id }}</small>
                                        @endif
                                        <span class="fw-bold d-block mt-1">Jumlah:
                                            Rp{{ number_format($payment->amount, 0, ',', '.') }}</span>
                                        {{-- Detail payment link untuk Admin --}}
                                        @if (Route::has('admin.payments.show') && $payment->hashid)
                                            <a href="{{ route('admin.payments.show', $payment->hashid) }}"
                                                class="btn btn-sm btn-outline-secondary mt-2"><i
                                                    class="bi bi-receipt"></i> Detail Payment</a>
                                        @endif
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
