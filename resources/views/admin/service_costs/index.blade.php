@extends('admin.layouts.master')

@section('page-title', 'Kelola Biaya Layanan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Biaya Layanan</li>
@endsection

@push('styles')
    <style>
        #service-costs-table th,
        #service-costs-table td {
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <h3>Manajemen Biaya Layanan</h3>
                    <p class="text-subtitle text-muted">Atur berbagai biaya layanan seperti pengiriman dan pemasangan.</p>
                </div>
                <div class="col-12 col-md-6 order-md-2 order-first">
                    <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                        <a href="{{ route('admin.service-costs.create') }}" class="btn btn-primary mb-2">
                            <i class="bi bi-plus-lg"></i> Tambah Biaya Baru
                        </a>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="page-content">
        <section class="section">
            <div class="card">
                <div class="card-body">
                    @include('admin.partials.alerts')
                    <div class="table-responsive">
                        <table id="service-costs-table" class="table table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Kode Internal (Name)</th>
                                    <th>Label Tampilan</th>
                                    <th>Biaya (Rp)</th>
                                    <th>Deskripsi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
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
            $('#service-costs-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.service-costs.data') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        width: '5%'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'label',
                        name: 'label'
                    },
                    {
                        data: 'cost',
                        name: 'cost',
                        className: 'text-end'
                    },
                    {
                        data: 'description',
                        name: 'description',
                        orderable: false,
                        render: function(data, type, row) {
                            return data ? data.substr(0, 50) + (data.length > 50 ? '...' : '') :
                            '-';
                        }
                    },
                    {
                        data: 'is_active',
                        name: 'is_active',
                        className: 'text-center'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        width: '15%',
                        className: 'text-center'
                    }
                ],
                order: [
                    [1, 'asc']
                ],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_-_END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data",
                    infoFiltered: "(difilter dari _MAX_ total data)",
                    zeroRecords: "Tidak ada data yang cocok",
                    paginate: {
                        first: "<<",
                        last: ">>",
                        next: ">",
                        previous: "<"
                    },
                    processing: "Sedang memproses..."
                }
            });
        });
    </script>
@endpush
