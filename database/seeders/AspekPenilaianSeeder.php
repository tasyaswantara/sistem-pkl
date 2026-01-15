<?php

namespace Database\Seeders;

use App\Models\AspekPenilaian;
use Illuminate\Database\Seeder;

class AspekPenilaianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $aspek = [
            ['nama_aspek' => 'Disiplin', 'bobot' => 0.20],
            ['nama_aspek' => 'Kerjasama', 'bobot' => 0.20],
            ['nama_aspek' => 'Kualitas Kerja', 'bobot' => 0.20],
            ['nama_aspek' => 'Inisiatif', 'bobot' => 0.20],
            ['nama_aspek' => 'Komunikasi', 'bobot' => 0.20],
        ];

        foreach ($aspek as $item) {
            AspekPenilaian::firstOrCreate(
                ['nama_aspek' => $item['nama_aspek']],
                ['bobot' => $item['bobot']]
            );
        }
    }
}
