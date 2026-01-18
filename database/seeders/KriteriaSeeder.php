<?php

namespace Database\Seeders;

use App\Models\Kriteria;
use Illuminate\Database\Seeder;

class KriteriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kriteria = [
            ['nama_kriteria' => 'Nilai Akademik', 'tipe' => 'benefit'],
            ['nama_kriteria' => 'Perangkat yang Dimiliki', 'tipe' => 'benefit'],
            ['nama_kriteria' => 'Kapasitas Tempat PKL', 'tipe' => 'benefit'],
        ];

        foreach ($kriteria as $item) {
            Kriteria::firstOrCreate(
                ['nama_kriteria' => $item['nama_kriteria']],
                ['tipe' => $item['tipe']]
            );
        }
    }
}
