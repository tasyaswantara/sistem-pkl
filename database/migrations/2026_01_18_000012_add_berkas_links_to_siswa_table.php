<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('bpjs_link', 2048)->nullable()->after('tahun_ajaran');
            $table->string('kartu_pelajar_link', 2048)->nullable()->after('bpjs_link');
            $table->string('cv_link', 2048)->nullable()->after('kartu_pelajar_link');
            $table->json('portofolio_links')->nullable()->after('cv_link');
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn([
                'bpjs_link',
                'kartu_pelajar_link',
                'cv_link',
                'portofolio_links',
            ]);
        });
    }
};
