@extends('customer.layouts.master')

@section('page-title', 'Dashboard Customer')

@push('styles')
    <style>
        .status-badge {
            font-size: 0.85rem;
            padding: .4em .7em;
        }

        .stats-icon i {
            font-size: 2rem;
            /* Sesuaikan ukuran icon jika perlu */
        }
    </style>
@endpush

@section('content')
    <div class="page-heading">
        <h3>Selamat Datang, {{ $customer->name ?? 'Customer' }}!</h3>
        <p class="text-subtitle text-muted">Ini adalah halaman ringkasan aktivitas order Anda.</p>

        @if (isset($errorMessage) && $errorMessage)
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ $errorMessage }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @include('customer.partials.alerts') {{-- Pastikan path ini benar untuk alert customer --}}
    </div>

    <div class="page-content">
        <section class="row">
            <div class="col-12 col-lg-8">
                <div class="row">
                    <div class="col-6 col-lg-6 col-md-6">
                        <div class="card">
                            <div class="card-body px-4 py-4-5">
                                <div class="row">
                                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                        <div class="stats-icon purple mb-2">
                                            {{-- Ganti icon jika perlu, misal: bi-clock-history atau bi-arrow-repeat --}}
                                            <i class="bi bi-arrow-repeat"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                        <h6 class="text-muted font-semibold">Order Aktif & Proses</h6>
                                        <h6 class="font-extrabold mb-0">{{ $activeOrdersCount ?? 0 }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-6 col-md-6">
                        <div class="card">
                            <div class="card-body px-4 py-4-5">
                                <div class="row">
                                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                        <div class="stats-icon green mb-2">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                        <h6 class="text-muted font-semibold">Order Selesai</h6>
                                        <h6 class="font-extrabold mb-0">{{ $completedOrdersCount ?? 0 }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>3 Order Terakhir Anda</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Kode Order</th>
                                                <th>Tgl. Order</th>
                                                <th>Total</th>
                                                <th>Status Bayar</th>
                                                <th>Status Order</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($recentOrders ?? [] as $order)
                                                <tr>
                                                    <td class="text-bold-500">{{ $order->order_code ?? 'N/A' }}</td>
                                                    <td>{{ $order->created_at ? $order->created_at->format('d M Y') : '-' }}
                                                    </td>
                                                    <td>Rp{{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                                    <td>
                                                        @php
                                                            $paymentStatus = $order->payment_status ?? 'unknown';
                                                            $paymentColor = 'secondary';
                                                            if ($paymentStatus == 'pending') {
                                                                $paymentColor = 'warning';
                                                            } elseif ($paymentStatus == 'paid') {
                                                                $paymentColor = 'success';
                                                            } elseif (
                                                                in_array($paymentStatus, [
                                                                    'failed',
                                                                    'cancelled',
                                                                    'expired',
                                                                    'deny',
                                                                ])
                                                            ) {
                                                                $paymentColor = 'danger';
                                                            } elseif ($paymentStatus == 'challenge') {
                                                                $paymentColor = 'info';
                                                            }
                                                        @endphp
                                                        <span
                                                            class="badge bg-light-{{ $paymentColor }} status-badge">{{ ucwords(str_replace('_', ' ', $paymentStatus)) }}</span>
                                                    </td>
                                                    <td>
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
                                                            class="badge bg-light-{{ $orderColor }} status-badge">{{ ucwords(str_replace('_', ' ', $orderStatus)) }}</span>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('customer.orders.show', ['order_hashid' => $order->hashid]) }}"
                                                            class="btn btn-sm btn-outline-info" title="Lihat Detail Order">
                                                            <i class="bi bi-eye-fill"></i>
                                                        </a>
                                                        @if (
                                                            $order->payment_method === 'qris' &&
                                                                $order->payment_status == 'pending' &&
                                                                !str_contains($order->order_status, 'cancelled'))
                                                            <a href="{{ route('customer.payment.initiate', ['order_hashid' => $order->hashid]) }}"
                                                                class="btn btn-sm btn-success ms-1"
                                                                title="Lanjutkan Pembayaran">
                                                                <i class="bi bi-credit-card-fill"></i>
                                                            </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center">Anda belum memiliki riwayat
                                                        order.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if (isset($recentOrders) && $recentOrders->isNotEmpty())
                                    <div class="text-center mt-3">
                                        <a href="{{ route('customer.orders.index') }}">Lihat Semua Order Saya</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Aksi Cepat</h4>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('customer.catalog.index') }}" class="btn btn-outline-primary"> <i
                                    class="bi bi-shop me-2"></i> Lihat Katalog Alat</a>
                            <a href="{{ route('customer.orders.index') }}" class="btn btn-outline-secondary"> <i
                                    class="bi bi-receipt-cutoff me-2"></i> Order Saya</a>
                            <a href="{{ route('customer.profile.edit') }}" class="btn btn-outline-info"> <i
                                    class="bi bi-person-circle me-2"></i> Edit Profil</a>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h4>Butuh Bantuan?</h4>
                    </div>
                    <div class="card-body">
                        <p>Jika Anda mengalami kendala atau memiliki pertanyaan, jangan ragu menghubungi kami.</p>
                        <p>
                            <i class="bi bi-whatsapp me-2"></i>
                            <a href="https://wa.me/6285119478701" target="_blank">0851-1947-8701 (WhatsApp)</a>
                        </p>
                        <p>
                            <i class="bi bi-envelope-fill me-2"></i>
                            <a href="mailto:devkitaid@gmail.com">devkitaid@gmail.com</a>
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
@endpush
