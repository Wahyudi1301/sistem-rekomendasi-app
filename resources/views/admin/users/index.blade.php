{{-- resources/views/admin/users/index.blade.php --}}
@extends('admin.layouts.master')

@section('page-title', 'Kelola Semua Users') {{-- Judul diubah agar lebih sesuai --}}

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Kelola Users</li>
@endsection

@push('styles')
    {{-- DataTables CSS --}}
    <style>
        /* Tambahkan style jika perlu */
    </style>
@endpush

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-6 order-md-1 order-last">
                    <p class="text-subtitle text-muted">Daftar semua pengguna dengan role Admin dan Staff.</p>
                </div>

            </div>
        </div>
    </div>

    <div class="page-content">
        <section class="section">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Daftar Users</h4>
                        {{-- Tombol Tambah User bisa dikontrol dengan Gate jika perlu --}}
                        @can('manage-users') {{-- Atau Gate lain yang lebih spesifik untuk create user --}}
                            <a href="{{ route('admin.users.create') }}" class="btn btn-success icon-left">
                                <i class="bi bi-person-plus-fill"></i> Tambah User Baru
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @include('admin.partials.alerts')
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="admin-users-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>      {{-- <-- PASTIKAN HEADER INI ADA --}}
                                    <th>Telepon</th>
                                    <th>Gender</th>
                                    <th>Status</th>
                                    <th>Tgl Dibuat</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- DataTables akan mengisi ini --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    {{-- DataTables JS --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> {{-- Jika menggunakan SweetAlert --}}
    <script>
        $(document).ready(function() {
            $('#admin-users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.users.data') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%' },
                    { data: 'name', name: 'name' },
                    { data: 'email', name: 'email' },
                    { data: 'role', name: 'role' }, // <-- PASTIKAN KOLOM INI ADA & MENGGUNAKAN ACCESSOR
                    { data: 'phone_number', name: 'phone_number', orderable: false, searchable: false },
                    { data: 'gender', name: 'gender', orderable: false },
                    { data: 'status', name: 'status' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, width: '15%' }
                ],
                order: [[1, 'asc']], // Default order by name ascending
                language: { /* ... Opsi Bahasa Indonesia ... */ }
            });

            // Event listener untuk tombol hapus dengan SweetAlert (jika digunakan)
            $('#admin-users-table').on('click', '.btn-delete-user', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const userName = $(this).closest('tr').find('td:nth-child(2)').text();

                Swal.fire({
                    title: `Yakin hapus user "${userName}"?`,
                    text: "Tindakan ini tidak dapat dibatalkan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d', // Lebih gelap dari #3085d6
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger mx-1',
                        cancelButton: 'btn btn-secondary mx-1'
                    },
                    buttonsStyling: false // Nonaktifkan styling default SweetAlert agar class Bootstrap bekerja
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
