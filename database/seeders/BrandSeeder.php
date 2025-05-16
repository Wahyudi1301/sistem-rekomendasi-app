<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand; // Pastikan namespace model benar
use Illuminate\Support\Facades\DB;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Kosongkan tabel dulu jika perlu (hati-hati di production)
        // DB::table('brands')->delete(); // Atau Brand::truncate(); jika tidak ada foreign key constraint

        $brands = [
            ['name' => 'Daikin', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Panasonic', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'LG', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sharp', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Samsung', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gree', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Midea', 'created_at' => now(), 'updated_at' => now()],
        ];

        Brand::insert($brands); // Lebih efisien untuk multiple insert
    }
}
