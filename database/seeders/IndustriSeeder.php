<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Industri;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Spatie\Permission\Models\Role;
use App\Models\Jurusan;

class IndustriSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        // Pastikan role industri ada
        $roleIndustri = Role::firstOrCreate([
            'name' => 'perwakilan industri',
            'guard_name' => 'web',
        ]);
        $jurusanIds = Jurusan::pluck('id')->toArray();
        $namaIndustri = [
            'PT Maju Jaya',
            'CV Teknologi Nusantara',
            'PT Industri Digital',
            'PT Solusi Cerdas',
            'PT Kreatif Anak Bangsa',
        ];

        foreach ($namaIndustri as $nama) {
            $user = User::factory()->create([
                'name'  => $nama,
                'email' => fake()->unique()->companyEmail(),
            ]);

            $user->assignRole($roleIndustri);

            Industri::create([
                'user_id'       => $user->id,
                'nama_industri' => $nama,
                'kapasitas'     => rand(3, 15),
                'alamat'        => fake()->address(),
                'reputasi'      => rand(1, 5),
                'jurusan_id'     => $faker->randomElement($jurusanIds),
            ]);
        }
    }
}
