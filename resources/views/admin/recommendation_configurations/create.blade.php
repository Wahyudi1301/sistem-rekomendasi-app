@extends('admin.layouts.master')

@section('page-title', 'Tambah Parameter Konfigurasi Baru')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.recommendation_configurations.index') }}">Konfigurasi Rekomendasi</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Tambah Parameter</li>
@endsection

@section('content')
    <div class="page-content">
        <section class="row">
            <div class="col-md-8 col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Form Tambah Parameter Konfigurasi Baru</h4>
                    </div>
                    <div class="card-body">
                        @include('admin.partials.alerts')

                        <form action="{{ route('admin.recommendation_configurations.store') }}" method="POST">
                            @csrf
                            <div class="form-group mb-3">
                                <label for="parameter_name" class="form-label">Nama Parameter <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('parameter_name') is-invalid @enderror"
                                    id="parameter_name" name="parameter_name" value="{{ old('parameter_name') }}" required
                                    autofocus placeholder="Contoh: content_based_weight, cb_price_max_value">
                                @error('parameter_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Harus unik dan sesuai dengan yang digunakan
                                    sistem.</small>
                            </div>

                            <div class="form-group mb-3">
                                <label for="parameter_value" class="form-label">Nilai Parameter <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control @error('parameter_value') is-invalid @enderror" id="parameter_value"
                                    name="parameter_value" rows="3" required placeholder="Contoh: 0.7, 15000000, true">{{ old('parameter_value') }}</textarea>
                                @error('parameter_value')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Nilai bisa berupa angka, teks, atau boolean (true/false,
                                    1/0).</small>
                            </div>

                            <div class="form-group mb-3">
                                <label for="description" class="form-label">Deskripsi</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                    rows="2">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Jelaskan fungsi dari parameter ini.</small>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Simpan Parameter</button>
                                <a href="{{ route('admin.recommendation_configurations.index') }}"
                                    class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
