<?php

namespace Database\Seeders;

use App\Models\AspekPenilaian;
use App\Models\Industri;
use App\Models\Penilaian;
use App\Models\Siswa;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Carbon\Carbon;

class PenilaianSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $siswaList = Siswa::all();
        $industriList = Industri::all();
        $aspekList = AspekPenilaian::all();

        if ($siswaList->isEmpty() || $industriList->isEmpty() || $aspekList->isEmpty()) {
            return;
        }

        foreach ($siswaList as $siswa) {
            $industri = $industriList->firstWhere('jurusan_id', $siswa->jurusan_id) ?? $industriList->random();
            $tanggal = Carbon::now()->subDays(rand(1, 30));
            $detailRows = [];
            $total = 0;

            foreach ($aspekList as $aspek) {
                $nilai = rand(70, 100);
                $detailRows[] = [
                    'aspek_penilaian_id' => $aspek->id,
                    'nilai' => $nilai,
                ];
                $total += $nilai * (float) $aspek->bobot;
            }

            $penilaian = Penilaian::create([
                'siswa_id' => $siswa->id,
                'industri_id' => $industri->id,
                'tanggal_penilaian' => $tanggal->toDateString(),
                'total_nilai' => round($total, 2),
                'catatan' => $faker->sentence(10),
            ]);

            foreach ($detailRows as $detail) {
                $penilaian->detailPenilaian()->create($detail);
            }
        }
    }
}
