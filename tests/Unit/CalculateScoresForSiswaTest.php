<?php

namespace Tests\Unit;

use App\Models\Industri;
use App\Models\Siswa;
use App\Services\PenempatanStatusService;
use App\Services\SawRunService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Tests\Support\InvokesPrivateMethods;

class CalculateScoresForSiswaTest extends TestCase
{
    use InvokesPrivateMethods;

    private function service(): SawRunService
    {
        return new SawRunService($this->createMock(PenempatanStatusService::class));
    }

    public function test_path_1_pool_available_but_kriteria_entries_empty_returns_zero_score(): void
    {
        $industri = new Industri(['grade' => 'B', 'kapasitas' => 10]);
        $industri->id = 1;

        $result = $this->invokePrivate($this->service(), 'calculateScoresForSiswa', [
            new Siswa(['nilai_akademik' => 80]),
            new Collection([$industri]),
            new Collection(['B' => new Collection([$industri])]),
            new Collection(),
            [],
            [],
        ]);

        $this->assertSame([['industri_id' => 1, 'nilai_preferensi' => 0]], $result);
    }

    public function test_path_2_empty_grade_pool_uses_all_industries_as_fallback(): void
    {
        $industri = new Industri(['grade' => 'A', 'kapasitas' => 10]);
        $industri->id = 1;

        $result = $this->invokePrivate($this->service(), 'calculateScoresForSiswa', [
            new Siswa(['nilai_akademik' => 80]),
            new Collection([$industri]),
            new Collection(['A' => new Collection([$industri])]),
            new Collection(),
            [],
            [],
        ]);

        $this->assertSame([['industri_id' => 1, 'nilai_preferensi' => 0]], $result);
    }

    public function test_path_3_no_industries_available_returns_empty_array(): void
    {
        $result = $this->invokePrivate($this->service(), 'calculateScoresForSiswa', [
            new Siswa(['nilai_akademik' => 80]),
            new Collection(),
            new Collection(),
            new Collection(),
            [],
            [],
        ]);

        $this->assertSame([], $result);
    }

    public function test_path_4_siswa_source_with_max_value_zero_returns_zero_normalization(): void
    {
        $industri = new Industri(['grade' => 'B']);
        $industri->id = 1;

        $result = $this->invokePrivate($this->service(), 'calculateScoresForSiswa', [
            new Siswa(['nilai_akademik' => 80]),
            new Collection([$industri]),
            new Collection(['B' => new Collection([$industri])]),
            new Collection([
                ['source' => 'siswa', 'field' => 'nilai_akademik', 'tipe' => 'benefit', 'bobot' => 1],
            ]),
            ['nilai_akademik' => ['max' => 0, 'min' => 0]],
            [],
        ]);

        $this->assertSame(0, $result[0]['nilai_preferensi']);
    }

    public function test_path_5_industri_source_with_max_value_zero_returns_zero_normalization(): void
    {
        $industri = new Industri(['grade' => 'B', 'kapasitas' => 20]);
        $industri->id = 1;

        $result = $this->invokePrivate($this->service(), 'calculateScoresForSiswa', [
            new Siswa(['nilai_akademik' => 80]),
            new Collection([$industri]),
            new Collection(['B' => new Collection([$industri])]),
            new Collection([
                ['source' => 'industri', 'field' => 'kapasitas', 'tipe' => 'benefit', 'bobot' => 1],
            ]),
            [],
            ['B' => ['kapasitas' => ['max' => 0, 'min' => 0]]],
        ]);

        $this->assertSame(0, $result[0]['nilai_preferensi']);
    }

    public function test_path_6_cost_criteria_with_positive_value_uses_min_divided_by_value(): void
    {
        $industri = new Industri(['grade' => 'B', 'kapasitas' => 20]);
        $industri->id = 1;

        $result = $this->invokePrivate($this->service(), 'calculateScoresForSiswa', [
            new Siswa(['nilai_akademik' => 80]),
            new Collection([$industri]),
            new Collection(['B' => new Collection([$industri])]),
            new Collection([
                ['source' => 'industri', 'field' => 'kapasitas', 'tipe' => 'cost', 'bobot' => 1],
            ]),
            [],
            ['B' => ['kapasitas' => ['max' => 30, 'min' => 10]]],
        ]);

        $this->assertSame(0.5, $result[0]['nilai_preferensi']);
    }

    public function test_path_7_cost_criteria_with_zero_value_returns_zero_normalization(): void
    {
        $industri = new Industri(['grade' => 'B', 'kapasitas' => 0]);
        $industri->id = 1;

        $result = $this->invokePrivate($this->service(), 'calculateScoresForSiswa', [
            new Siswa(['nilai_akademik' => 80]),
            new Collection([$industri]),
            new Collection(['B' => new Collection([$industri])]),
            new Collection([
                ['source' => 'industri', 'field' => 'kapasitas', 'tipe' => 'cost', 'bobot' => 1],
            ]),
            [],
            ['B' => ['kapasitas' => ['max' => 30, 'min' => 10]]],
        ]);

        $this->assertSame(0, $result[0]['nilai_preferensi']);
    }

    public function test_path_8_benefit_criteria_uses_value_divided_by_max_and_sorts_descending(): void
    {
        $industriA = new Industri(['grade' => 'B', 'kapasitas' => 50]);
        $industriA->id = 1;
        $industriB = new Industri(['grade' => 'B', 'kapasitas' => 100]);
        $industriB->id = 2;

        $result = $this->invokePrivate($this->service(), 'calculateScoresForSiswa', [
            new Siswa(['nilai_akademik' => 80]),
            new Collection([$industriA, $industriB]),
            new Collection(['B' => new Collection([$industriA, $industriB])]),
            new Collection([
                ['source' => 'industri', 'field' => 'kapasitas', 'tipe' => 'benefit', 'bobot' => 1],
            ]),
            [],
            ['B' => ['kapasitas' => ['max' => 100, 'min' => 50]]],
        ]);

        $this->assertSame(2, $result[0]['industri_id']);
        $this->assertSame(1.0, $result[0]['nilai_preferensi']);
        $this->assertSame(1, $result[1]['industri_id']);
        $this->assertSame(0.5, $result[1]['nilai_preferensi']);
    }
}
