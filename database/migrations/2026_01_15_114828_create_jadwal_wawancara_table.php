<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jadwal_wawancara', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')
                ->constrained('siswa')
                ->cascadeOnDelete();
            $table->foreignId('industri_id')
                ->constrained('industri')
                ->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('waktu')->nullable();
            $table->string('lokasi')->nullable();
            $table->enum('status', ['menunggu', 'dijadwalkan', 'selesai', 'dibatalkan'])
                ->default('menunggu');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['siswa_id', 'industri_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_wawancara');
    }
};
