<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('industri', function (Blueprint $table) {
            $table->id();

            // relasi ke users
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('nama_industri');
            $table->integer('kapasitas');
            $table->text('alamat');
            $table->integer('reputasi');
            $table->foreignId('jurusan_id')
                ->constrained('jurusan')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industri');
    }
};
