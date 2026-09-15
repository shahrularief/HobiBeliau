<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('listing_reports', function (Blueprint $table) {
            $table->id(); $table->foreignId('listing_id')->constrained()->restrictOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->restrictOnDelete();
            $table->text('reason'); $table->string('status')->default('open');
            $table->text('resolution')->nullable(); $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable(); $table->timestamps();
            $table->unique(['listing_id', 'reporter_id']);
        });
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->text('public_bio')->nullable(); $table->string('location')->nullable();
            $table->text('shipping_policy')->nullable();
        });
        Schema::table('listings', function (Blueprint $table) {
            $table->string('tcgdex_id')->nullable()->index(); $table->string('card_number')->nullable();
            $table->string('catalog_set_id')->nullable(); $table->string('rarity')->nullable();
        });
    }
    public function down(): void {
        Schema::dropIfExists('listing_reports');
        Schema::table('seller_applications', fn (Blueprint $table) => $table->dropColumn(['public_bio','location','shipping_policy']));
        Schema::table('listings', fn (Blueprint $table) => $table->dropColumn(['tcgdex_id','card_number','catalog_set_id','rarity']));
    }
};
