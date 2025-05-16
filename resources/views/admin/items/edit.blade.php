@extends('admin.layouts.master')

@section('page-title', 'Edit Item AC: ' . $item->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.items.index') }}">Kelola Items AC</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit Item AC</li>
@endsection

@section('content')
    <div class="page-content">
        <section class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Form Edit Item AC: {{ $item->name }}</h4>
                    </div>
                    <div class="card-body">
                        @include('admin.partials.alerts')

                        <form action="{{ route('admin.items.update', $item->hashid ?? $item->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                {{-- Kolom Kiri --}}
                                <div class="col-md-8">
                                    <div class="form-group mb-3">
                                        <label for="name" class="form-label">Nama Item AC <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $item->name) }}" required autofocus>
                                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="sku" class="form-label">SKU (Kode Item)</label>
                                        <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku', $item->sku) }}">
                                        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                                                <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                                                    <option value="" disabled>Pilih Kategori...</option>
                                                    @foreach ($categories as $id => $name)
                                                        <option value="{{ $id }}" {{ old('category_id', $item->category_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label for="brand_id" class="form-label">Brand <span class="text-danger">*</span></label>
                                                <select class="form-select @error('brand_id') is-invalid @enderror" id="brand_id" name="brand_id" required>
                                                    <option value="" disabled>Pilih Brand...</option>
                                                    @foreach ($brands as $id => $name)
                                                        <option value="{{ $id }}" {{ old('brand_id', $item->brand_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('brand_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label for="price" class="form-label">Harga Jual (Rp) <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $item->price) }}" required min="0" step="1000">
                                                @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label for="stock" class="form-label">Stok <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control @error('stock') is-invalid @enderror" id="stock" name="stock" value="{{ old('stock', $item->stock) }}" required min="0">
                                                @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="description" class="form-label">Deskripsi Lengkap (Untuk Rekomendasi)</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="6">{{ old('description', $item->description) }}</textarea>
                                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- Kolom Kanan --}}
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="status" class="form-label">Status Item <span class="text-danger">*</span></label>
                                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                            @foreach ($statuses as $key => $value)
                                                 <option value="{{ $key }}" {{ old('status', $item->status) == $key ? 'selected' : '' }}
                                                    {{-- Jika status saat ini RENTED (atau status otomatis lainnya), dan bukan opsi yg dipilih, disable opsi lain --}}
                                                    {{ ($item->status === 'rented' && $key !== 'rented' && !array_key_exists($item->status, $statuses)) ? 'disabled' : '' }}>
                                                    {{ $value }}
                                                </option>
                                            @endforeach
                                            {{-- Jika status saat ini adalah status otomatis yang tidak ada di $statuses helper (misal 'rented') --}}
                                            @if (!array_key_exists($item->status, $statuses))
                                                <option value="{{ $item->status }}" selected disabled>{{ ucfirst(str_replace('_', ' ', $item->status)) }} (Otomatis)</option>
                                            @endif
                                        </select>
                                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="img" class="form-label">Ganti Gambar Item</label>
                                        <input class="form-control @error('img') is-invalid @enderror" type="file" id="img" name="img" accept="image/*">
                                        <small class="form-text text-muted">Kosongkan jika tidak ganti. Maks 2MB.</small>
                                        @error('img') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div class="mt-3">
                                            <label>Gambar Saat Ini:</label><br>
                                            @if ($item->img && File::exists(public_path($targetPath . '/' . $item->img)))
                                                <img id="current-image" src="{{ asset($targetPath . '/' . $item->img) }}" alt="Gambar Saat Ini" class="img-thumbnail" style="max-height: 100px;">
                                            @else
                                                <span class="badge bg-light-secondary">Tidak ada gambar</span>
                                            @endif
                                            <img id="image-preview" src="#" alt="Preview Gambar Baru" class="mt-2 img-thumbnail" style="max-height: 100px; display: none; margin-left: 10px;" />
                                        </div>
                                    </div>
                                    <hr>
                                    <h6 class="mt-3">Atribut Spesifik AC</h6>
                                     <div class="form-group mb-3">
                                        <label for="btu_capacity" class="form-label">Kapasitas BTU</label>
                                        <input type="number" class="form-control @error('btu_capacity') is-invalid @enderror" id="btu_capacity" name="btu_capacity" value="{{ old('btu_capacity', $item->btu_capacity) }}" min="0" placeholder="Contoh: 5000, 9000">
                                        @error('btu_capacity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="power_consumption_watt" class="form-label">Konsumsi Daya (Watt)</label>
                                        <input type="number" class="form-control @error('power_consumption_watt') is-invalid @enderror" id="power_consumption_watt" name="power_consumption_watt" value="{{ old('power_consumption_watt', $item->power_consumption_watt) }}" min="0" placeholder="Contoh: 330, 780">
                                        @error('power_consumption_watt') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="is_inverter" id="is_inverter" {{ old('is_inverter', $item->is_inverter) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_inverter">Tipe Inverter</label>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="freon_type" class="form-label">Jenis Freon</label>
                                        <input type="text" class="form-control @error('freon_type') is-invalid @enderror" id="freon_type" name="freon_type" value="{{ old('freon_type', $item->freon_type) }}" placeholder="Contoh: R32, R410A">
                                        @error('freon_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5 class="mt-4 mb-3 text-center">Atribut Utama Tambahan (Untuk Case-Based Recommendation)</h5>
                             <p class="text-muted small text-center mb-3">Isi nama atribut dan nilainya. Contoh: "Garansi Kompresor" - "5 tahun", "Ukuran Ruangan" - "10-16 m²".</p>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="main_attribute_1_name" class="form-label">Nama Atribut 1</label>
                                    <input type="text" class="form-control @error('main_attribute_1_name') is-invalid @enderror" id="main_attribute_1_name" name="main_attribute_1_name" value="{{ old('main_attribute_1_name', $item->main_attribute_1_name) }}" placeholder="Nama Atribut 1">
                                    @error('main_attribute_1_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-8">
                                    <label for="main_attribute_1_value" class="form-label">Nilai Atribut 1</label>
                                    <input type="text" class="form-control @error('main_attribute_1_value') is-invalid @enderror" id="main_attribute_1_value" name="main_attribute_1_value" value="{{ old('main_attribute_1_value', $item->main_attribute_1_value) }}" placeholder="Nilai Atribut 1">
                                    @error('main_attribute_1_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                             <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="main_attribute_2_name" class="form-label">Nama Atribut 2</label>
                                    <input type="text" class="form-control @error('main_attribute_2_name') is-invalid @enderror" id="main_attribute_2_name" name="main_attribute_2_name" value="{{ old('main_attribute_2_name', $item->main_attribute_2_name) }}" placeholder="Nama Atribut 2">
                                    @error('main_attribute_2_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-8">
                                    <label for="main_attribute_2_value" class="form-label">Nilai Atribut 2</label>
                                    <input type="text" class="form-control @error('main_attribute_2_value') is-invalid @enderror" id="main_attribute_2_value" name="main_attribute_2_value" value="{{ old('main_attribute_2_value', $item->main_attribute_2_value) }}" placeholder="Nilai Atribut 2">
                                    @error('main_attribute_2_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                             <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="main_attribute_3_name" class="form-label">Nama Atribut 3</label>
                                    <input type="text" class="form-control @error('main_attribute_3_name') is-invalid @enderror" id="main_attribute_3_name" name="main_attribute_3_name" value="{{ old('main_attribute_3_name', $item->main_attribute_3_name) }}" placeholder="Nama Atribut 3">
                                    @error('main_attribute_3_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-8">
                                    <label for="main_attribute_3_value" class="form-label">Nilai Atribut 3</label>
                                    <input type="text" class="form-control @error('main_attribute_3_value') is-invalid @enderror" id="main_attribute_3_value" name="main_attribute_3_value" value="{{ old('main_attribute_3_value', $item->main_attribute_3_value) }}" placeholder="Nilai Atribut 3">
                                    @error('main_attribute_3_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Update Item AC</button>
                                <a href="{{ route('admin.items.index') }}" class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    const imageInput = document.getElementById('img');
    const imagePreview = document.getElementById('image-preview');
    const currentImage = document.getElementById('current-image');

    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    if (currentImage) currentImage.style.opacity = '0.5';
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.src = '#';
                imagePreview.style.display = 'none';
                if (currentImage) currentImage.style.opacity = '1';
                if (file) { alert('File yang dipilih bukan gambar!'); imageInput.value = '';}
            }
        });
    }
</script>
@endpush