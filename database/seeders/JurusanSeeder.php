<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Jurusan;

class JurusanSeeder extends Seeder
{
    public function run(): void
    {
        $data = ['Desain Media Class', 'Photo Video Class', 'Perhotelan', 'Kuliner'];

        foreach ($data as $nama) {
            Jurusan::firstOrCreate(['nama' => $nama]);
        }
    }
}
