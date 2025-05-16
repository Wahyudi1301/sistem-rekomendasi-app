@extends('admin.layouts.master') {{-- Sesuaikan dengan layout admin Anda --}}

@section('page-title', 'Kelola Items AC') {{-- Ganti menjadi Items AC --}}

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">Kelola Items AC</li>
@endsection

@section('content')
    <div class="page-content">
        <section class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Daftar Items AC</h4> {{-- Ganti judul --}}
                        <a href="{{ route('admin.items.create') }}" class="btn btn-success">
                            <i class="bi bi-plus-lg"></i> Tambah Item AC
                        </a>
                    </div>
                    <div class="card-body">
                        @include('admin.partials.alerts')

                        <div class="table-responsive">
                            <table id="items-table" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Gambar</th>
                                        <th>Nama Item</th>
                                        <th>Kategori</th>
                                        <th>Brand</th>
                                        <th>Harga Jual</th> {{-- Ganti dari Harga Sewa --}}
                                        <th>Stok</th>
                                        <th>Status</th>
                                        <th>Tgl Dibuat</th>
                                        <th width="20%">Action</th> {{-- Mungkin perlu lebih lebar untuk 3 tombol --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Data diisi oleh DataTables --}}
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
    <script>
        $(document).ready(function() {
            $('#items-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('admin.items.data') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        width: '5%'
                    },
                    {
                        data: 'image_display',
                        name: 'image_display',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'category_name',
                        name: 'category.name'
                    },
                    {
                        data: 'brand_name',
                        name: 'brand.name'
                    },
                    {
                        data: 'price',
                        name: 'price'
                    }, // Pastikan ini 'price' bukan 'rental_price'
                    {
                        data: 'stock',
                        name: 'stock'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    } // Lebar diatur di th
                ],
                order: [
                    [8, 'desc']
                ]
            });
        });


        function deleteItem(deleteUrl) {
            Swal.fire({
                title: 'Yakin Hapus Item?',
                text: "Data item yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: deleteUrl, // URL sudah berisi hashid dari controller
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire('Terhapus!', response.message, 'success');
                            $('#items-table').DataTable().ajax.reload(null,
                                false); // Refresh tanpa reset paging
                        },
                        error: function(xhr) {
                            let errorMessage = 'Gagal menghapus item.';
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMessage = xhr.responseJSON.error;
                            }
                            console.error("Delete Error:", xhr.responseJSON); // Log error detail
                            Swal.fire('Error!', errorMessage, 'error');
                        }
                    });
                }
            });
        }
    </script>
@endpush
