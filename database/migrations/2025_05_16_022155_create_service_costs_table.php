<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('service_costs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Nama unik untuk identifikasi biaya, misal: 'shipping_delivery_only', 'shipping_delivery_install'
            $table->string('label');          // Label yang bisa ditampilkan ke user, misal: 'Biaya Antar Saja', 'Biaya Antar + Pasang'
            $table->decimal('cost', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_costs');
    }
};
