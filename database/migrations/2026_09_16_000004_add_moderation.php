<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('selling_suspended')->default(false));
        Schema::table('listings', fn (Blueprint $table) => $table->boolean('admin_hidden')->default(false));
        Schema::create('moderation_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->string('target_type'); $table->unsignedBigInteger('target_id');
            $table->string('action'); $table->text('reason'); $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('moderation_logs');
        Schema::table('listings', fn (Blueprint $table) => $table->dropColumn('admin_hidden'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('selling_suspended'));
    }
};
