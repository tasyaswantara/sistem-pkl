<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jurusan')
            ->where('nama', 'DMC')
            ->update([
                'nama' => 'Desain Media Class',
                'updated_at' => now(),
            ]);

        DB::table('jurusan')
            ->where('nama', 'PVC')
            ->update([
                'nama' => 'Photo Video Class',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('jurusan')
            ->where('nama', 'Desain Media Class')
            ->update([
                'nama' => 'DMC',
                'updated_at' => now(),
            ]);

        DB::table('jurusan')
            ->where('nama', 'Photo Video Class')
            ->update([
                'nama' => 'PVC',
                'updated_at' => now(),
            ]);
    }
};
