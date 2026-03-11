<?php

namespace App\Services;

use App\Enums\PenempatanStatus;
use App\Models\BobotKriteria;
use App\Models\HasilRekomendasi;
use App\Models\Industri;
use App\Models\PenempatanPKL;
use App\Models\SawRun;
use App\Models\Siswa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SawRunService
{
    private const KEEP_RUNS_PER_JURUSAN_TAHUN = 3;

    public function __construct(private PenempatanStatusService $statusService)
    {
    }

    /**
     * @return array{ok:bool,error_key?:string,error_field?:string,rows_count?:int}
     */
    public function run(int $jurusanId, string $tahunAjaran, ?int $actorId): array
    {
        $bobotKriteria = BobotKriteria::with('kriteria')
            ->where('jurusan_id', $jurusanId)
            ->get();

        if ($bobotKriteria->isEmpty()) {
            return [
                'ok' => false,
                'error_field' => 'bobot',
                'error_key' => 'penempatan.errors.kriteria',
            ];
        }

        if (!$this->isTotalBobotValid($bobotKriteria)) {
            return [
                'ok' => false,
                'error_field' => 'bobot',
                'error_key' => 'penempatan.errors.bobot_saw',
            ];
        }

        $siswaList = Siswa::where('jurusan_id', $jurusanId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->get();
        $industriList = Industri::where('jurusan_id', $jurusanId)->get();

        if ($siswaList->isEmpty() || $industriList->isEmpty()) {
            return [
                'ok' => false,
                'error_field' => 'saw',
                'error_key' => 'penempatan.errors.data_kurang',
            ];
        }

        $kriteriaEntries = $this->buildKriteriaEntries($bobotKriteria);
        if ($kriteriaEntries->isEmpty()) {
            return [
                'ok' => false,
                'error_field' => 'saw',
                'error_key' => 'penempatan.errors.kriteria_link',
            ];
        }

        $normalisasiSiswa = $this->buildNormalisasiSiswa($kriteriaEntries, $siswaList);
        $industriesByGrade = $industriList->groupBy('grade');
        $normalisasiIndustriByGrade = $this->buildNormalisasiIndustriByGrade($kriteriaEntries, $industriesByGrade);
        $rowsCount = $this->persistSawRun(
            $jurusanId,
            $tahunAjaran,
            $actorId,
            $siswaList,
            $industriList,
            $industriesByGrade,
            $kriteriaEntries,
            $normalisasiSiswa,
            $normalisasiIndustriByGrade
        );

        $deletedRuns = $this->cleanupOldRuns($jurusanId, $tahunAjaran, self::KEEP_RUNS_PER_JURUSAN_TAHUN);

        Log::info('SAW run completed', [
            'jurusan_id' => $jurusanId,
            'tahun_ajaran' => $tahunAjaran,
            'siswa' => $siswaList->count(),
            'industri' => $industriList->count(),
            'rows_inserted' => $rowsCount,
            'runs_deleted' => $deletedRuns,
        ]);

        return ['ok' => true, 'rows_count' => $rowsCount];
    }

    private function isTotalBobotValid(Collection $bobotKriteria): bool
    {
        $totalBobot = $bobotKriteria->sum('bobot');

        return abs($totalBobot - 1) <= 0.02;
    }

    private function buildKriteriaEntries(Collection $bobotKriteria): Collection
    {
        return $bobotKriteria->map(function ($item) {
            $map = $this->resolveKriteriaMap($item->kriteria->nama_kriteria);

            return [
                'bobot' => $item->bobot,
                'tipe' => $item->kriteria->tipe,
                'source' => $map['source'] ?? null,
                'field' => $map['field'] ?? null,
            ];
        })->filter(function ($entry) {
            return $entry['source'] !== null;
        })->values();
    }

    /**
     * @param Collection<int, array{source:string,field:string}> $kriteriaEntries
     * @param Collection<int, Siswa> $siswaList
     * @return array<string, array{max:float|int,min:float|int}>
     */
    private function buildNormalisasiSiswa(Collection $kriteriaEntries, Collection $siswaList): array
    {
        $normalisasiSiswa = [];
        foreach ($kriteriaEntries as $entry) {
            if ($entry['source'] !== 'siswa') {
                continue;
            }

            $values = $siswaList->pluck($entry['field'])->filter(function ($value) {
                return $value !== null;
            });

            $normalisasiSiswa[$entry['field']] = [
                'max' => $values->max() ?: 0,
                'min' => $values->min() ?: 0,
            ];
        }

        return $normalisasiSiswa;
    }

    /**
     * @param Collection<int, array{source:string,field:string}> $kriteriaEntries
     * @param Collection<string, Collection<int, Industri>> $industriesByGrade
     * @return array<string, array<string, array{max:float|int,min:float|int}>>
     */
    private function buildNormalisasiIndustriByGrade(Collection $kriteriaEntries, Collection $industriesByGrade): array
    {
        $normalisasiIndustriByGrade = [];
        foreach ($industriesByGrade as $grade => $list) {
            foreach ($kriteriaEntries as $entry) {
                if ($entry['source'] !== 'industri') {
                    continue;
                }

                $values = $list->pluck($entry['field'])->filter(function ($value) {
                    return $value !== null;
                });

                $normalisasiIndustriByGrade[$grade][$entry['field']] = [
                    'max' => $values->max() ?: 0,
                    'min' => $values->min() ?: 0,
                ];
            }
        }

        return $normalisasiIndustriByGrade;
    }

    /**
     * @param Collection<int, Siswa> $siswaList
     * @param Collection<int, Industri> $industriList
     * @param Collection<string, Collection<int, Industri>> $industriesByGrade
     * @param Collection<int, array{bobot:float|int,tipe:string,source:string,field:string}> $kriteriaEntries
     * @param array<string, array{max:float|int,min:float|int}> $normalisasiSiswa
     * @param array<string, array<string, array{max:float|int,min:float|int}>> $normalisasiIndustriByGrade
     */
    private function persistSawRun(
        int $jurusanId,
        string $tahunAjaran,
        ?int $actorId,
        Collection $siswaList,
        Collection $industriList,
        Collection $industriesByGrade,
        Collection $kriteriaEntries,
        array $normalisasiSiswa,
        array $normalisasiIndustriByGrade
    ): int {
        $now = now();

        return DB::transaction(function () use (
            $jurusanId,
            $tahunAjaran,
            $actorId,
            $siswaList,
            $industriList,
            $industriesByGrade,
            $kriteriaEntries,
            $normalisasiSiswa,
            $normalisasiIndustriByGrade,
            $now
        ) {
            $run = SawRun::create([
                'jurusan_id' => $jurusanId,
                'tahun_ajaran' => $tahunAjaran,
                'run_at' => $now,
                'created_by' => $actorId,
            ]);

            $rows = [];
            foreach ($siswaList as $siswa) {
                $scores = $this->calculateScoresForSiswa(
                    $siswa,
                    $industriList,
                    $industriesByGrade,
                    $kriteriaEntries,
                    $normalisasiSiswa,
                    $normalisasiIndustriByGrade
                );

                $this->syncInitialPenempatanFromScores((int) $siswa->id, $scores);

                $rank = 1;
                foreach ($scores as $score) {
                    $rows[] = [
                        'saw_run_id' => $run->id,
                        'siswa_id' => $siswa->id,
                        'industri_id' => $score['industri_id'],
                        'nilai_preferensi' => $score['nilai_preferensi'],
                        'peringkat' => $rank,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $rank++;
                }
            }

            if ($rows) {
                HasilRekomendasi::insert($rows);
            }

            return count($rows);
        });
    }

    private function cleanupOldRuns(int $jurusanId, string $tahunAjaran, int $keep): int
    {
        $runIdsToDelete = SawRun::query()
            ->where('jurusan_id', $jurusanId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->orderByDesc('run_at')
            ->orderByDesc('id')
            ->offset($keep)
            ->pluck('id');

        if ($runIdsToDelete->isEmpty()) {
            return 0;
        }

        return SawRun::query()
            ->whereIn('id', $runIdsToDelete)
            ->delete();
    }

    /**
     * @param array<int, array{industri_id:int,nilai_preferensi:float|int}> $scores
     */
    private function syncInitialPenempatanFromScores(int $siswaId, array $scores): void
    {
        $topChoice = $scores[0] ?? null;
        if (!$topChoice) {
            return;
        }

        $existingPenempatan = PenempatanPKL::where('siswa_id', $siswaId)->first();
        if (!$this->shouldUpdatePenempatan($existingPenempatan)) {
            return;
        }

        $oldStatus = $existingPenempatan?->status;
        $penempatan = PenempatanPKL::updateOrCreate(
            ['siswa_id' => $siswaId],
            [
                'industri_id' => null,
                'usulan_industri_id' => null,
                'pilihan_siswa' => null,
                'status' => PenempatanStatus::BELUM_MEMILIH->value,
            ]
        );

        if ($oldStatus !== null) {
            $this->statusService->handleStatusChange($penempatan, $oldStatus);
        }
    }

    private function shouldUpdatePenempatan(?PenempatanPKL $existingPenempatan): bool
    {
        if (!$existingPenempatan) {
            return true;
        }

        return in_array(
            $existingPenempatan->status,
            [
                PenempatanStatus::BELUM_MEMILIH->value,
                PenempatanStatus::DITOLAK_SEKOLAH->value,
                PenempatanStatus::PENGAJUAN_DITOLAK_INDUSTRI->value,
                PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value,
            ],
            true
        );
    }

    /**
     * @param array<int, array{bobot:float|int,tipe:string,source:string,field:string}> $kriteriaEntries
     * @param array<string, array{max:float|int,min:float|int}> $normalisasiSiswa
     * @param array<string, array<string, array{max:float|int,min:float|int}>> $normalisasiIndustriByGrade
     * @return array<int, array{industri_id:int,nilai_preferensi:float|int}>
     */
    private function calculateScoresForSiswa(
        Siswa $siswa,
        Collection $industriList,
        Collection $industriesByGrade,
        Collection $kriteriaEntries,
        array $normalisasiSiswa,
        array $normalisasiIndustriByGrade
    ): array {
        $grade = $this->resolveSiswaGrade((float) $siswa->nilai_akademik);
        $poolIndustri = $industriesByGrade->get($grade, collect());
        if ($poolIndustri->isEmpty()) {
            $poolIndustri = $industriList;
        }

        $normalisasiIndustri = $normalisasiIndustriByGrade[$grade] ?? [];
        $scores = [];
        foreach ($poolIndustri as $industri) {
            $score = 0;
            foreach ($kriteriaEntries as $entry) {
                $value = $entry['source'] === 'siswa'
                    ? (float) ($siswa->{$entry['field']} ?? 0)
                    : (float) ($industri->{$entry['field']} ?? 0);

                if ($entry['source'] === 'siswa') {
                    $max = $normalisasiSiswa[$entry['field']]['max'] ?? 0;
                    $min = $normalisasiSiswa[$entry['field']]['min'] ?? 0;
                } else {
                    $max = $normalisasiIndustri[$entry['field']]['max'] ?? 0;
                    $min = $normalisasiIndustri[$entry['field']]['min'] ?? 0;
                }

                if ($max <= 0) {
                    $normalized = 0;
                } elseif ($entry['tipe'] === 'cost') {
                    $normalized = $value > 0 ? ($min / $value) : 0;
                } else {
                    $normalized = $value / $max;
                }

                $score += $normalized * $entry['bobot'];
            }

            $scores[] = [
                'industri_id' => $industri->id,
                'nilai_preferensi' => $score,
            ];
        }

        usort($scores, function ($a, $b) {
            return $b['nilai_preferensi'] <=> $a['nilai_preferensi'];
        });

        return $scores;
    }

    private function resolveKriteriaMap(string $nama): ?array
    {
        $nama = strtolower($nama);

        if (str_contains($nama, 'nilai') && str_contains($nama, 'akademik')) {
            return ['source' => 'siswa', 'field' => 'nilai_akademik'];
        }

        if (str_contains($nama, 'perangkat')) {
            return ['source' => 'siswa', 'field' => 'perangkat'];
        }

        if (str_contains($nama, 'kapasitas')) {
            return ['source' => 'industri', 'field' => 'kapasitas'];
        }

        return null;
    }

    private function resolveSiswaGrade(float $nilai): string
    {
        if ($nilai >= 90) {
            return 'A';
        }

        if ($nilai >= 80) {
            return 'B';
        }

        return 'C';
    }
}
