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
        Schema::create('penempatan_pkl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')
                ->constrained('siswa')
                ->cascadeOnDelete();
            $table->foreignId('industri_id')
                ->nullable()
                ->constrained('industri')
                ->nullOnDelete();
            $table->enum('pilihan_siswa', ['rekomendasi', 'usulan_lain'])->nullable();
            $table->enum('status', [
                'belum_diproses',
                'proses_pengajuan',
                'diterima_industri',
                'ditolak_industri',
            ])->default('belum_diproses');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->foreignId('guru_pembimbing_id')
                ->nullable()
                ->constrained('guru_pembimbing')
                ->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['siswa_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penempatan_pkl');
    }
};
