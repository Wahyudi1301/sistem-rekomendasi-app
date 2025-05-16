@extends('admin.layouts.master')

@section('page-title', 'Tambah Biaya Layanan Baru')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.service-costs.index') }}">Biaya Layanan</a></li>
    <li class="breadcrumb-item active" aria-current="page">Tambah Baru</li>
@endsection

@section('content')
    <div class="page-heading">
        <h3>Tambah Biaya Layanan Baru</h3>
    </div>
    <div class="page-content">
        <section class="section">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.service-costs.store') }}" method="POST">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Kode Internal (Name) <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                name="name" value="{{ old('name') }}" required
                                placeholder="cth: shipping_delivery_only (unik, huruf kecil, underscore)">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Identifier unik untuk sistem, tidak bisa diubah setelah
                                dibuat. Hanya huruf kecil, angka, dan underscore (_).</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="label" class="form-label">Label Tampilan <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('label') is-invalid @enderror" id="label"
                                name="label" value="{{ old('label') }}" required
                                placeholder="cth: Biaya Pengantaran Saja">
                            @error('label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="cost" class="form-label">Biaya (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('cost') is-invalid @enderror" id="cost"
                                name="cost" value="{{ old('cost', 0) }}" required step="1000" min="0">
                            @error('cost')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Deskripsi (Opsional)</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active"
                                {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Aktifkan Biaya Ini
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan Biaya Baru</button>
                        <a href="{{ route('admin.service-costs.index') }}" class="btn btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
