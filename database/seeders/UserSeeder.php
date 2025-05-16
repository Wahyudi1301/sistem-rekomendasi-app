<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Customer;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Seed data for Admin
        User::create([
            'name' => 'Admin Utama',
            'email' => '210103172@mhs.udb.ac.id',
            'password' => Hash::make('admin123'),
            'phone_number' => '081326886970',
            'address' => 'wanteyan bakungan karangdowo',
            'gender' => 'male', // Ganti ke 'male' atau 'female'
            'status' => 'active',
            'role' => User::ROLE_ADMIN, // Tetapkan role admin
            'email_verified_at' => now(),
        ]);

        // Contoh user Staff
        User::create([
            'name' => 'Staff Lapangan',
            'email' => 'staff@example.com',
            'password' => Hash::make('staff123'),
            'phone_number' => '081211112222',
            'address' => 'Jl. Operasional No. 5',
            'gender' => 'female', // Ganti ke 'male' atau 'female'
            'status' => 'active',
            'role' => User::ROLE_STAFF, // Default atau bisa eksplisit
            'email_verified_at' => now(),
        ]);

        // Seed data for Customer
        Customer::create([
            'name' => 'user bca',
            'email' => 'bca@gmail.com',
            'password' => Hash::make('bca123'),
            'phone_number' => '087712345678',
            'address' => 'Jl. Rumah Customer No. 10',
            'gender' => 'female', // Ganti ke 'male' atau 'female'
            'status' => 'active',
            // Customer tidak memiliki kolom 'role' di tabel 'customers'
        ]);
    }
}
