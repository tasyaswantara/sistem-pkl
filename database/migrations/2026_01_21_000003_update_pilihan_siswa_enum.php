<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE penempatan_pkl MODIFY pilihan_siswa ENUM('rekomendasi','usulan_lain','langsung') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE penempatan_pkl MODIFY pilihan_siswa ENUM('rekomendasi','usulan_lain') NULL");
    }
};
