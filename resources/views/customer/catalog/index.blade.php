@extends('customer.layouts.master')

@section('page-title', 'Katalog Produk AC')

@section('content')
    <div class="page-heading mb-4">
        <h3>Katalog Produk AC</h3>
        <p class="text-subtitle text-muted">Temukan AC yang tepat untuk kenyamanan Anda.</p>
    </div>

    <div class="page-content">
        <section class="row">
            <div class="col-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="bi bi-funnel-fill"></i> Filter Produk</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('customer.catalog.index') }}" method="GET">
                            @if ($categoryHashid)
                                <input type="hidden" name="category" value="{{ $categoryHashid }}">
                            @endif
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3 col-lg-3">
                                    <label for="search_text" class="form-label">Kata Kunci</label>
                                    <input type="text" class="form-control form-control-sm" id="search_text"
                                        name="search" value="{{ $searchQuery ?? old('search') }}"
                                        placeholder="Nama, merek, fitur...">
                                </div>
                                <div class="col-md-2 col-lg-2">
                                    <label for="filter_pk" class="form-label">Ukuran PK</label>
                                    <select class="form-select form-select-sm" id="filter_pk" name="pk">
                                        <option value="">Semua PK</option>
                                        <option value="0.5"
                                            {{ isset($filterPk) && $filterPk == '0.5' ? 'selected' : '' }}>0.5 PK</option>
                                        <option value="0.75"
                                            {{ isset($filterPk) && $filterPk == '0.75' ? 'selected' : '' }}>3/4 PK
                                        </option>
                                        <option value="1" {{ isset($filterPk) && $filterPk == '1' ? 'selected' : '' }}>
                                            1 PK</option>
                                        <option value="1.5"
                                            {{ isset($filterPk) && $filterPk == '1.5' ? 'selected' : '' }}>1.5 PK</option>
                                        <option value="2" {{ isset($filterPk) && $filterPk == '2' ? 'selected' : '' }}>
                                            2 PK</option>
                                    </select>
                                </div>
                                <div class="col-md-2 col-lg-2">
                                    <label for="filter_price_max" class="form-label">Harga Maks (Rp)</label>
                                    <input type="number" class="form-control form-control-sm" id="filter_price_max"
                                        name="price_max" value="{{ $filterPriceMax ?? '' }}" placeholder="5000000"
                                        step="100000">
                                </div>
                                <div class="col-md-2 col-lg-2">
                                    <label for="filter_inverter" class="form-label">Tipe</label>
                                    <select class="form-select form-select-sm" id="filter_inverter" name="inverter">
                                        <option value="">Semua Tipe</option>
                                        <option value="1"
                                            {{ isset($filterInverter) && $filterInverter === '1' ? 'selected' : '' }}>
                                            Inverter</option>
                                        <option value="0"
                                            {{ isset($filterInverter) && $filterInverter === '0' ? 'selected' : '' }}>
                                            Standard</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-lg-3">
                                    <label for="filter_brand" class="form-label">Brand</label>
                                    <select class="form-select form-select-sm" id="filter_brand" name="brand">
                                        <option value="">Semua Brand</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->hashid }}"
                                                {{ isset($filterBrandHashid) && $filterBrandHashid == $brand->hashid ? 'selected' : '' }}>
                                                {{ $brand->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 col-lg-2">
                                    <label for="filter_power_max" class="form-label">Daya Maks (Watt)</label>
                                    <input type="number" class="form-control form-control-sm" id="filter_power_max"
                                        name="power_max" value="{{ $filterPowerMax ?? '' }}" placeholder="800"
                                        step="50">
                                </div>
                                <div class="col-md-2 col-lg-2">
                                    <label for="filter_room_size" class="form-label">Ukuran Ruang (m²)</label>
                                    <select class="form-select form-select-sm" id="filter_room_size" name="room_size">
                                        <option value="">Semua Ukuran</option>
                                        <option value="10"
                                            {{ isset($filterRoomSize) && $filterRoomSize == '10' ? 'selected' : '' }}>
                                            <= 10 m²</option>
                                        <option value="16"
                                            {{ isset($filterRoomSize) && $filterRoomSize == '16' ? 'selected' : '' }}>
                                            <= 16 m²</option>
                                        <option value="20"
                                            {{ isset($filterRoomSize) && $filterRoomSize == '20' ? 'selected' : '' }}>
                                            <= 20 m²</option>
                                        <option value="25"
                                            {{ isset($filterRoomSize) && $filterRoomSize == '25' ? 'selected' : '' }}>>
                                            20 m²</option>
                                    </select>
                                </div>
                                <div class="col-md-2 col-lg-2">
                                    <label for="filter_warranty_min" class="form-label">Min. Garansi Komp. (Thn)</label>
                                    <select class="form-select form-select-sm" id="filter_warranty_min" name="warranty_min">
                                        <option value="">Semua Garansi</option>
                                        <option value="1"
                                            {{ isset($filterWarrantyMin) && $filterWarrantyMin == '1' ? 'selected' : '' }}>
                                            Minimal 1 Thn</option>
                                        <option value="3"
                                            {{ isset($filterWarrantyMin) && $filterWarrantyMin == '3' ? 'selected' : '' }}>
                                            Minimal 3 Thn</option>
                                        <option value="5"
                                            {{ isset($filterWarrantyMin) && $filterWarrantyMin == '5' ? 'selected' : '' }}>
                                            Minimal 5 Thn</option>
                                        <option value="10"
                                            {{ isset($filterWarrantyMin) && $filterWarrantyMin == '10' ? 'selected' : '' }}>
                                            Minimal 10 Thn</option>
                                    </select>
                                </div>
                                <div class="col-md-12 col-lg-1 d-flex align-items-end mt-3 mt-lg-0">
                                    <button class="btn btn-primary btn-sm w-100" type="submit" title="Terapkan Filter"><i
                                            class="bi bi-funnel-fill"></i></button>
                                </div>
                                <div class="col-md-12 col-lg-2 d-flex align-items-end mt-2 mt-lg-0">
                                    <a href="{{ route('customer.catalog.index', ['category' => $categoryHashid]) }}"
                                        class="btn btn-secondary btn-sm w-100" title="Reset Filter Spesifik">Reset
                                        Filter</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="mb-4">
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link {{ !$selectedCategory && !$searchQuery && !$filterPk && !$filterPriceMax && ($filterInverter === null || $filterInverter === '') && !$filterBrandHashid && !$filterPowerMax && !$filterRoomSize && !$filterWarrantyMin ? 'active' : '' }}"
                                href="{{ route('customer.catalog.index') }}">Semua Kategori</a>
                        </li>
                        @foreach ($categories as $category)
                            <li class="nav-item">
                                <a class="nav-link {{ isset($selectedCategory) && $selectedCategory->id == $category->id ? 'active' : '' }}"
                                    href="{{ route('customer.catalog.index', array_merge(request()->except('category', 'page'), ['category' => $category->hashid])) }}">
                                    {{ $category->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if (
                    $searchQuery ||
                        $filterPk ||
                        $filterPriceMax ||
                        ($filterInverter !== null && $filterInverter !== '') ||
                        $filterBrandHashid ||
                        $filterPowerMax ||
                        $filterRoomSize ||
                        $filterWarrantyMin)
                    <div class="alert alert-info">
                        Menampilkan hasil berdasarkan filter Anda.
                        <a href="{{ route('customer.catalog.index') }}"
                            class="float-end btn-sm btn-light text-decoration-none">Reset Semua Filter & Pencarian</a>
                    </div>
                @endif

                @if (isset($isGeneralRecommendation) &&
                        $isGeneralRecommendation &&
                        isset($recommendedItems) &&
                        $recommendedItems->count() > 0)
                    <div class="recommendation-section mb-5">
                        <h4 class="mb-3">
                            @if ($recommendationQueryItem)
                                Produk Serupa dengan "{{ Str::limit($recommendationQueryItem->name, 30) }}":
                            @else
                                Rekomendasi Untuk Anda:
                            @endif
                        </h4>
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                            @foreach ($recommendedItems as $recItem)
                                <div class="col">
                                    <div class="card h-100 shadow-sm border-0 item-card">
                                        <a href="{{ route('customer.catalog.show', ['item_hash' => $recItem->hashid]) }}">
                                            @if ($recItem->img && File::exists(public_path('assets/compiled/items/' . $recItem->img)))
                                                <img src="{{ asset('assets/compiled/items/' . $recItem->img) }}"
                                                    class="card-img-top item-card-img-recommendation"
                                                    alt="{{ $recItem->name }}">
                                            @else
                                                <img src="{{ asset('assets/compiled/svg/no-image.svg') }}"
                                                    class="card-img-top item-card-img-recommendation p-4 bg-light"
                                                    alt="No image">
                                            @endif
                                        </a>
                                        <div class="card-body d-flex flex-column">
                                            <h6 class="card-title flex-grow-1 mb-1 recommendation-title">
                                                <a href="{{ route('customer.catalog.show', ['item_hash' => $recItem->hashid]) }}"
                                                    class="text-decoration-none text-dark stretched-link-sibling">
                                                    {{ Str::limit($recItem->name, 35) }}
                                                </a>
                                            </h6>
                                            <p class="card-text text-primary fw-bold mb-1">
                                                Rp{{ number_format($recItem->price, 0, ',', '.') }}</p>
                                            <small
                                                class="text-muted mb-2 d-block">{{ optional($recItem->brand)->name }}</small>
                                            @if (isset($recommendationsData[$recItem->id]['score']))
                                                <small class="text-info" style="font-size: 0.75rem;">Similarity:
                                                    {{ number_format($recommendationsData[$recItem->id]['score'], 3) }}</small>
                                            @endif
                                            <a href="{{ route('customer.catalog.show', ['item_hash' => $recItem->hashid]) }}"
                                                class="btn btn-sm btn-outline-primary mt-2">Lihat Detail</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <hr class="my-4">
                    </div>
                @endif

                <h4 class="mb-3">
                    @if (
                        $searchQuery ||
                            $filterPk ||
                            $filterPriceMax ||
                            ($filterInverter !== null && $filterInverter !== '') ||
                            $filterBrandHashid ||
                            $filterPowerMax ||
                            $filterRoomSize ||
                            $filterWarrantyMin)
                        Hasil yang Sesuai:
                    @elseif (isset($selectedCategory) && $selectedCategory)
                        Kategori: {{ $selectedCategory->name }}
                    @else
                        Semua Produk AC
                    @endif
                </h4>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                    @forelse ($items as $item)
                        <div class="col">
                            <div class="card h-100 shadow-sm border-0 item-card">
                                <a href="{{ route('customer.catalog.show', $item->hashid) }}" class="stretched-link">
                                    @if ($item->img && File::exists(public_path('assets/compiled/items/' . $item->img)))
                                        <img src="{{ asset('assets/compiled/items/' . $item->img) }}"
                                            class="card-img-top item-card-img" alt="{{ $item->name }}">
                                    @else
                                        <img src="{{ asset('assets/compiled/svg/no-image.svg') }}"
                                            class="card-img-top item-card-img p-5 bg-light" alt="No image available">
                                    @endif
                                </a>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title mb-1">
                                        <a href="{{ route('customer.catalog.show', $item->hashid) }}"
                                            class="text-decoration-none text-dark stretched-link-sibling">{{ Str::limit($item->name, 40) }}</a>
                                    </h5>
                                    <p class="card-text text-primary fw-bold mb-1">
                                        Rp{{ number_format($item->price, 0, ',', '.') }}</p>
                                    @if (isset($recommendationsData[$item->id]['score']) &&
                                            ($searchQuery ||
                                                $filterPk ||
                                                $filterPriceMax ||
                                                ($filterInverter !== null && $filterInverter !== '') ||
                                                $filterBrandHashid ||
                                                $filterPowerMax ||
                                                $filterRoomSize ||
                                                $filterWarrantyMin))
                                        <small class="text-success fw-bold" style="font-size: 0.8rem;">
                                            <i class="bi bi-check-circle-fill"></i> Kecocokan:
                                            {{ round($recommendationsData[$item->id]['score'] * 100) }}%
                                        </small>
                                    @endif
                                    <div class="mt-auto d-flex justify-content-between align-items-center pt-2">
                                        <small class="text-muted">Stok: {{ $item->stock }}</small>
                                        <span
                                            class="badge bg-light-secondary">{{ optional($item->category)->name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-warning text-center">
                                @if (
                                    $searchQuery ||
                                        $filterPk ||
                                        $filterPriceMax ||
                                        ($filterInverter !== null && $filterInverter !== '') ||
                                        $filterBrandHashid ||
                                        $filterPowerMax ||
                                        $filterRoomSize ||
                                        $filterWarrantyMin)
                                    Tidak ada item yang cocok dengan kriteria filter atau pencarian Anda.
                                @elseif($selectedCategory)
                                    Belum ada item dalam kategori "{{ $selectedCategory->name }}".
                                @else
                                    Belum ada item AC yang tersedia saat ini.
                                @endif
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
            transition: transform .2s ease-in-out, box-shadow .2s ease-in-out;
        }

        .item-card-img {
            height: 200px;
            object-fit: contain;
            padding: 0.75rem;
        }

        .item-card-img-recommendation {
            height: 160px;
            object-fit: contain;
            padding: 0.5rem;
        }

        .recommendation-section .recommendation-title a {
            font-size: .9rem;
        }

        .stretched-link-sibling {
            position: relative;
            z-index: 1;
        }
    </style>
@endpush
