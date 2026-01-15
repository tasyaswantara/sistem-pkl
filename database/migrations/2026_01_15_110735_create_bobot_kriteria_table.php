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
        Schema::create('bobot_kriteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jurusan_id')
                ->constrained('jurusan')
                ->cascadeOnDelete();
            $table->foreignId('kriteria_id')
                ->constrained('kriteria')
                ->cascadeOnDelete();
            $table->decimal('bobot', 5, 2);
            $table->timestamps();

            $table->unique(['jurusan_id', 'kriteria_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bobot_kriteria');
    }
};
