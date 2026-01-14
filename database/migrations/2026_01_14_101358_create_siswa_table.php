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
        Schema::create('siswa', function (Blueprint $table) {
            $table->id();

            // Relasi ke users (akun login)
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->unique();

            // Data akademik siswa
            $table->string('nis')->unique();
            $table->string('jurusan');
            $table->string('kelas');

            // Status PKL
            $table->enum('status_pkl', ['belum', 'berjalan', 'selesai'])
                ->default('belum');

            $table->string('tahun_ajaran');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswa');
    }
};
