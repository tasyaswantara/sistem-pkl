<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('cleanup:penempatan', function () {
    $this->info('Cleanup penempatan_pkl dan jadwal_wawancara.');

    if (!$this->confirm('Lanjutkan penghapusan data yang tidak sesuai status baru?', true)) {
        $this->comment('Dibatalkan.');
        return;
    }

    $validStatuses = [
        'belum_memilih',
        'menunggu_konfirmasi',
        'ditolak_sekolah',
        'proses_pengajuan',
        'pengajuan_ditolak_industri',
        'proses_wawancara',
        'diterima_industri',
        'tidak_lolos_industri',
    ];

    $penempatanDeleted = DB::table('penempatan_pkl')
        ->whereNotIn('status', $validStatuses)
        ->delete();

    $jadwalDeleted = DB::table('jadwal_wawancara')
        ->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('penempatan_pkl')
                ->whereColumn('penempatan_pkl.siswa_id', 'jadwal_wawancara.siswa_id')
                ->whereColumn('penempatan_pkl.industri_id', 'jadwal_wawancara.industri_id');
        })
        ->delete();

    $this->info("Penempatan dihapus: {$penempatanDeleted}");
    $this->info("Jadwal wawancara dihapus: {$jadwalDeleted}");
})->purpose('Membersihkan data penempatan/jadwal yang tidak relevan');

Artisan::command('cleanup:dummy-all', function () {
    $this->info('Reset data dummy (penempatan, rekomendasi, logbook, perizinan, penilaian).');

    if (!$this->confirm('Lanjutkan menghapus semua data dummy?', false)) {
        $this->comment('Dibatalkan.');
        return;
    }

    DB::transaction(function () {
        DB::table('detail_penilaian')->delete();
        DB::table('penilaian')->delete();
        DB::table('logbook_komentar')->delete();
        DB::table('logbook')->delete();
        DB::table('perizinan')->delete();
        DB::table('jadwal_wawancara')->delete();
        DB::table('hasil_rekomendasi')->delete();
        DB::table('saw_runs')->delete();
        DB::table('penempatan_pkl')->delete();
    });

    $this->info('Semua data dummy sudah dihapus.');
})->purpose('Reset seluruh data dummy tanpa menghapus akun pengguna');
