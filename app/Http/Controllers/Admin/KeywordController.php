<?php

// PASTIKAN NAMESPACE SUDAH BENAR
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemKeyword; // Model tetap ItemKeyword
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids; // Import Hashids

// RENAME CLASS
class KeywordController extends Controller
{
    /**
     * Menampilkan daftar keywords. Membutuhkan item_hashid via query string.
     */
    public function index(Request $request)
    {
        $item = null;
        $itemHashid = $request->query('item_hashid'); // Ambil dari query string

        if ($itemHashid) {
            $decodedId = Hashids::decode($itemHashid);
            if (!empty($decodedId)) {
                $item = Item::find($decodedId[0]);
            }
        }

        if (!$item && $itemHashid) {
            return redirect()->route('admin.items.index')->with('error', 'Item tidak ditemukan untuk melihat keywords.');
        }
        // Jika tidak ada itemHashid, view index mungkin perlu menampilkan pesan
        // atau tidak menampilkan tabel sama sekali sampai item dipilih.

        // Ganti path view
        return view('admin.keywords.index', compact('item'));
    }

    /**
     * Menyediakan data keywords untuk DataTables.
     * Filter berdasarkan item_hashid dari query string.
     */
    public function getData(Request $request)
    {
        $query = ItemKeyword::with('item')->select('item_keywords.*');
        $itemHashid = $request->query('item_hashid'); // Ambil dari query string

        if ($itemHashid) {
            $decodedId = Hashids::decode($itemHashid);
            if (!empty($decodedId)) {
                $query->where('item_id', $decodedId[0]);
            } else {
                return DataTables::of(collect())->make(true); // Hashid tidak valid
            }
        } else {
            // Jika tidak ada filter item, mungkin kembalikan data kosong?
            // Atau tampilkan semua (bisa sangat banyak) - Perlu pertimbangan
            return DataTables::of(collect())->make(true); // Contoh: kembalikan kosong jika item tidak dispesifikkan
        }


        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('item_name', fn($row) => $row->item?->name ?? 'N/A')
            ->addColumn('action', function ($row) {
                // Menggunakan hashid keyword dan nama route baru
                $editUrl = route('admin.keywords.edit', ['keyword' => $row->hashid ?? $row->id]);
                $deleteUrl = route('admin.keywords.destroy', ['keyword' => $row->hashid ?? $row->id]);
                return '
                <a href="' . $editUrl . '" class="btn btn-sm btn-info">Edit</a>
                <button onclick="deleteKeyword(\'' . $deleteUrl . '\')" class="btn btn-sm btn-danger">Delete</button>
                '; // JS deleteKeyword mungkin perlu tahu item_hashid jika ingin refresh/redirect ke halaman item
            })
            ->editColumn('created_at', fn($row) => $row->created_at?->format('d M Y H:i') ?? '-')
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Menampilkan form untuk menambah keyword baru.
     * Membutuhkan item_hashid via query string.
     */
    public function create(Request $request)
    {
        $itemHashid = $request->query('item_hashid');
        if (!$itemHashid) {
            return redirect()->route('admin.items.index')->with('error', 'Pilih item terlebih dahulu untuk menambah keyword.');
        }
        $decodedId = Hashids::decode($itemHashid);
        if (empty($decodedId)) {
            return redirect()->route('admin.items.index')->with('error', 'Item tidak valid.');
        }
        $item = Item::find($decodedId[0]);
        if (!$item) {
            return redirect()->route('admin.items.index')->with('error', 'Item tidak ditemukan.');
        }

        // Ganti path view
        return view('admin.keywords.create', compact('item'));
    }

    /**
     * Menyimpan keyword baru ke database.
     * item_hashid dikirim dari form.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'item_hashid' => ['required', 'string'],
            'keyword_name' => ['required', 'string', 'max:191'],
        ]);

        $decodedId = Hashids::decode($validatedData['item_hashid']);
        if (empty($decodedId)) {
            return redirect()->back()->withInput()->with('error', 'Item tidak valid.');
        }
        $itemId = $decodedId[0];
        $item = Item::find($itemId); // Perlu untuk nama item dan validasi unik

        if (!$item) {
            return redirect()->back()->withInput()->with('error', 'Item tidak ditemukan.');
        }

        // Validasi unik
        $request->validate([
            'keyword_name' => Rule::unique('item_keywords')->where(fn($query) => $query->where('item_id', $itemId)),
        ]);

        try {
            ItemKeyword::create([ // Model tetap ItemKeyword
                'item_id' => $itemId,
                'keyword_name' => strtolower(trim($validatedData['keyword_name'])),
            ]);
            // Redirect ke index keyword DENGAN item_hashid lagi, gunakan nama route baru
            return redirect()->route('admin.keywords.index', ['item_hashid' => $validatedData['item_hashid']])
                ->with('success', 'Keyword berhasil ditambahkan untuk item "' . $item->name . '".');
        } catch (\Exception $e) {
            Log::error('Keyword Creation Error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan keyword.');
        }
    }

    /**
     * Menampilkan form untuk mengedit keyword.
     * $keyword di-resolve dengan hashid.
     */
    public function edit(ItemKeyword $keyword) // Model tetap ItemKeyword
    {
        $item = $keyword->item;
        if (!$item) {
            abort(404, 'Item untuk keyword ini tidak ditemukan.');
        }
        // Ganti path view
        return view('admin.keywords.edit', compact('item', 'keyword'));
    }

    /**
     * Mengupdate keyword di database.
     * $keyword di-resolve dengan hashid.
     */
    public function update(Request $request, ItemKeyword $keyword) // Model tetap ItemKeyword
    {
        $item = $keyword->item;
        if (!$item) {
            abort(403, 'Item untuk keyword ini tidak ditemukan, aksi tidak diizinkan.');
        }
        $itemId = $item->id;

        $validatedData = $request->validate([
            'keyword_name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('item_keywords')->where(fn($query) => $query->where('item_id', $itemId))->ignore($keyword->id),
            ],
        ]);

        try {
            $keyword->update([
                'keyword_name' => strtolower(trim($validatedData['keyword_name'])),
            ]);
            // Redirect ke index keyword DENGAN item_hashid, gunakan nama route baru
            return redirect()->route('admin.keywords.index', ['item_hashid' => $item->hashid ?? $item->id])
                ->with('success', 'Keyword berhasil diperbarui untuk item "' . $item->name . '".');
        } catch (\Exception $e) {
            Log::error('Keyword Update Error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat memperbarui keyword.');
        }
    }

    /**
     * Menghapus keyword dari database.
     * $keyword di-resolve dengan hashid.
     */
    public function destroy(ItemKeyword $keyword) // Model tetap ItemKeyword
    {
        try {
            $keywordName = $keyword->keyword_name;
            $itemName = $keyword->item?->name ?? 'N/A';
            $itemHashid = $keyword->item?->hashid ?? null;

            $keyword->delete();

            $message = "Keyword '{$keywordName}' berhasil dihapus.";
            if ($itemName !== 'N/A') {
                $message .= " Dari item \"{$itemName}\".";
            }

            // Kirim itemHashid agar JS bisa redirect ke halaman keywords item itu jika perlu
            return response()->json(['message' => $message, 'item_hashid_redirect' => $itemHashid]);
        } catch (\Exception $e) {
            Log::error('Keyword Deletion Error: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan saat menghapus keyword.'], 500);
        }
    }
}
