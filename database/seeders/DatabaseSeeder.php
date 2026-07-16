<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    // public function run(): void
    // {
    //     // User::factory(10)->create();

    //     User::factory()->create([
    //         'name' => 'Test User',
    //         'email' => 'test@example.com',
    //     ]);
    // }
    // public function run()
    // {
    //     $this->call(AdminUserSeeder::class);
    // }
    public function run()
    {
        User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password')
            ]
        );

        $this->call(AdminUserSeeder::class);
        $this->call(PermissionsSeeder::class);
        $this->call(JurusanSeeder::class);
        $this->call(KriteriaSeeder::class);
        $this->call(BobotKriteriaSeeder::class);
        $this->call(AspekPenilaianSeeder::class);
        $this->call(SiswaSeeder::class);
        $this->call(GuruPembimbingSeeder::class);
        $this->call(IndustriSeeder::class);
        $this->call(PenempatanSeeder::class);
        $this->call(LogbookSeeder::class);
        $this->call(PerizinanSeeder::class);
        $this->call(PenilaianSeeder::class);
    }
}
