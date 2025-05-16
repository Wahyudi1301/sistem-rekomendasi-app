<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->text('description')->nullable();

            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->foreignId('brand_id')->constrained('brands')->onDelete('restrict');

            $table->unsignedInteger('stock')->default(0);
            $table->string('img')->nullable();
            $table->string('status')->default('available');
            $table->string('sku')->unique()->nullable();

            // Kolom spesifik AC tambahan
            $table->integer('btu_capacity')->nullable();
            $table->integer('power_consumption_watt')->nullable();
            $table->boolean('is_inverter')->default(false)->nullable(); // Nullable agar bisa difilter "semua tipe"
            $table->string('freon_type', 20)->nullable();
            $table->integer('room_size_min_sqm')->nullable();
            $table->integer('room_size_max_sqm')->nullable();
            $table->tinyInteger('warranty_compressor_years')->unsigned()->nullable();
            $table->tinyInteger('warranty_parts_years')->unsigned()->nullable();

            // Kolom atribut generik
            $table->string('main_attribute_1_name', 100)->nullable();
            $table->string('main_attribute_1_value', 191)->nullable();
            $table->string('main_attribute_2_name', 100)->nullable();
            $table->string('main_attribute_2_value', 191)->nullable();
            $table->string('main_attribute_3_name', 100)->nullable();
            $table->string('main_attribute_3_value', 191)->nullable();

            $table->timestamps();

            $table->index('price');
            $table->index('btu_capacity');
            $table->index('is_inverter');
            $table->index('room_size_max_sqm');
            $table->index('warranty_compressor_years');
        });
    }

    public function down()
    {
        Schema::dropIfExists('items');
    }
};
