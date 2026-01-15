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
        Schema::create('penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')
                ->constrained('siswa')
                ->cascadeOnDelete();
            $table->foreignId('industri_id')
                ->constrained('industri')
                ->cascadeOnDelete();
            $table->date('tanggal_penilaian')->nullable();
            $table->decimal('total_nilai', 8, 2)->nullable();
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
        Schema::dropIfExists('penilaian');
    }
};
