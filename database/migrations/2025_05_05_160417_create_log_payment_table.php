<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('gateway_reference_id')->unique();
            $table->string('gateway_transaction_id')->nullable()->unique();
            $table->string('payment_method_gateway')->nullable();
            $table->string('payment_channel')->nullable();
            $table->string('transaction_status');
            $table->decimal('amount', 12, 2);
            $table->timestamp('transaction_time')->nullable();
            $table->timestamp('settlement_time')->nullable();
            $table->timestamp('expiry_time')->nullable();
            $table->text('snap_token')->nullable(); // <-- PASTIKAN KOLOM INI ADA (bisa text atau longText)
            $table->text('gateway_response_payload')->nullable();
            $table->string('fraud_status')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
