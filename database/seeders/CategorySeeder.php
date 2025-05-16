<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category; // Pastikan namespace model benar
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // DB::table('categories')->delete();

        $categories = [
            ['name' => 'AC Split Standard', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'AC Split Inverter', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'AC Low Watt', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'AC 0.5 PK', 'created_at' => now(), 'updated_at' => now()], // Ukuran
            ['name' => 'AC 0.75 PK (3/4 PK)', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'AC 1 PK', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'AC 1.5 PK', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'AC 2 PK', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'AC Portable', 'created_at' => now(), 'updated_at' => now()], // Tipe lain
        ];

        Category::insert($categories);
    }
}
