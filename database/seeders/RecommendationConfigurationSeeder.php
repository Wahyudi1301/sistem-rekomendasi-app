<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RecommendationConfiguration;
// use Illuminate\Support\Facades\DB; // Tidak perlu jika pakai truncate model

class RecommendationConfigurationSeeder extends Seeder
{
    public function run()
    {
        RecommendationConfiguration::truncate(); // Hapus data lama untuk memastikan bersih

        $configs = [
            // Bobot Overall untuk Rekomendasi (Filter & Item-to-Item)
            // Anda bisa punya set bobot berbeda jika mau, tapi untuk simpelnya kita pakai ini dulu
            ['parameter_name' => 'content_based_overall_weight', 'parameter_value' => '0.2', 'description' => 'Bobot global untuk Content-Based (keyword).'],
            ['parameter_name' => 'case_based_overall_weight', 'parameter_value' => '0.8', 'description' => 'Bobot global untuk Case-Based (atribut).'],

            // Bobot Khusus untuk Ranking Berdasarkan Filter (Jika ingin dibedakan, jika tidak, ini bisa diabaikan dan pakai overall)
            // ['parameter_name' => 'filter_content_based_weight', 'parameter_value' => '0.1', 'description' => 'Bobot Content-Based saat filter aktif.'],
            // ['parameter_name' => 'filter_case_based_weight', 'parameter_value' => '0.9', 'description' => 'Bobot Case-Based saat filter aktif.'],

            // --- Atribut Case-Based (Total bobot di bawah ini idealnya = 1.0) ---
            ['parameter_name' => 'cb_attr_price_weight', 'parameter_value' => '0.30', 'description' => 'Bobot atribut Harga.'],
            ['parameter_name' => 'cb_attr_price_max_value', 'parameter_value' => '15000000', 'description' => 'Harga tertinggi estimasi untuk normalisasi.'],

            ['parameter_name' => 'cb_attr_btu_capacity_weight', 'parameter_value' => '0.20', 'description' => 'Bobot atribut Kapasitas BTU.'],
            ['parameter_name' => 'cb_attr_btu_capacity_max_value', 'parameter_value' => '24000', 'description' => 'BTU tertinggi estimasi untuk normalisasi.'],

            ['parameter_name' => 'cb_attr_power_consumption_watt_weight', 'parameter_value' => '0.15', 'description' => 'Bobot atribut Konsumsi Daya (Watt).'],
            ['parameter_name' => 'cb_attr_power_consumption_watt_max_value', 'parameter_value' => '2500', 'description' => 'Watt tertinggi estimasi untuk normalisasi.'],

            ['parameter_name' => 'cb_attr_is_inverter_weight', 'parameter_value' => '0.15', 'description' => 'Bobot atribut Tipe Inverter (boolean).'],
            ['parameter_name' => 'cb_is_inverter_max_value', 'parameter_value' => '1', 'description' => 'Nilai maks untuk normalisasi boolean (1-0=1).'],

            // BARU: Ukuran Ruang (berdasarkan room_size_max_sqm)
            ['parameter_name' => 'cb_attr_room_size_weight', 'parameter_value' => '0.10', 'description' => 'Bobot atribut Ukuran Ruang Maks (m²).'],
            ['parameter_name' => 'cb_attr_room_size_max_value', 'parameter_value' => '30', 'description' => 'Ukuran ruang maks (m²) estimasi untuk normalisasi.'],

            // BARU: Garansi Kompresor (berdasarkan warranty_compressor_years)
            ['parameter_name' => 'cb_attr_warranty_weight', 'parameter_value' => '0.10', 'description' => 'Bobot atribut Garansi Kompresor (tahun).'],
            ['parameter_name' => 'cb_attr_warranty_max_value', 'parameter_value' => '10', 'description' => 'Durasi garansi maks (tahun) estimasi untuk normalisasi.'],
            // Total bobot atribut: 0.30 + 0.20 + 0.15 + 0.15 + 0.10 + 0.10 = 1.0

            // Kolom cb_attr_specX_column dan _name_ref tidak lagi dipakai jika kita map langsung ke nama kolom di Item
        ];

        foreach ($configs as &$config) {
            $config['created_at'] = now();
            $config['updated_at'] = now();
            // Hapus user_id jika Anda sudah menghapusnya dari migrasi dan model
            // unset($config['user_id']);
        }
        unset($config);

        RecommendationConfiguration::insert($configs);
    }
}
