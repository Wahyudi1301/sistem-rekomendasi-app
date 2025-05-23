<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,     
            BrandSeeder::class,
            CategorySeeder::class,
            ItemSeeder::class,
            ItemKeywordSeeder::class,
            RecommendationConfigurationSeeder::class,
            ServiceCostSeeder::class,
        ]);
    }
}
