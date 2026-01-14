<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guru_pembimbing', function (Blueprint $table) {
            $table->id();

            // relasi ke users
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('nip')->unique();
            $table->foreignId('jurusan_id')
                ->constrained('jurusan')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guru_pembimbing');
    }
};
