<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceCost;
use Illuminate\Support\Facades\DB;

class ServiceCostSeeder extends Seeder
{
    public function run()
    {
        DB::table('service_costs')->truncate(); // Hapus data lama jika ada

        ServiceCost::create([
            'name' => 'shipping_delivery_only',
            'label' => 'Biaya Pengantaran Saja',
            'cost' => 50000,
            'description' => 'Biaya standar untuk pengantaran barang tanpa instalasi.',
            'is_active' => true,
        ]);

        ServiceCost::create([
            'name' => 'shipping_delivery_install', // Ini adalah biaya TOTAL jika antar + pasang
            'label' => 'Biaya Pengantaran dan Pemasangan',
            'cost' => 75000,
            'description' => 'Biaya untuk pengantaran barang sekaligus pemasangan oleh teknisi.',
            'is_active' => true,
        ]);

        // Anda bisa menambahkan biaya lain di sini jika perlu
        // Misalnya, biaya instalasi saja jika customer ambil di tempat tapi minta dipasangkan:
        /*
        ServiceCost::create([
            'name' => 'installation_pickup_only',
            'label' => 'Biaya Pemasangan (Ambil di Tempat)',
            'cost' => 30000,
            'description' => 'Biaya untuk pemasangan oleh teknisi jika barang diambil sendiri oleh customer.',
            'is_active' => true,
        ]);
        */
    }
}
