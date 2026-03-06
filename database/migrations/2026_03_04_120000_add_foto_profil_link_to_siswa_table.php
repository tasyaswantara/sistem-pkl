<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('siswa', 'foto_profil_link')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->string('foto_profil_link', 2048)->nullable()->after('kartu_pelajar_link');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('siswa', 'foto_profil_link')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->dropColumn('foto_profil_link');
            });
        }
    }
};
