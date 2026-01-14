<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\GuruPembimbing;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Spatie\Permission\Models\Role;
use App\Models\Jurusan;

class GuruPembimbingSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        // Pastikan role guru ada
        $roleGuru = Role::firstOrCreate([
            'name' => 'guru pembimbing',
            'guard_name' => 'web',
        ]);
        $jurusanIds = Jurusan::pluck('id')->toArray();
        // Ambil beberapa user baru khusus guru
        User::factory(5)->create()->each(function ($user) use ($roleGuru, $faker, $jurusanIds) {

            // Assign role
            $user->assignRole($roleGuru);

            // Buat data guru
            GuruPembimbing::create([
                'user_id'    => $user->id,
                'nip'        => fake()->unique()->numerify('19########'),
                'jurusan_id'     => $faker->randomElement($jurusanIds),
            ]);
        });
    }
}
