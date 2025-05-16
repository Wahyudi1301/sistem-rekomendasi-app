<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ItemKeyword; // <--- PENTING: Import ItemKeyword
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ItemController extends Controller
{
    private $targetPath = 'assets/compiled/items';

    public function index()
    {
        return view('admin.items.index');
    }

    public function getData(Request $request)
    {
        $items = Item::with(['category', 'brand'])->select('items.*');

        return DataTables::of($items)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $editUrl = route('admin.items.edit', $row->hashid ?? $row->id); // Gunakan hashid jika ada
                $deleteUrl = route('admin.items.destroy', $row->hashid ?? $row->id); // Gunakan hashid jika ada
                // TAMBAHKAN URL UNTUK KEYWORDS
                $keywordsUrl = route('admin.keywords.index', ['item_hashid' => $row->hashid ?? $row->id]);

                $buttons = '<a href="' . $editUrl . '" class="btn btn-sm btn-primary me-1 mb-1" title="Edit Item"><i class="bi bi-pencil-square"></i> Edit</a>';
                // TAMBAHKAN TOMBOL KEYWORDS
                $buttons .= '<a href="' . $keywordsUrl . '" class="btn btn-sm btn-info me-1 mb-1" title="Lihat & Kelola Keywords"><i class="bi bi-tags"></i> Keywords</a>';

                $buttons .= '
                <form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Yakin ingin menghapus item ini beserta keywords terkait?\');">
                    ' . csrf_field() . '
                    ' . method_field('DELETE') . '
                    <button type="submit" class="btn btn-sm btn-danger mb-1" title="Hapus Item"><i class="bi bi-trash"></i> Delete</button>
                </form>';
                return $buttons;
            })
            ->addColumn('category_name', fn($row) => $row->category?->name ?? '<span class="badge bg-light-secondary">None</span>')
            ->addColumn('brand_name', fn($row) => $row->brand?->name ?? '<span class="badge bg-light-secondary">None</span>')
            // Pastikan Anda menggunakan 'price' jika sudah diganti dari 'rental_price'
            ->editColumn('price', fn($row) => 'Rp ' . number_format($row->price, 0, ',', '.'))
            ->addColumn('image_display', function ($row) {
                $fullPublicPath = public_path($this->targetPath . '/' . $row->img);
                if ($row->img && File::exists($fullPublicPath)) {
                    $imageUrl = asset($this->targetPath . '/' . $row->img);
                    return '<img src="' . $imageUrl . '" alt="' . htmlspecialchars($row->name) . '" width="60" height="60" style="object-fit: cover; border-radius: 5px;">';
                }
                return '<span class="badge bg-light-secondary">No Image</span>';
            })
            ->editColumn('status', function ($row) {
                $color = 'secondary';
                if ($row->status == 'available') $color = 'success';
                elseif ($row->status == 'rented') $color = 'warning';
                elseif ($row->status == 'maintenance') $color = 'info';
                elseif ($row->status == 'out_of_stock') $color = 'dark';
                elseif ($row->status == 'unavailable') $color = 'danger';
                return '<span class="badge bg-light-' . $color . '">' . ucfirst(str_replace('_', ' ', $row->status)) . '</span>';
            })
            ->editColumn('created_at', fn($row) => $row->created_at?->format('d M Y H:i') ?? '-')
            ->rawColumns(['action', 'image_display', 'category_name', 'brand_name', 'status'])
            ->make(true);
    }

    public function create()
    {
        $categories = Category::orderBy('name')->pluck('name', 'id');
        $brands = Brand::orderBy('name')->pluck('name', 'id');
        $statuses = $this->getStatuses();
        return view('admin.items.create', compact('categories', 'brands', 'statuses'));
    }

    public function store(Request $request)
    {
        // Sesuaikan validasi dengan semua field baru di model Item
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('items', 'name')],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'price' => ['required', 'numeric', 'min:0'], // Ganti dari rental_price
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', Rule::in(array_keys($this->getStatuses()))],
            'description' => ['nullable', 'string'],
            'img' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('items', 'sku')],
            'main_attribute_1_name' => ['nullable', 'string', 'max:100'],
            'main_attribute_1_value' => ['nullable', 'string', 'max:191'],
            'main_attribute_2_name' => ['nullable', 'string', 'max:100'],
            'main_attribute_2_value' => ['nullable', 'string', 'max:191'],
            'main_attribute_3_name' => ['nullable', 'string', 'max:100'],
            'main_attribute_3_value' => ['nullable', 'string', 'max:191'],
            'btu_capacity' => ['nullable', 'integer', 'min:0'],
            'power_consumption_watt' => ['nullable', 'integer', 'min:0'],
            'is_inverter' => ['nullable', 'boolean'],
            'freon_type' => ['nullable', 'string', 'max:20'],
        ]);
        $validatedData['is_inverter'] = $request->has('is_inverter');


        $filenameToStore = null;
        $fullTargetPath = public_path($this->targetPath);
        if ($request->hasFile('img')) { /* ... logika upload ... */
            try {
                if (!File::isDirectory($fullTargetPath)) {
                    File::makeDirectory($fullTargetPath, 0755, true, true);
                }
                $image = $request->file('img');
                $filenameToStore = 'item_' . time() . '_' . Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $image->getClientOriginalExtension();
                $image->move($fullTargetPath, $filenameToStore);
            } catch (\Exception $e) {
                Log::error('Item Image Upload Error (Store): ' . $e->getMessage());
                return redirect()->back()->withInput()->with('error', 'Gagal mengupload gambar.');
            }
        }

        try {
            $item = Item::create(array_merge($validatedData, ['img' => $filenameToStore]));
            $this->generateKeywordsForItem($item); // <--- PANGGIL GENERATE KEYWORDS
            return redirect()->route('admin.items.index')->with('success', 'Item AC berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Item Creation Error: ' . $e->getMessage());
            if ($filenameToStore && File::exists($fullTargetPath . '/' . $filenameToStore)) {
                File::delete($fullTargetPath . '/' . $filenameToStore);
            }
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan item.');
        }
    }

    public function edit(Item $item)
    {
        $categories = Category::orderBy('name')->pluck('name', 'id');
        $brands = Brand::orderBy('name')->pluck('name', 'id');
        $statuses = $this->getStatuses();
        $targetPath = $this->targetPath;
        return view('admin.items.edit', compact('item', 'categories', 'brands', 'statuses', 'targetPath'));
    }

    public function update(Request $request, Item $item)
    {
        // Sesuaikan validasi dengan semua field baru di model Item
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('items', 'name')->ignore($item->id)],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'price' => ['required', 'numeric', 'min:0'], // Ganti dari rental_price
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', Rule::in(array_keys($this->getStatuses()))],
            'description' => ['nullable', 'string'],
            'img' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('items', 'sku')->ignore($item->id)],
            'main_attribute_1_name' => ['nullable', 'string', 'max:100'],
            'main_attribute_1_value' => ['nullable', 'string', 'max:191'],
            'main_attribute_2_name' => ['nullable', 'string', 'max:100'],
            'main_attribute_2_value' => ['nullable', 'string', 'max:191'],
            'main_attribute_3_name' => ['nullable', 'string', 'max:100'],
            'main_attribute_3_value' => ['nullable', 'string', 'max:191'],
            'btu_capacity' => ['nullable', 'integer', 'min:0'],
            'power_consumption_watt' => ['nullable', 'integer', 'min:0'],
            'is_inverter' => ['nullable', 'boolean'],
            'freon_type' => ['nullable', 'string', 'max:20'],
        ]);
        $validatedData['is_inverter'] = $request->has('is_inverter');

        $currentFilename = $item->img;
        $filenameToStore = $currentFilename;
        $fullTargetPath = public_path($this->targetPath);
        $fileUploaded = false;
        if ($request->hasFile('img')) { /* ... logika upload ... */
            try {
                if (!File::isDirectory($fullTargetPath)) {
                    File::makeDirectory($fullTargetPath, 0755, true, true);
                }
                $oldFilePath = $fullTargetPath . '/' . $currentFilename;
                if ($currentFilename && File::exists($oldFilePath)) {
                    File::delete($oldFilePath);
                }
                $image = $request->file('img');
                $filenameToStore = 'item_' . time() . '_' . Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $image->getClientOriginalExtension();
                $image->move($fullTargetPath, $filenameToStore);
                $fileUploaded = true;
            } catch (\Exception $e) {
                Log::error('Item Image Upload Error (Update): ' . $e->getMessage());
                return redirect()->back()->withInput()->with('error', 'Gagal memproses gambar baru.');
            }
        }

        try {
            $item->update(array_merge($validatedData, ['img' => $filenameToStore]));
            $this->generateKeywordsForItem($item); // <--- PANGGIL GENERATE KEYWORDS
            return redirect()->route('admin.items.index')->with('success', 'Item AC berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Item Update Error: ' . $e->getMessage());
            if ($fileUploaded && $filenameToStore && File::exists($fullTargetPath . '/' . $filenameToStore)) {
                File::delete($fullTargetPath . '/' . $filenameToStore);
            }
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui item.');
        }
    }

    public function destroy(Item $item)
    {
        if ($item->bookings()->exists()) {
            return response()->json(['error' => 'Item tidak dapat dihapus karena memiliki riwayat booking.'], 422);
        }
        try {
            $filenameToDelete = $item->img;
            $fullFilePath = public_path($this->targetPath . '/' . $filenameToDelete);
            $itemName = $item->name;
            // Keyword akan terhapus otomatis jika ada onDelete('cascade') di foreign key
            // atau bisa dihapus manual di sini: $item->keywords()->delete(); sebelum $item->delete();
            $item->delete();
            if ($filenameToDelete && File::exists($fullFilePath)) {
                File::delete($fullFilePath);
            }
            // Untuk AJAX delete, return JSON. Jika non-AJAX, redirect.
            // Kode Anda sebelumnya menggunakan response JSON, jadi saya biarkan.
            return response()->json(['message' => "Item '{$itemName}' berhasil dihapus."]);
        } catch (\Exception $e) {
            Log::error('Item Deletion Error: ' . $e->getMessage());
            if ($e instanceof ModelNotFoundException) {
                return response()->json(['error' => 'Item tidak ditemukan.'], 404);
            }
            return response()->json(['error' => 'Terjadi kesalahan.'], 500);
        }
    }

    private function getStatuses(): array
    {
        return ['available' => 'Available', 'maintenance' => 'Maintenance', 'out_of_stock' => 'Out of Stock', 'unavailable' => 'Unavailable'];
    }

    /**
     * Generate dan simpan keywords untuk item.
     * Ini adalah contoh, Anda bisa membuatnya lebih canggih.
     */
    protected function generateKeywordsForItem(Item $item): void
    {
        // Refresh item untuk mendapatkan relasi brand dan category terbaru jika ada perubahan
        $item->load(['brand', 'category']);
        $item->keywords()->delete(); // Hapus keyword lama

        $textParts = [
            strtolower($item->name ?? ''),
            strtolower($item->description ?? ''),
            strtolower($item->sku ?? ''),
            strtolower($item->brand?->name ?? ''),
            strtolower($item->category?->name ?? ''),
            strtolower($item->freon_type ?? ''),
            $item->is_inverter ? 'inverter' : 'standard',
            $item->btu_capacity ? (string)$item->btu_capacity . 'btu' : '',
            $item->btu_capacity ? (string)round($item->btu_capacity / 9000, 2) . 'pk' : '',
            $item->power_consumption_watt ? (string)$item->power_consumption_watt . 'watt' : '',
            strtolower($item->main_attribute_1_name ?? ''),
            strtolower($item->main_attribute_1_value ?? ''),
            strtolower($item->main_attribute_2_name ?? ''),
            strtolower($item->main_attribute_2_value ?? ''),
            strtolower($item->main_attribute_3_name ?? ''),
            strtolower($item->main_attribute_3_value ?? ''),
        ];

        if ($item->btu_capacity) {
            if ($item->btu_capacity <= 5500) $textParts[] = '0.5pk'; // Sesuaikan rentang
            elseif ($item->btu_capacity <= 8000) $textParts[] = '0.75pk';
            elseif ($item->btu_capacity <= 10000) $textParts[] = '1pk';
            elseif ($item->btu_capacity <= 13000) $textParts[] = '1.5pk';
            elseif ($item->btu_capacity <= 19000) $textParts[] = '2pk';
        }
        if ($item->power_consumption_watt && $item->btu_capacity && ($item->btu_capacity / $item->power_consumption_watt) > 12) { // Estimasi EER sederhana untuk low watt
            $textParts[] = 'lowwatt';
            $textParts[] = 'hematenergi';
        }

        $textToProcess = implode(' ', array_filter($textParts));
        $textToProcess = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $textToProcess);
        $textToProcess = str_replace('-', ' ', $textToProcess);
        $textToProcess = preg_replace('/\s+/', ' ', $textToProcess);

        $tokens = array_filter(array_unique(explode(' ', $textToProcess)));
        $stopwords = ['dan', 'di', 'atau', 'dengan', 'untuk', 'yang', 'ini', 'itu', 'ac', 'pk', 'rp', 'harga', 'jual', 'watt', 'btu', 'tipe', 'jenis', 'series', 'fitur', 'mode', 'pendingin', 'pendinginan', 'listrik', 'udara', 'garansi', 'tahun', 'konsumsi', 'daya', 'kapasitas', 'dari', 'ke', 'unit', 'pada', 'hingga', 'lebih', 'juga', 'sangat', 'sekali', 'control', 'operation', 'cocok', 'ruangan', 'h', 'memiliki', 'dilengkapi', 'adalah', 'merupakan', 'sebagai', 'produk', 'seri', 'membuat', 'akan', 'dapat', 'namun', 'serta', 'lain', 'menggunakan', 'efisien', 'berbagai', 'contoh', 'model', 'untuk', 'bisa', 'namun', 'jika', 'mungkin', 'tidak', 'juga', 'saja', 'saat', 'lebih', 'akan', 'pada', 'ke', 'ini', 'itu'];
        $keywords = array_diff($tokens, $stopwords);

        $generatedKeywordsData = [];
        foreach ($keywords as $keyword) {
            $trimmedKeyword = trim($keyword);
            if ($trimmedKeyword !== '' && strlen($trimmedKeyword) > 2 && !is_numeric($trimmedKeyword)) {
                $generatedKeywordsData[] = ['keyword_name' => $trimmedKeyword];
            }
        }

        if (!empty($generatedKeywordsData)) {
            try {
                $item->keywords()->createMany($generatedKeywordsData);
            } catch (\Illuminate\Database\QueryException $e) {
                // Tangani jika ada duplikasi unik, meskipun createMany seharusnya tidak masalah jika tidak ada unique constraint di DB selain (item_id, keyword_name)
                Log::warning("Gagal createMany keywords untuk item ID {$item->id}. Mencoba satu per satu. Error: " . $e->getMessage());
                foreach ($generatedKeywordsData as $kwData) {
                    try {
                        $item->keywords()->firstOrCreate($kwData); // Lebih aman untuk duplikasi
                    } catch (\Exception $subE) {
                        Log::error("Gagal menyimpan keyword '{$kwData['keyword_name']}' untuk item ID {$item->id}: " . $subE->getMessage());
                    }
                }
            }
        }
        Log::info("Generated " . count($generatedKeywordsData) . " keywords for item ID: {$item->id}");
    }
}
