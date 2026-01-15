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
        Schema::create('logbook_komentar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logbook_id')
                ->constrained('logbook')
                ->cascadeOnDelete();
            $table->foreignId('guru_pembimbing_id')
                ->constrained('guru_pembimbing')
                ->cascadeOnDelete();
            $table->text('komentar');
            $table->timestamps();

            $table->index(['logbook_id', 'guru_pembimbing_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logbook_komentar');
    }
};
