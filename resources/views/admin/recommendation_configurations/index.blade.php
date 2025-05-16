@extends('admin.layouts.master') {{-- Sesuaikan dengan layout admin Anda --}}

@section('page-title', 'Kelola Konfigurasi Rekomendasi')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Konfigurasi Rekomendasi</li>
@endsection

@section('content')
    <div class="page-content">
        <section class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Daftar Parameter Konfigurasi Rekomendasi</h4>
                        <a href="{{ route('admin.recommendation_configurations.create') }}" class="btn btn-success">
                            <i class="bi bi-plus-lg"></i> Tambah Parameter
                        </a>
                    </div>
                    <div class="card-body">
                        @include('admin.partials.alerts') {{-- Pastikan partial ini ada --}}
                        <p class="text-muted small">
                            Parameter ini mengatur bagaimana sistem rekomendasi bekerja. Ubah dengan hati-hati.
                        </p>
                        <div class="table-responsive">
                            <table id="configurations-table" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Parameter</th>
                                        <th>Nilai Parameter</th>
                                        <th>Deskripsi</th>
                                        <th>Terakhir Diubah</th>
                                        {{-- Kolom 'Oleh' dihapus --}}
                                        <th width="15%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Data akan diisi oleh DataTables --}}
                                </tbody>
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
    {{-- Pastikan jQuery dan DataTables JS sudah di-load di master layout Anda --}}
    <script>
        $(document).ready(function() {
            $('#configurations-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.recommendation_configurations.data') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        width: '5%'
                    },
                    {
                        data: 'parameter_name',
                        name: 'parameter_name'
                    },
                    {
                        data: 'parameter_value',
                        name: 'parameter_value',
                        orderable: false
                    },
                    {
                        data: 'description',
                        name: 'description',
                        orderable: false
                    },
                    {
                        data: 'updated_at',
                        name: 'updated_at'
                    },
                    // Kolom 'updated_by' dihapus
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [1, 'asc']
                ] // Default order by parameter_name ascending
            });
        });

        function deleteConfiguration(deleteUrl) {
            Swal.fire({
                title: 'Yakin Hapus Parameter Konfigurasi?',
                text: "Perubahan ini dapat mempengaruhi cara kerja sistem rekomendasi!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: deleteUrl,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire('Terhapus!', response.message, 'success');
                            $('#configurations-table').DataTable().ajax.reload(null, false);
                        },
                        error: function(xhr) {
                            let errorMessage = 'Gagal menghapus parameter.';
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMessage = xhr.responseJSON.error;
                            }
                            Swal.fire('Error!', errorMessage, 'error');
                        }
                    });
                }
            });
        }
    </script>
@endpush
