<?php

namespace Database\Seeders;

use App\Models\BobotKriteria;
use App\Models\Jurusan;
use App\Models\Kriteria;
use Illuminate\Database\Seeder;

class BobotKriteriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bobotMap = [
            'Nilai Akademik' => 0.30,
            'Perangkat yang Dimiliki' => 0.25,
            'Kapasitas Tempat PKL' => 0.45,
        ];

        $jurusanList = Jurusan::all();
        $kriteriaList = Kriteria::all()->keyBy('nama_kriteria');

        foreach ($jurusanList as $jurusan) {
            foreach ($bobotMap as $namaKriteria => $bobot) {
                $kriteria = $kriteriaList->get($namaKriteria);
                if (!$kriteria) {
                    continue;
                }

                BobotKriteria::updateOrCreate(
                    [
                        'jurusan_id' => $jurusan->id,
                        'kriteria_id' => $kriteria->id,
                    ],
                    ['bobot' => $bobot]
                );
            }
        }
    }
}
