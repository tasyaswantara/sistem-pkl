<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateNames = DB::table('industri')
            ->select('nama_industri', DB::raw('COUNT(*) as total'))
            ->groupBy('nama_industri')
            ->having('total', '>', 1)
            ->pluck('nama_industri');

        foreach ($duplicateNames as $nama) {
            $rows = DB::table('industri')
                ->where('nama_industri', $nama)
                ->orderBy('id')
                ->get(['id', 'nama_industri']);

            $suffix = 1;
            foreach ($rows as $row) {
                if ($suffix === 1) {
                    $suffix++;
                    continue;
                }

                $newName = $row->nama_industri . ' (' . $suffix . ')';
                while (DB::table('industri')->where('nama_industri', $newName)->exists()) {
                    $suffix++;
                    $newName = $row->nama_industri . ' (' . $suffix . ')';
                }

                DB::table('industri')->where('id', $row->id)->update([
                    'nama_industri' => $newName,
                ]);
                $suffix++;
            }
        }

        Schema::table('industri', function (Blueprint $table) {
            $table->unique('nama_industri', 'industri_nama_industri_unique');
        });
    }

    public function down(): void
    {
        Schema::table('industri', function (Blueprint $table) {
            $table->dropUnique('industri_nama_industri_unique');
        });
    }
};
