<?php

namespace Database\Seeders;

use App\Models\Industri;
use App\Models\Logbook;
use App\Models\Siswa;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Carbon\Carbon;

class LogbookSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $siswaList = Siswa::with('jurusan')->get();
        $industriList = Industri::all();

        if ($siswaList->isEmpty() || $industriList->isEmpty()) {
            return;
        }

        $statusList = ['pending', 'disetujui', 'ditolak'];

        foreach ($siswaList as $siswa) {
            $industri = $industriList->firstWhere('jurusan_id', $siswa->jurusan_id) ?? $industriList->random();
            $entries = rand(3, 6);

            for ($i = 0; $i < $entries; $i++) {
                $status = $statusList[array_rand($statusList)];
                $tanggal = Carbon::now()->subDays(rand(1, 30));

                Logbook::create([
                    'siswa_id' => $siswa->id,
                    'industri_id' => $industri->id,
                    'tanggal' => $tanggal->toDateString(),
                    'aktivitas' => $faker->sentence(10),
                    'status_validasi' => $status,
                    'validated_at' => $status === 'pending' ? null : $tanggal->copy()->addHours(rand(1, 6)),
                    'catatan_industri' => $status === 'pending' ? null : $faker->sentence(8),
                ]);
            }
        }
    }
}
