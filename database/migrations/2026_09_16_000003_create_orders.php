<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->uuid('checkout_token')->unique();
            $table->string('recipient');
            $table->string('phone');
            $table->text('address');
            $table->unsignedBigInteger('subtotal_cents');
            $table->unsignedBigInteger('shipping_cents');
            $table->unsignedBigInteger('total_cents');
            $table->string('payment_status')->default('not_collected');
            $table->string('status')->default('placed');
            $table->string('tracking')->nullable();
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('game');
            $table->string('condition');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_cents');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('order_items'); Schema::dropIfExists('orders'); }
};
