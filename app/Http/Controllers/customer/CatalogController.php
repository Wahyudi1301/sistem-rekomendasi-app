<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Item;
use App\Models\Brand;
// use App\Http\Controllers\Customer\RecommendationController; // Sudah di-inject di constructor
use Illuminate\View\View;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    protected $recommender;

    public function __construct(RecommendationController $recommender)
    {
        $this->recommender = $recommender;
    }

    public function index(Request $request): View
    {
        $categories = Category::orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();
        $selectedCategory = null;
        $searchQuery = $request->input('search');
        $categoryHashid = $request->input('category');
        $filterBrandHashid = $request->input('brand');
        $filterPk = $request->input('pk');
        $filterPriceMax = $request->input('price_max');
        $filterInverter = $request->input('inverter'); // Bisa '1', '0', atau '' (string kosong)
        $filterPowerMax = $request->input('power_max');
        $filterRoomSize = $request->input('room_size');
        $filterWarrantyMin = $request->input('warranty_min');

        $itemsToDisplay = new EloquentCollection();
        $recommendedItems = new EloquentCollection(); // Untuk rekomendasi umum
        $recommendationsData = []; // Untuk menyimpan skor, [item_id => ['score' => 0.xx]]
        $isGeneralRecommendation = false;
        $recommendationQueryItem = null;

        // ---- Membangun Ideal Profile dari Filter ----
        $idealProfileData = [];
        if ($filterPriceMax) {
            $idealProfileData['price'] = (float) $filterPriceMax;
        }
        if ($filterPk) {
            // Mapping PK ke BTU. Pastikan ini sesuai dengan data Anda.
            $pkToBtuMap = ['0.5' => 5000, '0.75' => 7000, '1' => 9000, '1.5' => 12000, '2' => 18000];
            $idealProfileData['btu_capacity'] = $pkToBtuMap[$filterPk] ?? null; // Jika PK tidak valid, BTU akan null
        }
        // Perhatikan: $filterInverter bisa string '0', '1', atau ''.
        // Jika '' (kosong/Semua Tipe), jangan set 'is_inverter' di ideal profile
        // agar tidak mempengaruhi skor case-based secara spesifik untuk inverter.
        if ($filterInverter !== null && $filterInverter !== '') {
            $idealProfileData['is_inverter'] = (bool) (int) $filterInverter; // Konversi '0'/'1' ke boolean
        }
        if ($filterPowerMax) {
            $idealProfileData['power_consumption_watt'] = (float) $filterPowerMax;
        }
        if ($filterBrandHashid) {
            $decodedBrandId = Hashids::decode($filterBrandHashid);
            if (!empty($decodedBrandId)) {
                $brandModel = Brand::find($decodedBrandId[0]);
                if ($brandModel) {
                    // Ini akan digunakan untuk content-based di extractKeywordsFromItemProfile
                    $idealProfileData['brand_name_for_keyword'] = strtolower($brandModel->name);
                    // Anda juga bisa menambahkan brand_id jika ingin case-based memperhitungkannya
                    // $idealProfileData['brand_id'] = $decodedBrandId[0];
                }
            }
        }
        if ($filterRoomSize) {
            // Jika '25' berarti '> 20 m²', kita set target tinggi agar item dengan room_size besar cocok
            $idealProfileData['room_size_max_sqm'] = ($filterRoomSize == '25' ? 999 : (int)$filterRoomSize);
        }
        if ($filterWarrantyMin) {
            $idealProfileData['warranty_compressor_years'] = (int)$filterWarrantyMin;
        }
        if ($searchQuery) {
            $idealProfileData['description'] = $searchQuery; // Untuk keyword extraction
        }
        // Membuat "dummy" Item sebagai ideal profile
        $idealItemProfile = new Item($idealProfileData);
        $idealItemProfile->id = 0; // Tandai sebagai ideal profile

        // Cek apakah ada filter yang aktif
        $isAnyFilterActive = $searchQuery || $filterPk || $filterPriceMax ||
            ($filterInverter !== null && $filterInverter !== '') ||
            $filterBrandHashid || $filterPowerMax || $filterRoomSize || $filterWarrantyMin;

        if ($isAnyFilterActive) {
            $isGeneralRecommendation = false;
            $recommendationQueryItem = null; // Tidak ada query item spesifik saat filter aktif
            $recommendedItems = new EloquentCollection(); // Kosongkan rekomendasi umum

            // --- Mengambil Kandidat Item ---
            // Longgarkan query awal di sini. Biarkan rankItemsByProfile yang menilai.
            $candidateItemsQuery = Item::with(['category', 'brand'])
                ->where('status', 'available')
                ->where('stock', '>', 0)
                ->whereNotNull('description'); // Deskripsi penting untuk content-based

            // Filter kategori jika ada
            if ($categoryHashid) {
                $decodedCategoryId = Hashids::decode($categoryHashid);
                if (!empty($decodedCategoryId)) {
                    $selectedCategory = Category::find($decodedCategoryId[0]);
                    if ($selectedCategory) {
                        $candidateItemsQuery->where('category_id', $selectedCategory->id);
                    } else {
                        $categoryHashid = null; // Kategori tidak valid
                    }
                } else {
                    $categoryHashid = null; // Hashid tidak valid
                }
            }

            // Filter awal yang MUNGKIN masih relevan (opsional, bisa dihapus jika ingin ranker yang handle semua)
            // if (isset($idealProfileData['price'])) {
            //     $candidateItemsQuery->where('price', '<=', $idealProfileData['price']); // Hard filter harga maks
            // }
            // Jika ada search query, kita bisa lakukan pre-filter sederhana untuk performa
            // Namun, rankItemsByProfile juga akan menghitung content-based dari description
            if ($searchQuery) {
                $candidateItemsQuery->where(function ($q) use ($searchQuery) {
                    $q->where('name', 'like', "%$searchQuery%")
                        ->orWhere('description', 'like', "%$searchQuery%");
                });
            }


            $candidateItems = $candidateItemsQuery->take(200)->get(); // Ambil sejumlah kandidat, jangan semua jika data besar

            if ($candidateItems->isNotEmpty()) {
                // Panggil ranker dengan ideal profile dan kandidat
                $rankedResults = $this->recommender->rankItemsByProfile($idealItemProfile, $candidateItems, 50); // Ambil top 50 hasil ranking

                if (!empty($rankedResults)) {
                    $itemsToDisplay = new EloquentCollection(array_map(fn($res) => $res['item'], $rankedResults));
                    foreach ($rankedResults as $res) {
                        $recommendationsData[$res['item']->id] = ['score' => $res['score']];
                    }
                }
            }
            // Jika $candidateItems kosong atau $rankedResults kosong, $itemsToDisplay akan tetap kosong,
            // dan view akan menampilkan "Tidak ada item yang cocok".
        } else {
            // --- Logika Tanpa Filter Aktif (Rekomendasi Umum) ---
            $isGeneralRecommendation = true;
            $baseItemsQuery = Item::with(['category', 'brand'])
                ->where('status', 'available')
                ->where('stock', '>', 0);

            if ($categoryHashid) {
                $decodedCategoryId = Hashids::decode($categoryHashid);
                if (!empty($decodedCategoryId)) {
                    $categoryId = $decodedCategoryId[0];
                    $selectedCategory = Category::find($categoryId);
                    if ($selectedCategory) {
                        $baseItemsQuery->where('category_id', $selectedCategory->id);
                    } else {
                        $categoryHashid = null; // Kategori tidak valid
                    }
                } else {
                    $categoryHashid = null; // Hashid tidak valid
                }
            }
            $itemsToDisplay = $baseItemsQuery->latest()->take(20)->get(); // Tampilkan 20 item terbaru

            // Ambil rekomendasi umum berdasarkan item terbaru jika ada
            if ($itemsToDisplay->isNotEmpty()) {
                $recommendationQueryItem = $itemsToDisplay->sortByDesc('created_at')->first();
                if ($recommendationQueryItem) {
                    $recommendationsFromService = $this->recommender->getRecommendations($recommendationQueryItem, 4); // Ambil 4 rekomendasi
                    if (!empty($recommendationsFromService)) {
                        $recs = new EloquentCollection(array_map(fn($rec) => $rec['item'], $recommendationsFromService));
                        // Pastikan tidak merekomendasikan item itu sendiri
                        $recommendedItems = $recs->filter(fn($recItem) => $recItem->id !== $recommendationQueryItem->id)->take(4);
                        foreach ($recommendationsFromService as $rec) {
                            if ($recommendedItems->contains('id', $rec['item']->id)) {
                                $recommendationsData[$rec['item']->id] = ['score' => $rec['score']];
                            }
                        }
                    }
                }
            }
        }

        // Variabel $items sekarang merujuk ke $itemsToDisplay yang sudah diproses
        $items = $itemsToDisplay;

        return view('customer.catalog.index', compact(
            'categories',
            'brands',
            'selectedCategory',
            'searchQuery',
            'categoryHashid',
            'items', // Ini yang akan di-loop di view
            'recommendedItems', // Untuk section rekomendasi umum
            'isGeneralRecommendation',
            'recommendationQueryItem',
            'recommendationsData', // Ini yang berisi skor untuk ditampilkan
            // Kirim kembali nilai filter untuk ditampilkan di form
            'filterPk',
            'filterPriceMax',
            'filterInverter',
            'filterBrandHashid',
            'filterPowerMax',
            'filterRoomSize',
            'filterWarrantyMin'
        ));
    }

    public function show($item_hash): View
    {
        $decodedId = Hashids::decode($item_hash);
        if (empty($decodedId)) {
            abort(404);
        }
        $id = $decodedId[0];

        try {
            $item = Item::with(['category', 'brand'])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            abort(404);
        }

        if ($item->status !== 'available' || $item->stock <= 0) {
            // Sebaiknya tidak tampilkan item yang tidak tersedia, atau beri pesan jelas
            // Di sini kita biarkan tampil tapi dengan warning di view
            $warning = 'Item ini mungkin tidak tersedia atau stok habis.';
            // return view('customer.catalog.show', compact('item', 'warning')); // Pilihan
        }

        $pageRecommendedItems = new EloquentCollection();
        $pageRecommendationsData = [];

        // Dapatkan rekomendasi untuk halaman detail produk
        $recommendationsFromServiceShow = $this->recommender->getRecommendations($item, 5); // Ambil 5, filter 1 (item itu sendiri)
        if (!empty($recommendationsFromServiceShow)) {
            $recs = new EloquentCollection(array_map(fn($rec) => $rec['item'], $recommendationsFromServiceShow));
            // Filter item saat ini dari rekomendasi
            $pageRecommendedItems = $recs->filter(fn($recItem) => $recItem->id !== $item->id)->take(4);

            foreach ($recommendationsFromServiceShow as $rec) {
                // Hanya simpan skor untuk item yang benar-benar direkomendasikan
                if ($pageRecommendedItems->contains('id', $rec['item']->id)) {
                    $pageRecommendationsData[$rec['item']->id] = ['score' => $rec['score']];
                }
            }
        }
        $viewData = compact('item', 'pageRecommendedItems', 'pageRecommendationsData');
        if (isset($warning)) $viewData['warning'] = $warning;

        return view('customer.catalog.show', $viewData);
    }
}
