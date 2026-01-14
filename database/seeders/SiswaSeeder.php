<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Siswa;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SiswaSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan role siswa ada
        $roleSiswa = Role::firstOrCreate([
            'name' => 'siswa',
            'guard_name' => 'web',
        ]);

        // Buat 10 user siswa
        User::factory(10)->create()->each(function ($user) use ($roleSiswa) {

            // Assign role siswa (Spatie)
            $user->assignRole($roleSiswa);

            // Buat data siswa
            Siswa::create([
                'user_id'       => $user->id,
                'nis'           => fake()->unique()->numerify('23######'),
                'jurusan'       => fake()->randomElement([
                    'DMC',
                    'PVC',
                    'Perhotelan',
                    'Kuliner'
                ]),
                'kelas'         => fake()->randomElement([
                    'XII DMC 1',
                    'XII PVC 1',
                    'XII Perhotelan 1',
                    'XII Kuliner 1'
                ]),
                'status_pkl'    => 'belum',
                'tahun_ajaran'  => '2024/2025',
            ]);
        });
    }
}
