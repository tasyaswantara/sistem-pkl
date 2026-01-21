<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            if (!Schema::hasColumn('siswa', 'cv_link')) {
                $table->string('cv_link', 2048)->nullable()->after('kartu_pelajar_link');
            }
        });

        Schema::table('siswa', function (Blueprint $table) {
            if (Schema::hasColumn('siswa', 'cv_links')) {
                $table->dropColumn('cv_links');
            }
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            if (Schema::hasColumn('siswa', 'cv_link')) {
                $table->dropColumn('cv_link');
            }
        });

        Schema::table('siswa', function (Blueprint $table) {
            if (!Schema::hasColumn('siswa', 'cv_links')) {
                $table->json('cv_links')->nullable()->after('kartu_pelajar_link');
            }
        });
    }
};
