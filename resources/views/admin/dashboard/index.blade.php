@extends('admin.layouts.master')

@section('page-title', Auth::user()->isAdmin() ? 'Admin Dashboard' : 'Staff Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3>Selamat Datang, {{ Auth::user()->name }}!</h3>
                    <p class="text-subtitle text-muted">Ringkasan aktivitas sistem.</p>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="page-content">
        <section class="row">
            <div class="col-12 col-lg-9">
                <div class="row">
                    <div class="col-6 col-lg-3 col-md-6">
                        <div class="card">
                            <div class="card-body px-4 py-4-5">
                                <div class="row">
                                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                        <div class="stats-icon purple mb-2">
                                            <i class="iconly-boldShow bi-box-seam"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                        <h6 class="text-muted font-semibold">Total Items</h6>
                                        <h6 class="font-extrabold mb-0">{{ $totalItems ?? 0 }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (isset($totalCustomers))
                        <div class="col-6 col-lg-3 col-md-6">
                            <div class="card">
                                <div class="card-body px-4 py-4-5">
                                    <div class="row">
                                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                            <div class="stats-icon blue mb-2">
                                                <i class="iconly-boldProfile bi-people-fill"></i>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                            <h6 class="text-muted font-semibold">Total Customers</h6>
                                            <h6 class="font-extrabold mb-0">{{ $totalCustomers ?? 0 }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="col-6 col-lg-3 col-md-6">
                        <div class="card">
                            <div class="card-body px-4 py-4-5">
                                <div class="row">
                                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                        <div class="stats-icon green mb-2">
                                            {{-- Icon bisa diganti, misal: bi-hourglass-split --}}
                                            <i class="bi bi-hourglass-split"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                        <h6 class="text-muted font-semibold">Order Pending</h6>
                                        <h6 class="font-extrabold mb-0">{{ $pendingOrders ?? 0 }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-lg-3 col-md-6">
                        <div class="card">
                            <div class="card-body px-4 py-4-5">
                                <div class="row">
                                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                        <div class="stats-icon orange mb-2">
                                            {{-- Icon bisa diganti, misal: bi-truck --}}
                                            <i class="bi bi-truck"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                        <h6 class="text-muted font-semibold">Order Aktif</h6>
                                        <h6 class="font-extrabold mb-0">{{ $activeOrders ?? 0 }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (isset($totalBrands))
                        <div class="col-6 col-lg-3 col-md-6">
                            <div class="card">
                                <div class="card-body px-4 py-4-5">
                                    <div class="row">
                                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                            <div class="stats-icon red mb-2">
                                                <i class="iconly-boldBookmark bi-tags-fill"></i>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                            <h6 class="text-muted font-semibold">Brands</h6>
                                            <h6 class="font-extrabold mb-0">{{ $totalBrands ?? 0 }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if (isset($totalCategories))
                        <div class="col-6 col-lg-3 col-md-6">
                            <div class="card">
                                <div class="card-body px-4 py-4-5">
                                    <div class="row">
                                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start ">
                                            <div class="stats-icon red mb-2">
                                                <i class="iconly-boldBookmark bi-bookmark-fill"></i>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                            <h6 class="text-muted font-semibold">Categories</h6>
                                            <h6 class="font-extrabold mb-0">{{ $totalCategories ?? 0 }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="col-6 col-lg-3 col-md-6">
                        <div class="card">
                            <div class="card-body px-4 py-4-5">
                                <div class="row">
                                    <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                        <div class="stats-icon bg-success text-white mb-2">
                                            <i class="bi bi-check2-circle"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                        <h6 class="text-muted font-semibold">Order Selesai</h6>
                                        <h6 class="font-extrabold mb-0">{{ $completedOrders ?? 0 }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (isset($totalUsers))
                        <div class="col-6 col-lg-3 col-md-6">
                            <div class="card">
                                <div class="card-body px-4 py-4-5">
                                    <div class="row">
                                        <div class="col-md-4 col-lg-12 col-xl-12 col-xxl-5 d-flex justify-content-start">
                                            <div class="stats-icon dark mb-2">
                                                <i class="iconly-boldProfile bi-person-lines-fill"></i>
                                            </div>
                                        </div>
                                        <div class="col-md-8 col-lg-12 col-xl-12 col-xxl-7">
                                            <h6 class="text-muted font-semibold">Total Users</h6>
                                            <h6 class="font-extrabold mb-0">{{ $totalUsers ?? 0 }}</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>5 Order Terbaru</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-lg">
                                        <thead>
                                            <tr>
                                                <th>Kode Order</th>
                                                <th>Customer</th>
                                                <th>Tgl. Order</th>
                                                <th>Metode Kirim</th>
                                                <th>Status Bayar</th>
                                                <th>Status Order</th>
                                                <th>Item (Jumlah)</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($recentOrders as $order)
                                                <tr>
                                                    <td class="text-bold-500">
                                                        <a
                                                            href="{{ route('admin.orders.show', $order->hashid) }}">{{ $order->order_code ?? 'N/A' }}</a>
                                                    </td>
                                                    <td>{{ optional($order->customer)->name ?? 'N/A' }}</td>
                                                    <td class="text-bold-500">
                                                        {{ $order->created_at ? $order->created_at->format('d M Y H:i') : '-' }}
                                                    </td>
                                                    <td>{{ $order->delivery_method == 'pickup' ? 'Ambil di Tempat' : 'Di Antar' }}
                                                    </td>
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
                                                            class="badge bg-light-{{ $paymentColor }}">{{ ucwords(str_replace('_', ' ', $paymentStatus)) }}</span>
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
                                                                    'payment_review',
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
                                                            class="badge bg-light-{{ $orderColor }}">{{ ucwords(str_replace('_', ' ', $orderStatus)) }}</span>
                                                    </td>
                                                    <td>
                                                        {{ $order->items_count ?? $order->items->count() }} Item
                                                        ({{ $order->items->sum('pivot.quantity') }} unit)
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('admin.orders.show', $order->hashid) }}"
                                                            class="btn btn-sm btn-outline-info" title="Detail"><i
                                                                class="bi bi-eye-fill"></i></a>
                                                        {{-- Tombol Edit Status Order oleh Admin --}}
                                                        {{-- <a href="{{ route('admin.orders.editStatus', $order->hashid) }}" class="btn btn-sm btn-outline-primary ms-1" title="Ubah Status Order"><i class="bi bi-pencil-square"></i></a> --}}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="text-center">Belum ada data order terbaru.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-3">
                <div class="card">
                    <div class="card-header">
                        <h4>Ringkasan Pembayaran</h4>
                    </div>
                    <div class="card-body">
                        @if (isset($verifiedPayments))
                            <div class="d-flex align-items-center mb-2">
                                <div class="stats-icon green me-3">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted font-semibold mb-0">Verified</h6>
                                    <h6 class="font-extrabold mb-0">{{ $verifiedPayments }}</h6>
                                </div>
                            </div>
                        @endif
                        @if (isset($pendingPayments))
                            <div class="d-flex align-items-center">
                                <div class="stats-icon yellow me-3">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted font-semibold mb-0">Pending</h6>
                                    <h6 class="font-extrabold mb-0">{{ $pendingPayments }}</h6>
                                </div>
                            </div>
                        @endif
                        @if (!isset($verifiedPayments) && !isset($pendingPayments))
                            <p class="text-muted">Data pembayaran tidak tersedia.</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
