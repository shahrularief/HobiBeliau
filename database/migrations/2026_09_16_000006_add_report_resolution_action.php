<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('listing_reports', fn (Blueprint $table) => $table->string('resolution_action')->nullable()); }
    public function down(): void { Schema::table('listing_reports', fn (Blueprint $table) => $table->dropColumn('resolution_action')); }
};
