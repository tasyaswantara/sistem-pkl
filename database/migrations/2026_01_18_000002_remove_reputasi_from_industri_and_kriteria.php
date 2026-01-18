<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('industri', 'reputasi')) {
            Schema::table('industri', function (Blueprint $table) {
                $table->dropColumn('reputasi');
            });
        }

        DB::table('kriteria')
            ->where('nama_kriteria', 'Reputasi Perusahaan')
            ->delete();
    }

    public function down(): void
    {
        Schema::table('industri', function (Blueprint $table) {
            $table->integer('reputasi')->after('alamat');
        });

        DB::table('kriteria')->insertOrIgnore([
            'nama_kriteria' => 'Reputasi Perusahaan',
            'tipe' => 'benefit',
        ]);
    }
};
