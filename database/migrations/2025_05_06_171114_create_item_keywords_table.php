<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('item_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')
                  ->constrained('items')
                  ->onDelete('cascade');

            $table->string('keyword_name', 191); // 191 untuk kompatibilitas indeks default MySQL
            $table->timestamps();

            $table->unique(['item_id', 'keyword_name']);
            $table->index('keyword_name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('item_keywords');
    }
};