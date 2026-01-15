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
        Schema::create('logbook', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')
                ->constrained('siswa')
                ->cascadeOnDelete();
            $table->foreignId('industri_id')
                ->constrained('industri')
                ->cascadeOnDelete();
            $table->date('tanggal');
            $table->text('aktivitas');
            $table->enum('status_validasi', ['pending', 'disetujui', 'ditolak'])
                ->default('pending');
            $table->timestamp('validated_at')->nullable();
            $table->text('catatan_industri')->nullable();
            $table->timestamps();

            $table->index(['siswa_id', 'status_validasi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logbook');
    }
};
