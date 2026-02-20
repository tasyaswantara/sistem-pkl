<?php

namespace App\Services;

use App\Enums\PenempatanStatus;
use App\Models\BobotKriteria;
use App\Models\HasilRekomendasi;
use App\Models\Industri;
use App\Models\PenempatanPKL;
use App\Models\SawRun;
use App\Models\Siswa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SawRunService
{
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

        $totalBobot = $bobotKriteria->sum('bobot');
        if (abs($totalBobot - 1) > 0.02) {
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

        $kriteriaEntries = $bobotKriteria->map(function ($item) {
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

        if ($kriteriaEntries->isEmpty()) {
            return [
                'ok' => false,
                'error_field' => 'saw',
                'error_key' => 'penempatan.errors.kriteria_link',
            ];
        }

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

        $industriesByGrade = $industriList->groupBy('grade');
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

        $now = now();
        $rowsCount = 0;

        DB::transaction(function () use (
            $jurusanId,
            $tahunAjaran,
            $kriteriaEntries,
            $normalisasiSiswa,
            $normalisasiIndustriByGrade,
            $industriesByGrade,
            $siswaList,
            $industriList,
            $now,
            $actorId,
            &$rowsCount
        ) {
            $run = SawRun::create([
                'jurusan_id' => $jurusanId,
                'tahun_ajaran' => $tahunAjaran,
                'run_at' => $now,
                'created_by' => $actorId,
            ]);

            $rows = [];
            foreach ($siswaList as $siswa) {
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

                $topChoice = $scores[0] ?? null;
                if ($topChoice) {
                    $existingPenempatan = PenempatanPKL::where('siswa_id', $siswa->id)->first();
                    $shouldUpdate = !$existingPenempatan || in_array(
                        $existingPenempatan->status,
                        [
                            PenempatanStatus::BELUM_MEMILIH->value,
                            PenempatanStatus::DITOLAK_SEKOLAH->value,
                            PenempatanStatus::PENGAJUAN_DITOLAK_INDUSTRI->value,
                            PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value,
                        ],
                        true
                    );

                    if ($shouldUpdate) {
                        $oldStatus = $existingPenempatan?->status;

                        $penempatan = PenempatanPKL::updateOrCreate(
                            ['siswa_id' => $siswa->id],
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
                }

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
                $rowsCount = count($rows);
            }
        });

        Log::info('SAW run completed', [
            'jurusan_id' => $jurusanId,
            'tahun_ajaran' => $tahunAjaran,
            'siswa' => $siswaList->count(),
            'industri' => $industriList->count(),
            'rows_inserted' => $rowsCount,
        ]);

        return ['ok' => true, 'rows_count' => $rowsCount];
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
