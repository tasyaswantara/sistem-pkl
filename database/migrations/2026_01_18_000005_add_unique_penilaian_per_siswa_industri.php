<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('penilaian')) {
            return;
        }

        $duplicates = DB::table('penilaian')
            ->select('siswa_id', 'industri_id', DB::raw('MAX(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->groupBy('siswa_id', 'industri_id')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicates as $row) {
            $deleteIds = DB::table('penilaian')
                ->where('siswa_id', $row->siswa_id)
                ->where('industri_id', $row->industri_id)
                ->where('id', '!=', $row->keep_id)
                ->pluck('id');

            if ($deleteIds->isNotEmpty()) {
                DB::table('detail_penilaian')->whereIn('penilaian_id', $deleteIds)->delete();
                DB::table('penilaian')->whereIn('id', $deleteIds)->delete();
            }
        }

        Schema::table('penilaian', function (Blueprint $table) {
            $table->unique(['siswa_id', 'industri_id'], 'penilaian_siswa_industri_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('penilaian')) {
            return;
        }

        Schema::table('penilaian', function (Blueprint $table) {
            $table->dropUnique('penilaian_siswa_industri_unique');
        });
    }
};
