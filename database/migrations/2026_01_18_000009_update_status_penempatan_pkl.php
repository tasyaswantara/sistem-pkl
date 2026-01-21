<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE penempatan_pkl MODIFY status ENUM(
            'belum_diproses',
            'proses_pengajuan',
            'diterima_industri',
            'ditolak_industri',
            'belum_memilih',
            'menunggu_konfirmasi',
            'ditolak_sekolah',
            'pengajuan_ditolak_industri',
            'proses_wawancara',
            'tidak_lolos_industri'
        ) NOT NULL DEFAULT 'belum_diproses'");

        DB::table('penempatan_pkl')->where('status', 'belum_diproses')->update([
            'status' => 'belum_memilih',
        ]);
        DB::table('penempatan_pkl')->where('status', 'ditolak_industri')->update([
            'status' => 'pengajuan_ditolak_industri',
        ]);

        DB::statement("ALTER TABLE penempatan_pkl MODIFY status ENUM(
            'belum_memilih',
            'menunggu_konfirmasi',
            'ditolak_sekolah',
            'proses_pengajuan',
            'pengajuan_ditolak_industri',
            'proses_wawancara',
            'diterima_industri',
            'tidak_lolos_industri'
        ) NOT NULL DEFAULT 'belum_memilih'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE penempatan_pkl MODIFY status ENUM(
            'belum_diproses',
            'proses_pengajuan',
            'diterima_industri',
            'ditolak_industri'
        ) NOT NULL DEFAULT 'belum_diproses'");
    }
};
