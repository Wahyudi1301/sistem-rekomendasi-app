@extends('admin.layouts.master')

@section('page-title', 'Edit Biaya Layanan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.service-costs.index') }}">Biaya Layanan</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit: {{ $serviceCost->label }}</li>
@endsection

@section('content')
    <div class="page-heading">
        <h3>Edit Biaya Layanan: {{ $serviceCost->label }}</h3>
    </div>
    <div class="page-content">
        <section class="section">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.service-costs.update', $serviceCost->hashid) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Kode Internal (Name)</label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="{{ $serviceCost->name }}" readonly>
                            <small class="form-text text-muted">Identifier unik, tidak bisa diubah.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="label" class="form-label">Label Tampilan <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('label') is-invalid @enderror" id="label"
                                name="label" value="{{ old('label', $serviceCost->label) }}" required>
                            @error('label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="cost" class="form-label">Biaya (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('cost') is-invalid @enderror" id="cost"
                                name="cost" value="{{ old('cost', $serviceCost->cost) }}" required step="1000"
                                min="0">
                            @error('cost')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Deskripsi (Opsional)</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                rows="3">{{ old('description', $serviceCost->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active"
                                {{ old('is_active', $serviceCost->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Aktifkan Biaya Ini
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="{{ route('admin.service-costs.index') }}" class="btn btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
