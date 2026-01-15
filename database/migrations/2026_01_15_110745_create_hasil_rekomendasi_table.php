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
        Schema::create('hasil_rekomendasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saw_run_id')
                ->constrained('saw_runs')
                ->cascadeOnDelete();
            $table->foreignId('siswa_id')
                ->constrained('siswa')
                ->cascadeOnDelete();
            $table->foreignId('industri_id')
                ->constrained('industri')
                ->cascadeOnDelete();
            $table->decimal('nilai_preferensi', 8, 4)->nullable();
            $table->unsignedInteger('peringkat')->nullable();
            $table->timestamps();

            $table->index(['saw_run_id', 'siswa_id']);
            $table->unique(['saw_run_id', 'siswa_id', 'industri_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_rekomendasi');
    }
};
