<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_pkl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('industri_id')->constrained('industri')->cascadeOnDelete();
            $table->date('tanggal');
            $table->timestamp('check_in_at');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_m', 8, 2)->nullable();
            $table->decimal('distance_to_industri_m', 10, 2)->nullable();
            $table->boolean('is_within_geofence')->default(false);
            $table->string('status', 32);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['siswa_id', 'tanggal']);
            $table->index(['tanggal', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_pkl');
    }
};
