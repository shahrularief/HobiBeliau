<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('game');
            $table->string('set_name')->nullable();
            $table->string('condition');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('shipping_price', 10, 2)->default(0);
            $table->text('shipping_details');
            $table->json('images');
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('listings'); }
};
