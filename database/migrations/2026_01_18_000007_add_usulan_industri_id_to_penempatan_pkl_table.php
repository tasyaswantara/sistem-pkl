<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penempatan_pkl', function (Blueprint $table) {
            $table->foreignId('usulan_industri_id')
                ->nullable()
                ->after('industri_id')
                ->constrained('usulan_industri')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('penempatan_pkl', function (Blueprint $table) {
            $table->dropForeign(['usulan_industri_id']);
            $table->dropColumn('usulan_industri_id');
        });
    }
};
