@extends('admin.layouts.master')

@section('page-title', 'Kelola Semua Order')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Kelola Order</li>
@endsection

@push('styles')
    <style>
        #admin-orders-table th,
        #admin-orders-table td {
            font-size: 0.9rem;
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    <div class="page-content">
        <section class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Daftar Semua Order</h4>
                    </div>
                    <div class="card-body">
                        @include('admin.partials.alerts')
                        <div class="table-responsive">
                            <table id="admin-orders-table" class="table table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Kode Order</th>
                                        <th>Customer</th>
                                        <th>Tgl. Order</th>
                                        <th>Tgl. Kirim/Ambil</th>
                                        <th>Pengiriman</th>
                                        <th>Pembayaran</th>
                                        <th>Total</th>
                                        <th>Status Bayar</th>
                                        <th>Status Order</th>
                                        <th>Diproses Oleh</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#admin-orders-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.orders.data') }}", // Pastikan route ini benar
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        width: '3%'
                    },
                    {
                        data: 'order_code',
                        name: 'orders.order_code', // Tambahkan alias tabel jika nama kolom ambigu setelah join
                        width: '10%'
                    },
                    {
                        data: 'customer_name',
                        name: 'customer.name' // Asumsi relasi customer punya kolom name
                    },
                    {
                        data: 'created_at',
                        name: 'orders.created_at'
                    },
                    {
                        data: 'preferred_delivery_date',
                        name: 'orders.preferred_delivery_date'
                    },
                    {
                        data: 'delivery_method_display', // Kolom virtual dari controller
                        name: 'orders.delivery_method',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'payment_method_display', // Kolom virtual dari controller
                        name: 'payments.payment_method_gateway', // Nama kolom asli setelah join
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'total_amount',
                        name: 'orders.total_amount'
                    },
                    {
                        data: 'payment_status',
                        name: 'orders.payment_status',
                        width: '8%'
                    },
                    {
                        data: 'order_status', // Menggunakan order_status langsung dari controller
                        name: 'orders.order_status',
                        width: '10%'
                    },
                    {
                        data: 'admin_handler',
                        name: 'user.name', // Asumsi relasi user punya kolom name
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        width: '10%' // Sesuaikan lebar jika perlu
                    }
                ],
                order: [
                    [3, 'desc'] // Order by Tgl. Order descending
                ],
                language: {
                    /* ... Opsi Bahasa Indonesia ... */ }
            });
        });
    </script>
@endpush
