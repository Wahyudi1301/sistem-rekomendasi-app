@extends('admin.layouts.master')

{{-- Judul Halaman Dinamis --}}
@section('page-title', $item ? 'Keywords untuk Item: ' . $item->name : 'Kelola Item Keywords')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    {{-- Breadcrumb Dinamis --}}
    @if ($item)
        <li class="breadcrumb-item"><a href="{{ route('admin.items.index') }}">Kelola Items AC</a></li>
        {{-- Link kembali ke item edit --}}
        <li class="breadcrumb-item"><a
                href="{{ route('admin.items.edit', ['item' => $item->hashid ?? $item->id]) }}">{{ Str::limit($item->name, 20) }}</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">Kelola Keywords</li>
    @else
        <li class="breadcrumb-item active" aria-current="page">Kelola Item Keywords</li>
    @endif
@endsection

@section('content')
    <div class="page-content">
        <section class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        {{-- Judul Kartu Dinamis --}}
                        @if ($item)
                            <h4 class="card-title">Daftar Keywords untuk: <strong>{{ $item->name }}</strong></h4>
                            {{-- Tombol Tambah hanya muncul jika ada konteks item --}}
                            <a href="{{ route('admin.keywords.create', ['item_hashid' => $item->hashid ?? $item->id]) }}"
                                class="btn btn-success">
                                <i class="bi bi-plus-lg"></i> Tambah Keyword Manual
                            </a>
                        @else
                            <h4 class="card-title">Kelola Item Keywords</h4>
                            <span class="text-muted">Pilih item dari <a href="{{ route('admin.items.index') }}">daftar
                                    item</a> untuk melihat atau mengelola keywords.</span>
                        @endif
                    </div>
                    <div class="card-body">
                        @include('admin.partials.alerts')

                        @if ($item)
                            <p class="text-muted small">
                                Keyword di bawah ini sebagian besar di-generate otomatis dari deskripsi item.
                                Anda dapat menambahkan atau menghapus keyword secara manual jika diperlukan.
                            </p>
                        @endif

                        <div class="table-responsive">
                            {{-- Tabel hanya ditampilkan jika ada konteks item --}}
                            <table id="keywords-table" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        @if (!$item)
                                            <th>Nama Item</th>
                                        @endif {{-- Tampilkan nama item jika tidak filter --}}
                                        <th>Nama Keyword</th>
                                        <th>Tgl Dibuat</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Data diisi oleh DataTables --}}
                                    {{-- Jika tidak ada $item, tabel akan kosong karena AJAX tidak akan mengembalikan data --}}
                                </tbody>
                            </table>
                        </div>
                        @if ($item)
                            <div class="mt-3">
                                <a href="{{ route('admin.items.edit', ['item' => $item->hashid ?? $item->id]) }}"
                                    class="btn btn-light">
                                    <i class="bi bi-arrow-left"></i> Kembali ke Edit Item
                                </a>
                            </div>
                        @endif
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
            // Hanya inisialisasi DataTables jika ada konteks item
            const itemHashid = @json($item ? $item->hashid ?? $item->id : null);
            if (itemHashid) {
                $('#keywords-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('admin.keywords.data') }}?item_hashid=' +
                    itemHashid, // Sertakan item_hashid
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false,
                            width: '5%'
                        },
                        // Kolom Nama Item tidak diperlukan jika sudah filter by item
                        // { data: 'item_name', name: 'item.name' },
                        {
                            data: 'keyword_name',
                            name: 'keyword_name'
                        },
                        {
                            data: 'created_at',
                            name: 'created_at'
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false,
                            width: '15%'
                        }
                    ],
                    order: [
                        [2, 'desc']
                    ] // Order by created_at
                });
            } else {
                $('#keywords-table').html(
                    '<tbody><tr><td colspan="4" class="text-center text-muted">Pilih item terlebih dahulu untuk melihat keywords.</td></tr></tbody>'
                    );
            }
        });

        // Fungsi delete tetap sama, URL sudah benar dari controller
        function deleteKeyword(deleteUrl) {
            Swal.fire({
                title: 'Yakin Hapus Keyword?',
                text: "Keyword yang dihapus mungkin mempengaruhi hasil rekomendasi!",
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
                            if ($.fn.DataTable.isDataTable('#keywords-table')) { // Cek jika tabel ada
                                $('#keywords-table').DataTable().ajax.reload(null, false);
                            }
                        },
                        error: function(xhr) {
                            let errorMessage = 'Gagal menghapus keyword.';
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
