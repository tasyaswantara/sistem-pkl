<?php

namespace Database\Seeders;

use App\Models\Industri;
use App\Models\Perizinan;
use App\Models\Siswa;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Carbon\Carbon;

class PerizinanSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $siswaList = Siswa::all();
        $industriList = Industri::all();

        if ($siswaList->isEmpty() || $industriList->isEmpty()) {
            return;
        }

        $statusList = ['menunggu', 'disetujui', 'ditolak'];
        $jenisList = ['Sakit', 'Izin keluarga', 'Keperluan sekolah', 'Lainnya'];

        foreach ($siswaList as $siswa) {
            $industri = $industriList->firstWhere('jurusan_id', $siswa->jurusan_id) ?? $industriList->random();
            $status = $statusList[array_rand($statusList)];
            $mulai = Carbon::now()->subDays(rand(1, 14));
            $selesai = (clone $mulai)->addDays(rand(1, 3));

            Perizinan::create([
                'siswa_id' => $siswa->id,
                'industri_id' => $industri->id,
                'created_by' => $siswa->user_id,
                'jenis_izin' => $jenisList[array_rand($jenisList)],
                'tanggal_mulai' => $mulai->toDateString(),
                'tanggal_selesai' => $selesai->toDateString(),
                'status' => $status,
                'catatan_industri' => $status === 'menunggu' ? null : $faker->sentence(8),
            ]);
        }
    }
}
