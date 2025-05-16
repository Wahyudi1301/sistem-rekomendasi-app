@extends('admin.layouts.master')

{{-- Judul Halaman, asumsikan $item selalu ada karena dicek di controller --}}
@section('page-title', 'Tambah Keyword untuk: ' . $item->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    {{-- Link kembali ke index keywords DENGAN filter item --}}
    <li class="breadcrumb-item"><a
            href="{{ route('admin.keywords.index', ['item_hashid' => $item->hashid ?? $item->id]) }}">Kelola Keywords</a>
    </li>
    {{-- Link kembali ke item edit --}}
    <li class="breadcrumb-item"><a
            href="{{ route('admin.items.edit', ['item' => $item->hashid ?? $item->id]) }}">{{ Str::limit($item->name, 20) }}</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Tambah Keyword</li>
@endsection

@section('content')
    <div class="page-content">
        <section class="row">
            <div class="col-md-6 col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Form Tambah Keyword untuk Item: <strong>{{ $item->name }}</strong></h4>
                    </div>
                    <div class="card-body">
                        @include('admin.partials.alerts')

                        {{-- Action ke route store keywords --}}
                        <form action="{{ route('admin.keywords.store') }}" method="POST">
                            @csrf
                            {{-- Hidden input untuk mengirim item_hashid --}}
                            <input type="hidden" name="item_hashid" value="{{ $item->hashid ?? $item->id }}">

                            <div class="form-group mb-3">
                                <label for="keyword_name" class="form-label">Nama Keyword <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('keyword_name') is-invalid @enderror"
                                    id="keyword_name" name="keyword_name" value="{{ old('keyword_name') }}" required
                                    autofocus>
                                @error('keyword_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Masukkan satu keyword (kata atau frasa pendek).</small>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Simpan Keyword</button>
                                {{-- Link batal kembali ke index keywords DENGAN filter item --}}
                                <a href="{{ route('admin.keywords.index', ['item_hashid' => $item->hashid ?? $item->id]) }}"
                                    class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
