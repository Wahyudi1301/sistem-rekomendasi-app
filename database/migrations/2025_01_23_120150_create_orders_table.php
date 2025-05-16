<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Migrasi orders (database/migrations/xxxx_xx_xx_xxxxxx_create_orders_table.php)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Admin yg handle
            $table->decimal('total_item_price', 12, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('installation_cost', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('delivery_method');
            $table->string('delivery_option')->nullable();
            $table->date('preferred_delivery_date')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('order_status')->default('pending_payment');
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
