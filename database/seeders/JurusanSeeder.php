<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Jurusan;

class JurusanSeeder extends Seeder
{
    public function run(): void
    {
        $data = ['DMC', 'PVC', 'Perhotelan', 'Kuliner'];

        foreach ($data as $nama) {
            Jurusan::firstOrCreate(['nama' => $nama]);
        }
    }
}
