<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usulan_industri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')
                ->constrained('siswa')
                ->cascadeOnDelete();
            $table->foreignId('jurusan_id')
                ->constrained('jurusan')
                ->cascadeOnDelete();
            $table->string('nama_industri');
            $table->text('alamat');
            $table->string('kontak')->nullable();
            $table->text('keterangan')->nullable();
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->timestamps();

            $table->index(['siswa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usulan_industri');
    }
};
