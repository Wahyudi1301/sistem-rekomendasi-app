<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('recommendation_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('parameter_name', 191)->unique();
            $table->text('parameter_value');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('recommendation_configurations');
    }
};
