@extends('customer.layouts.master')

@section('page-title', $item->name . ' - Detail Produk AC')

@section('content')
    <div class="page-heading mb-4">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('customer.catalog.index') }}">Katalog</a></li>
                @if ($item->category)
                    <li class="breadcrumb-item"><a
                            href="{{ route('customer.catalog.index', ['category' => $item->category->hashid]) }}">{{ $item->category->name }}</a>
                    </li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($item->name, 50) }}</li>
            </ol>
        </nav>
    </div>

    <div class="page-content">
        <section class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-5 mb-4 mb-md-0 text-center">
                                @if ($item->img && File::exists(public_path('assets/compiled/items/' . $item->img)))
                                    <img src="{{ asset('assets/compiled/items/' . $item->img) }}" class="img-fluid rounded"
                                        alt="{{ $item->name }}" style="max-height: 400px; object-fit: contain;">
                                @else
                                    <img src="{{ asset('assets/compiled/svg/no-image.svg') }}"
                                        class="img-fluid rounded bg-light p-5" alt="No image available"
                                        style="max-height: 400px; object-fit: contain;">
                                @endif
                            </div>

                            <div class="col-md-7">
                                <h1 class="mb-2 h2">{{ $item->name }}</h1>
                                <div class="mb-3">
                                    <span class="badge bg-light-secondary me-1">{{ optional($item->brand)->name ?? 'Tanpa Brand' }}</span>
                                    <span class="badge bg-light-info">{{ optional($item->category)->name ?? 'Tanpa Kategori' }}</span>
                                </div>
                                <h3 class="text-primary mb-3">Rp{{ number_format($item->price, 0, ',', '.') }}</h3>
                                <p class="text-muted mb-4 lead fs-6">{{ $item->description ?? 'Tidak ada deskripsi detail untuk produk ini.' }}</p>
                                <h5>Spesifikasi Utama:</h5>
                                <div class="row mb-3">
                                    @if($item->btu_capacity) <div class="col-sm-6 mb-1"><strong>Kapasitas:</strong> {{ $item->btu_capacity }} BTU</div> @endif
                                    @if($item->power_consumption_watt) <div class="col-sm-6 mb-1"><strong>Daya Listrik:</strong> {{ $item->power_consumption_watt }} Watt</div> @endif
                                    @if($item->is_inverter) <div class="col-sm-6 mb-1"><strong>Tipe:</strong> Inverter</div> @endif
                                    @if($item->freon_type) <div class="col-sm-6 mb-1"><strong>Jenis Freon:</strong> {{ $item->freon_type }}</div> @endif
                                    @if($item->room_size_min_sqm || $item->room_size_max_sqm) <div class="col-sm-6 mb-1"><strong>Ukuran Ruang:</strong> {{ $item->room_size_min_sqm ?? '?' }} - {{ $item->room_size_max_sqm ?? '?' }} m²</div> @endif
                                    @if($item->warranty_compressor_years) <div class="col-sm-6 mb-1"><strong>Garansi Kompresor:</strong> {{ $item->warranty_compressor_years }} Tahun</div> @endif
                                </div>
                                @if(($item->main_attribute_1_name && $item->main_attribute_1_value) || ($item->main_attribute_2_name && $item->main_attribute_2_value) || ($item->main_attribute_3_name && $item->main_attribute_3_value))
                                <h6>Atribut Tambahan:</h6>
                                <ul class="list-unstyled mb-4 ps-3" style="list-style-type: disc;">
                                    @if($item->main_attribute_1_name && $item->main_attribute_1_value) <li><strong>{{ $item->main_attribute_1_name }}:</strong> {{ $item->main_attribute_1_value }}</li> @endif
                                    @if($item->main_attribute_2_name && $item->main_attribute_2_value) <li><strong>{{ $item->main_attribute_2_name }}:</strong> {{ $item->main_attribute_2_value }}</li> @endif
                                    @if($item->main_attribute_3_name && $item->main_attribute_3_value) <li><strong>{{ $item->main_attribute_3_name }}:</strong> {{ $item->main_attribute_3_value }}</li> @endif
                                </ul>
                                @endif
                                <p class="mb-1"><strong>Stok Tersedia:</strong> <span class="badge {{ $item->stock > 5 ? 'bg-success' : ($item->stock > 0 ? 'bg-warning' : 'bg-danger') }}">{{ $item->stock }} unit</span></p>
                                <form action="{{ route('customer.cart.add') }}" method="POST" class="mt-4">
                                    @csrf
                                    <input type="hidden" name="item_id" value="{{ $item->hashid }}">
                                    <div class="row align-items-end mb-3">
                                        <div class="col-auto">
                                            <label for="quantity" class="form-label">Jumlah:</label>
                                            <div class="input-group" style="width: 130px;">
                                                <button class="btn btn-outline-secondary btn-sm" type="button" id="button-addon-minus" onclick="decreaseQty()" {{ $item->stock <= 0 ? 'disabled' : '' }}>-</button>
                                                <input type="number" class="form-control form-control-sm text-center" id="quantity" name="quantity" value="{{ $item->stock > 0 ? 1 : 0 }}" min="1" max="{{ $item->stock }}" aria-label="Quantity" {{ $item->stock <= 0 ? 'disabled' : '' }}>
                                                <button class="btn btn-outline-secondary btn-sm" type="button" id="button-addon-plus" onclick="increaseQty()" {{ $item->stock <= 0 ? 'disabled' : '' }}>+</button>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <button type="submit" class="btn btn-primary btn-lg px-4 w-100" {{ $item->stock <= 0 ? 'disabled' : '' }}>
                                                <i class="bi bi-cart-plus-fill me-2"></i>
                                                {{ $item->stock > 0 ? 'Tambahkan ke Keranjang' : 'Stok Habis' }}
                                            </button>
                                        </div>
                                    </div>
                                     <small class="text-danger" id="stock-warning" style="display: none;">Jumlah melebihi stok yang tersedia!</small>
                                </form>
                            </div>
                        </div>

                        <hr class="my-5">
                        <div class="mt-4">
                            <h4 class="mb-3">Anda Mungkin Juga Suka:</h4>
                            @if (isset($pageRecommendedItems) && $pageRecommendedItems->count() > 0)
                                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                                    @foreach ($pageRecommendedItems as $recItem)
                                        <div class="col">
                                            <div class="card h-100 shadow-sm border-0 item-card">
                                                <a href="{{ route('customer.catalog.show', ['item_hash' => $recItem->hashid]) }}">
                                                    @if ($recItem->img && File::exists(public_path('assets/compiled/items/' . $recItem->img)))
                                                        <img src="{{ asset('assets/compiled/items/' . $recItem->img) }}" class="card-img-top item-card-img-recommendation" alt="{{ $recItem->name }}">
                                                    @else
                                                        <img src="{{ asset('assets/compiled/svg/no-image.svg') }}" class="card-img-top item-card-img-recommendation p-4 bg-light" alt="No image">
                                                    @endif
                                                </a>
                                                <div class="card-body d-flex flex-column">
                                                    <h6 class="card-title flex-grow-1 mb-1 recommendation-title">
                                                        <a href="{{ route('customer.catalog.show', ['item_hash' => $recItem->hashid]) }}" class="text-decoration-none text-dark stretched-link-sibling">
                                                            {{ Str::limit($recItem->name, 35) }}
                                                        </a>
                                                    </h6>
                                                    <p class="card-text text-primary fw-bold mb-1">Rp{{ number_format($recItem->price, 0, ',', '.') }}</p>
                                                    <small class="text-muted mb-2 d-block">{{ optional($recItem->brand)->name }}</small>
                                                    @if(isset($pageRecommendationsData[$recItem->id]['score']))
                                                        <small class="text-info" style="font-size: 0.75rem;">Similarity: {{ number_format($pageRecommendationsData[$recItem->id]['score'], 3) }}</small>
                                                    @endif
                                                    <a href="{{ route('customer.catalog.show', ['item_hash' => $recItem->hashid]) }}" class="btn btn-sm btn-outline-primary mt-auto">Lihat Detail</a>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">Belum ada rekomendasi lain untuk item ini.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .item-card:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; transition: transform .2s ease-in-out,box-shadow .2s ease-in-out; }
        .item-card-img-recommendation { height: 160px; object-fit: contain; padding: .5rem; }
        .recommendation-section .recommendation-title a { font-size: .9rem; }
        .stretched-link-sibling { position: relative; z-index: 1; }
    </style>
@endpush

@push('scripts')
    <script>
        const quantityInput = document.getElementById('quantity');
        const stock = {{ $item->stock }};
        const stockWarning = document.getElementById('stock-warning');
        function decreaseQty(){ let c = parseInt(quantityInput.value); if(c>1){quantityInput.value=c-1; stockWarning.style.display='none';}}
        function increaseQty(){ let c = parseInt(quantityInput.value); if(c<stock){quantityInput.value=c+1; stockWarning.style.display='none';}else{stockWarning.style.display='inline';}}
        quantityInput.addEventListener('input',function(){ let c=parseInt(this.value); if(isNaN(c)||c<1){this.value=stock>0?1:0; stockWarning.style.display='none';}else if(c>stock){this.value=stock;stockWarning.style.display='inline';}else{stockWarning.style.display='none';}});
        if(parseInt(quantityInput.value)>stock){quantityInput.value=stock>0?stock:(stock===0?0:1); if(stock>0&&parseInt(quantityInput.value)>=stock)stockWarning.style.display='inline';}
        if(stock<=0){quantityInput.value=0; quantityInput.disabled=true; document.getElementById('button-addon-minus').disabled=true; document.getElementById('button-addon-plus').disabled=true;}
    </script>
@endpush
