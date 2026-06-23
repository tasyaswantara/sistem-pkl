<?php

namespace Tests\Unit;

use App\Models\Siswa;
use App\Services\PenempatanStatusService;
use App\Services\SawRunService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Tests\Support\InvokesPrivateMethods;

class BuildNormalisasiSiswaTest extends TestCase
{
    use InvokesPrivateMethods;

    private function service(): SawRunService
    {
        return new SawRunService($this->createMock(PenempatanStatusService::class));
    }

    public function test_path_1_kriteria_entries_empty_returns_empty_array(): void
    {
        $result = $this->invokePrivate($this->service(), 'buildNormalisasiSiswa', [
            new Collection(),
            new Collection([new Siswa(['nilai_akademik' => 80])]),
        ]);

        $this->assertSame([], $result);
    }

    public function test_path_2_industri_source_entry_is_skipped(): void
    {
        $result = $this->invokePrivate($this->service(), 'buildNormalisasiSiswa', [
            new Collection([
                ['source' => 'industri', 'field' => 'kapasitas'],
            ]),
            new Collection([new Siswa(['nilai_akademik' => 80])]),
        ]);

        $this->assertSame([], $result);
    }

    public function test_path_3_siswa_source_with_empty_siswa_list_returns_zero_max_min(): void
    {
        $result = $this->invokePrivate($this->service(), 'buildNormalisasiSiswa', [
            new Collection([
                ['source' => 'siswa', 'field' => 'nilai_akademik'],
            ]),
            new Collection(),
        ]);

        $this->assertSame(['nilai_akademik' => ['max' => 0, 'min' => 0]], $result);
    }

    public function test_path_4_null_siswa_values_are_ignored(): void
    {
        $result = $this->invokePrivate($this->service(), 'buildNormalisasiSiswa', [
            new Collection([
                ['source' => 'siswa', 'field' => 'nilai_akademik'],
            ]),
            new Collection([
                new Siswa(['nilai_akademik' => null]),
                new Siswa(['nilai_akademik' => 85]),
            ]),
        ]);

        $this->assertSame(['nilai_akademik' => ['max' => 85, 'min' => 85]], $result);
    }

    public function test_path_5_valid_siswa_values_return_max_and_min(): void
    {
        $result = $this->invokePrivate($this->service(), 'buildNormalisasiSiswa', [
            new Collection([
                ['source' => 'siswa', 'field' => 'nilai_akademik'],
            ]),
            new Collection([
                new Siswa(['nilai_akademik' => 80]),
                new Siswa(['nilai_akademik' => 90]),
                new Siswa(['nilai_akademik' => 75]),
            ]),
        ]);

        $this->assertSame(['nilai_akademik' => ['max' => 90, 'min' => 75]], $result);
    }
    public function test_continue_skips_industri_entry_and_processes_next_siswa_entry(): void
{
    $result = $this->invokePrivate($this->service(), 'buildNormalisasiSiswa', [
        new Collection([
            ['source' => 'industri', 'field' => 'kapasitas'],
            ['source' => 'siswa', 'field' => 'nilai_akademik'],
        ]),
        new Collection([
            new Siswa(['nilai_akademik' => 80]),
            new Siswa(['nilai_akademik' => 90]),
        ]),
    ]);
 dump($result);
    $this->assertSame([
        'nilai_akademik' => [
            'max' => 90,
            'min' => 80,
        ],
    ], $result);
}
}
