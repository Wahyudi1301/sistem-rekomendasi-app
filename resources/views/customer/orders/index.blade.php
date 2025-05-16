@extends('customer.layouts.master')

@section('page-title', 'Order Saya')

@push('styles')
    <style>
        .status-badge {
            font-size: 0.9em;
        }
    </style>
@endpush

@section('content')
    <div class="page-heading mb-4">
        <h3>Order Saya</h3>
        <p class="text-subtitle text-muted">Riwayat dan status semua pesanan Anda.</p>
    </div>

    <div class="page-content">
        @include('customer.partials.alerts')

        <section class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4 class="card-title">Daftar Order</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-lg" id="my-orders-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Kode Order</th>
                                        <th>Tgl. Order</th>
                                        <th>Tgl. Pengiriman/Ambil</th>
                                        <th>Metode Pengiriman</th>
                                        <th>Metode Pembayaran</th>
                                        <th>Total</th>
                                        <th>Status Bayar</th>
                                        <th>Status Order</th>
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
            $('#my-orders-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('customer.orders.data') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        width: '5%'
                    },
                    {
                        data: 'order_code',
                        name: 'order_code'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'preferred_delivery_date',
                        name: 'preferred_delivery_date'
                    },
                    {
                        data: 'delivery_method',
                        name: 'delivery_method',
                        render: function(data, type, row) {
                            return data === 'pickup' ? 'Ambil di Tempat' : 'Di Antar';
                        }
                    },
                    {
                        data: 'payment_method',
                        name: 'payment_method'
                    },
                    {
                        data: 'total_amount',
                        name: 'total_amount'
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status'
                    },
                    {
                        data: 'order_status_display',
                        name: 'order_status'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        width: '10%'
                    }
                ],
                order: [
                    [2, 'desc']
                ],
                language: {
                    "sEmptyTable": "Tidak ada data yang tersedia pada tabel ini",
                    "sProcessing": "Sedang memproses...",
                    "sLengthMenu": "Tampilkan _MENU_ entri",
                    "sZeroRecords": "Tidak ditemukan data yang sesuai",
                    "sInfo": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                    "sInfoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
                    "sInfoFiltered": "(disaring dari _MAX_ entri keseluruhan)",
                    "sInfoPostFix": "",
                    "sSearch": "Cari:",
                    "sUrl": "",
                    "oPaginate": {
                        "sFirst": "Pertama",
                        "sPrevious": "Sebelumnya",
                        "sNext": "Selanjutnya",
                        "sLast": "Terakhir"
                    }
                }
            });
        });
    </script>
@endpush
