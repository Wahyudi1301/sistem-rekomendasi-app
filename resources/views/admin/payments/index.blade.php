@extends('admin.layouts.master')

@section('page-title', 'Laporan Pembayaran Admin')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Laporan Pembayaran</li>
@endsection

@section('content')
    <div class="page-content">
        <section class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Laporan Semua Transaksi Pembayaran</h4>
                    </div>
                    <div class="card-body">
                        @include('admin.partials.alerts')
                        <div class="table-responsive">
                            <table id="admin-payments-table" class="table table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Kode Order</th>
                                        <th>Customer</th>
                                        <th>Ref. Gateway</th>
                                        <th>ID Trans. Gateway</th>
                                        <th>Jumlah</th>
                                        <th>Metode Bayar (Gateway)</th>
                                        <th>Waktu Transaksi</th>
                                        <th>Status Transaksi</th>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('#admin-payments-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.payments.data') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'order_code', // Dari addColumn di controller
                        name: 'order.order_code' // Untuk searching/ordering di sisi server
                    },
                    {
                        data: 'customer_name', // Dari addColumn
                        name: 'order.customer.name'
                    },
                    {
                        data: 'gateway_reference_id_display', // Dari addColumn
                        name: 'gateway_reference_id'
                    },
                    {
                        data: 'gateway_transaction_id_display', // Dari addColumn
                        name: 'gateway_transaction_id'
                    },
                    {
                        data: 'amount', // Dari editColumn
                        name: 'amount'
                    },
                    {
                        data: 'payment_method_gateway_display', // Dari editColumn
                        name: 'payment_method_gateway'
                    },
                    {
                        data: 'transaction_time',
                        name: 'transaction_time'
                    },
                    {
                        data: 'transaction_status',
                        name: 'transaction_status'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [7, 'desc']
                ]
            });
        });
    </script>
@endpush
