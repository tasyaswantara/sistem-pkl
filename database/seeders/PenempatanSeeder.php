<?php

namespace Database\Seeders;

use App\Models\GuruPembimbing;
use App\Models\Industri;
use App\Models\PenempatanPKL;
use App\Models\Siswa;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PenempatanSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $siswaList = Siswa::all();
        $industriList = Industri::all();
        $guruList = GuruPembimbing::with('jurusan')->get();

        if ($siswaList->isEmpty() || $industriList->isEmpty()) {
            return;
        }

        $statusPool = [
            'belum_memilih',
            'menunggu_konfirmasi',
            'proses_pengajuan',
            'pengajuan_ditolak_industri',
            'proses_wawancara',
            'diterima_industri',
            'tidak_lolos_industri',
        ];

        foreach ($siswaList as $siswa) {
            $industri = $industriList->firstWhere('jurusan_id', $siswa->jurusan_id) ?? $industriList->random();
            $status = $statusPool[array_rand($statusPool)];
            $guru = $guruList->firstWhere('jurusan_id', $siswa->jurusan_id);

            PenempatanPKL::updateOrCreate(
                ['siswa_id' => $siswa->id],
                [
                    'industri_id' => $industri->id,
                    'pilihan_siswa' => $faker->randomElement(['rekomendasi', 'usulan_lain']),
                    'status' => $status,
                    'guru_pembimbing_id' => $status === 'diterima_industri' ? ($guru->id ?? null) : null,
                    'keterangan' => in_array($status, ['pengajuan_ditolak_industri', 'tidak_lolos_industri', 'ditolak_sekolah'], true) ? 'Perlu penempatan ulang' : null,
                ]
            );
        }
    }
}
