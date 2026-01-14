<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswa', function (Blueprint $table) {
            $table->id();

            // Relasi ke akun login
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->unique();

            // Identitas siswa
            $table->string('nis')->unique();

            // Relasi ke jurusan
            $table->foreignId('jurusan_id')
                ->constrained('jurusan')
                ->cascadeOnDelete();

            $table->string('kelas');

            // Nilai akademik (untuk seleksi / SAW)
            $table->integer('nilai_akademik');
            $table->integer('perangkat');

            // Status PKL
            $table->enum('status_pkl', ['belum', 'berjalan', 'selesai'])
                ->default('belum');

            $table->string('tahun_ajaran');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa');
    }
};
