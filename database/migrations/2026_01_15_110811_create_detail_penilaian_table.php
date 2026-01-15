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
        Schema::create('detail_penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penilaian_id')
                ->constrained('penilaian')
                ->cascadeOnDelete();
            $table->foreignId('aspek_penilaian_id')
                ->constrained('aspek_penilaian')
                ->cascadeOnDelete();
            $table->decimal('nilai', 8, 2);
            $table->timestamps();

            $table->unique(['penilaian_id', 'aspek_penilaian_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_penilaian');
    }
};
