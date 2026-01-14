<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Siswa;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Faker\Factory as Faker;
use App\Models\Jurusan;

class SiswaSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Pastikan role siswa ada
        $roleSiswa = Role::firstOrCreate([
            'name' => 'siswa',
            'guard_name' => 'web',
        ]);

        // Ambil ID jurusan yang BENAR dari DB
        $jurusanIds = Jurusan::pluck('id')->toArray();

        User::factory(10)->create()->each(function ($user) use ($roleSiswa, $faker, $jurusanIds) {

            // Assign role siswa
            $user->assignRole($roleSiswa);

            // Create data siswa
            Siswa::create([
                'user_id'        => $user->id,
                'nis'            => $faker->unique()->numerify('23######'),
                'jurusan_id'     => $faker->randomElement($jurusanIds),
                'kelas'          => $faker->randomElement([
                    'XII RPL 1',
                    'XII RPL 2',
                    'XII TKJ 1',
                ]),
                'nilai_akademik' => $faker->numberBetween(70, 95),
                'perangkat'      => $faker->numberBetween(60, 100),
                'status_pkl'     => 'belum',
                'tahun_ajaran'   => '2024/2025',
            ]);
        });
    }
}
