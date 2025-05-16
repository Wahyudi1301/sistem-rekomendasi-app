<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\ItemKeyword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ItemKeywordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // DB::table('item_keywords')->delete();

        $items = Item::with(['brand', 'category'])->get(); // Ambil semua item dengan relasinya
        $allKeywordsData = [];

        foreach ($items as $item) {
            $textParts = [
                strtolower($item->name ?? ''),
                strtolower($item->description ?? ''),
                strtolower($item->sku ?? ''),
                strtolower($item->brand?->name ?? ''),
                strtolower($item->category?->name ?? ''),
                strtolower($item->freon_type ?? ''),
                $item->is_inverter ? 'inverter' : 'standard', // Tambahkan standard jika bukan inverter
                $item->btu_capacity ? (string)$item->btu_capacity . 'btu' : '',
                $item->btu_capacity ? (string)round($item->btu_capacity / 9000, 2) . 'pk' : '', // Estimasi PK
                $item->power_consumption_watt ? (string)$item->power_consumption_watt . 'watt' : '',
                strtolower($item->main_attribute_1_name ?? ''),
                strtolower($item->main_attribute_1_value ?? ''),
                strtolower($item->main_attribute_2_name ?? ''),
                strtolower($item->main_attribute_2_value ?? ''),
                strtolower($item->main_attribute_3_name ?? ''),
                strtolower($item->main_attribute_3_value ?? ''),
                $item->room_size_max_sqm ? (string)$item->room_size_max_sqm . 'm2' : '', // Tambahkan m2 agar beda dengan angka biasa
                $item->warranty_compressor_years ? (string)$item->warranty_compressor_years . 'thngaransi' : '', // thngaransi
            ];

            // Menambahkan kata kunci dari kategori yang lebih spesifik jika ada
            if ($item->btu_capacity) {
                if ($item->btu_capacity <= 5000) $textParts[] = '0.5pk';
                elseif ($item->btu_capacity <= 7500) $textParts[] = '0.75pk';
                elseif ($item->btu_capacity <= 9500) $textParts[] = '1pk';
                elseif ($item->btu_capacity <= 12500) $textParts[] = '1.5pk';
                elseif ($item->btu_capacity <= 18500) $textParts[] = '2pk';
            }
            if ($item->power_consumption_watt && $item->btu_capacity && $item->power_consumption_watt < ($item->btu_capacity / 10) * 0.9) { // Estimasi low watt
                $textParts[] = 'lowwatt';
            }


            $textToProcess = implode(' ', array_filter($textParts));
            // Hapus karakter non-alfanumerik kecuali spasi dan strip, lalu ganti strip dengan spasi
            $textToProcess = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $textToProcess);
            $textToProcess = str_replace('-', ' ', $textToProcess);
            // Hapus spasi berlebih
            $textToProcess = preg_replace('/\s+/', ' ', $textToProcess);

            $tokens = array_filter(array_unique(explode(' ', $textToProcess)));

            $stopwords = ['dan', 'di', 'atau', 'dengan', 'untuk', 'yang', 'ini', 'itu', 'ac', 'pk', 'rp', 'harga', 'jual', 'watt', 'btu', 'tipe', 'jenis', 'series', 'fitur', 'mode', 'pendingin', 'pendinginan', 'listrik', 'udara', 'garansi', 'tahun', 'konsumsi', 'daya', 'kapasitas', 'dari', 'ke', 'unit', 'pada', 'hingga', 'lebih', 'juga', 'sangat', 'sekali', 'control', 'operation', 'cocok', 'ruangan', 'h', 'memiliki', 'dilengkapi', 'adalah', 'merupakan', 'sebagai', 'produk', 'seri', 'membuat', 'akan', 'dapat', 'namun', 'serta', 'lain', 'menggunakan', 'efisien', 'berbagai'];

            $keywords = array_diff($tokens, $stopwords);

            foreach ($keywords as $keyword) {
                $trimmedKeyword = trim($keyword);
                // Hanya simpan keyword yang lebih dari 2 karakter dan bukan hanya angka
                if ($trimmedKeyword !== '' && strlen($trimmedKeyword) > 2 && !is_numeric($trimmedKeyword)) {
                    $allKeywordsData[] = [
                        'item_id' => $item->id,
                        'keyword_name' => $trimmedKeyword,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // Chunk data untuk insert agar tidak terlalu besar query-nya
        foreach (array_chunk($allKeywordsData, 500) as $chunk) {
            ItemKeyword::insert($chunk);
        }
    }
}
