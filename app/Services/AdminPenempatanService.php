<?php

namespace App\Services;

use App\Enums\JenisPenempatan;
use App\Enums\PenempatanStatus;
use App\Models\BobotKriteria;
use App\Models\GuruPembimbing;
use App\Models\HasilRekomendasi;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\Kriteria;
use App\Models\PenempatanPKL;
use App\Models\SawRun;
use App\Models\Siswa;
use App\Models\UsulanIndustri;
use Illuminate\Support\Facades\Lang;

class AdminPenempatanService
{
    /**
     * @param array{tab?:string,jurusan_id?:string,tahun_ajaran?:string,status?:string,q?:string} $filters
     * @return array<string,mixed>
     */
    public function getIndexData(array $filters): array
    {
        $tab = $filters['tab'] ?? 'konfigurasi';
        $selectedJurusan = $filters['jurusan_id'] ?? null;
        $selectedTahun = $filters['tahun_ajaran'] ?? null;
        $selectedStatus = $filters['status'] ?? 'all';
        $search = $filters['q'] ?? '';

        $jurusanOptions = Jurusan::orderBy('nama')->get();

        if ($tab === 'hasil' && (!$selectedJurusan || !$selectedTahun)) {
            $latestRunContext = SawRun::query()
                ->latest('run_at')
                ->first();

            if ($latestRunContext) {
                $selectedJurusan = $selectedJurusan ?: $latestRunContext->jurusan_id;
                $selectedTahun = $selectedTahun ?: $latestRunContext->tahun_ajaran;
            }
        }

        if (!$selectedJurusan && $jurusanOptions->isNotEmpty() && $tab !== 'hasil') {
            $selectedJurusan = $jurusanOptions->first()->id;
        }

        $tahunAjaranList = Siswa::query()
            ->when($selectedJurusan, function ($query) use ($selectedJurusan) {
                $query->where('jurusan_id', $selectedJurusan);
            })
            ->select('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');

        if (!$selectedTahun && $tahunAjaranList->isNotEmpty() && $tab !== 'hasil') {
            $selectedTahun = $tahunAjaranList->first();
        }

        $statusList = Lang::get('penempatan.list');
        $statusLabels = Lang::get('penempatan.label');
        $pilihanLabels = Lang::get('penempatan.pilih');

        $kriteriaList = Kriteria::orderBy('nama_kriteria')->get();
        $bobotByKriteria = BobotKriteria::query()
            ->when($selectedJurusan, function ($query) use ($selectedJurusan) {
                $query->where('jurusan_id', $selectedJurusan);
            })
            ->get()
            ->keyBy('kriteria_id');

        $bobotKriteria = $kriteriaList->map(function ($kriteria) use ($bobotByKriteria) {
            $bobot = $bobotByKriteria->get($kriteria->id)?->bobot ?? 0;

            return [
                'id' => $kriteria->id,
                'kriteria' => $kriteria->nama_kriteria,
                'tipe' => $kriteria->tipe,
                'bobot' => $bobot,
            ];
        });

        $totalBobot = $bobotKriteria->sum('bobot');
        $isBobotValid = abs($totalBobot - 1) <= 0.02;

        $queryParams = [
            'tab' => $tab,
            'jurusan_id' => $selectedJurusan,
            'tahun_ajaran' => $selectedTahun,
            'status' => $selectedStatus,
            'q' => $search,
        ];

        $penempatanBaseQuery = PenempatanPKL::with([
            'siswa.user',
            'siswa.jurusan',
            'industri',
            'usulanIndustri',
            'guruPembimbing.user',
        ]);

        if ($selectedJurusan) {
            $penempatanBaseQuery->whereHas('siswa', function ($query) use ($selectedJurusan) {
                $query->where('jurusan_id', $selectedJurusan);
            });
        }

        if ($selectedTahun) {
            $penempatanBaseQuery->whereHas('siswa', function ($query) use ($selectedTahun) {
                $query->where('tahun_ajaran', $selectedTahun);
            });
        }

        if ($selectedStatus && $selectedStatus !== 'all') {
            $penempatanBaseQuery->where('status', $selectedStatus);
        }

        if ($search) {
            $penempatanBaseQuery->whereHas('siswa.user', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        $penempatanData = (clone $penempatanBaseQuery)
            ->orderByDesc('id')
            ->paginate(10)
            ->appends($queryParams);

        $penempatanLangsung = (clone $penempatanBaseQuery)
            ->where('jenis_penempatan', JenisPenempatan::LANGSUNG->value)
            ->orderByDesc('id')
            ->get();

        $siswaOptions = Siswa::with('user', 'jurusan')
            ->orderBy('id')
            ->get();

        $industriOptions = Industri::orderBy('nama_industri')->get();

        $usulanList = UsulanIndustri::with(['siswa.user', 'jurusan'])
            ->orderByDesc('id')
            ->get();

        $guruOptions = GuruPembimbing::with('user', 'jurusan')
            ->orderBy('id')
            ->get()
            ->groupBy('jurusan_id');

        $statusCounts = [
            PenempatanStatus::DITERIMA_INDUSTRI->value => (clone $penempatanBaseQuery)
                ->where('status', PenempatanStatus::DITERIMA_INDUSTRI->value)
                ->count(),
            PenempatanStatus::PROSES_PENGAJUAN->value => (clone $penempatanBaseQuery)
                ->where('status', PenempatanStatus::PROSES_PENGAJUAN->value)
                ->count(),
            PenempatanStatus::MENUNGGU_KONFIRMASI->value => (clone $penempatanBaseQuery)
                ->where('status', PenempatanStatus::MENUNGGU_KONFIRMASI->value)
                ->count(),
        ];

        $latestSawRun = null;
        $rekomendasiBySiswa = collect();
        $penempatanBySiswa = collect();

        if ($tab === 'hasil') {
            $latestRunIds = SawRun::query()
                ->orderByDesc('run_at')
                ->orderByDesc('id')
                ->get()
                ->unique('jurusan_id')
                ->pluck('id')
                ->values();

            if ($latestRunIds->isNotEmpty()) {
                $allRekomendasi = HasilRekomendasi::with('industri')
                    ->whereIn('saw_run_id', $latestRunIds)
                    ->orderBy('peringkat')
                    ->get();

                $rekomendasiBySiswa = $allRekomendasi->groupBy(function ($item) {
                    return $item->saw_run_id . '-' . $item->siswa_id;
                });

                $hasilBaseQuery = HasilRekomendasi::with([
                    'siswa.user',
                    'siswa.jurusan',
                    'industri',
                ])
                    ->whereIn('saw_run_id', $latestRunIds)
                    ->where('peringkat', 1);

                if ($selectedStatus && $selectedStatus !== 'all') {
                    $siswaIdsByStatus = PenempatanPKL::query()
                        ->where('status', $selectedStatus)
                        ->pluck('siswa_id');

                    $hasilBaseQuery->whereIn('siswa_id', $siswaIdsByStatus);
                }

                if ($search) {
                    $hasilBaseQuery->whereHas('siswa.user', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
                }

                $penempatanData = $hasilBaseQuery
                    ->orderByDesc('saw_run_id')
                    ->orderBy('siswa_id')
                    ->paginate(10)
                    ->appends($queryParams);

                $latestRunSiswaIds = HasilRekomendasi::query()
                    ->whereIn('saw_run_id', $latestRunIds)
                    ->where('peringkat', 1)
                    ->pluck('siswa_id')
                    ->unique()
                    ->values();

                $statusCounts = [
                    PenempatanStatus::DITERIMA_INDUSTRI->value => PenempatanPKL::query()
                        ->whereIn('siswa_id', $latestRunSiswaIds)
                        ->where('status', PenempatanStatus::DITERIMA_INDUSTRI->value)
                        ->count(),
                    PenempatanStatus::PROSES_PENGAJUAN->value => PenempatanPKL::query()
                        ->whereIn('siswa_id', $latestRunSiswaIds)
                        ->where('status', PenempatanStatus::PROSES_PENGAJUAN->value)
                        ->count(),
                    PenempatanStatus::MENUNGGU_KONFIRMASI->value => PenempatanPKL::query()
                        ->whereIn('siswa_id', $latestRunSiswaIds)
                        ->where('status', PenempatanStatus::MENUNGGU_KONFIRMASI->value)
                        ->count(),
                ];

                $currentSiswaIds = $penempatanData->getCollection()
                    ->pluck('siswa_id')
                    ->filter()
                    ->values();

                if ($currentSiswaIds->isNotEmpty()) {
                    $penempatanBySiswa = PenempatanPKL::with([
                        'siswa.user',
                        'siswa.jurusan',
                        'industri',
                        'usulanIndustri',
                        'guruPembimbing.user',
                    ])
                        ->whereIn('siswa_id', $currentSiswaIds)
                        ->orderByDesc('id')
                        ->get()
                        ->unique('siswa_id')
                        ->keyBy('siswa_id');
                }
            } else {
                $penempatanData = HasilRekomendasi::with([
                    'siswa.user',
                    'siswa.jurusan',
                    'industri',
                ])
                    ->whereRaw('1 = 0')
                    ->paginate(10)
                    ->appends($queryParams);
            }
        } elseif ($selectedJurusan && $selectedTahun) {
            $latestSawRun = SawRun::query()
                ->where('jurusan_id', $selectedJurusan)
                ->where('tahun_ajaran', $selectedTahun)
                ->latest('run_at')
                ->first();

            if ($latestSawRun) {
                $rekomendasiBySiswa = HasilRekomendasi::with('industri')
                    ->where('saw_run_id', $latestSawRun->id)
                    ->orderBy('peringkat')
                    ->get()
                    ->groupBy(function ($item) {
                        return $item->saw_run_id . '-' . $item->siswa_id;
                    });

                $hasilBaseQuery = HasilRekomendasi::with([
                    'siswa.user',
                    'siswa.jurusan',
                    'industri',
                ])
                    ->where('saw_run_id', $latestSawRun->id)
                    ->where('peringkat', 1);

                if ($selectedStatus && $selectedStatus !== 'all') {
                    $siswaIdsByStatus = PenempatanPKL::query()
                        ->where('status', $selectedStatus)
                        ->pluck('siswa_id');

                    $hasilBaseQuery->whereIn('siswa_id', $siswaIdsByStatus);
                }

                if ($search) {
                    $hasilBaseQuery->whereHas('siswa.user', function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    });
                }

                $penempatanData = $hasilBaseQuery
                    ->orderBy('siswa_id')
                    ->paginate(10)
                    ->appends($queryParams);

                $currentSiswaIds = $penempatanData->getCollection()
                    ->pluck('siswa_id')
                    ->filter()
                    ->values();

                if ($currentSiswaIds->isNotEmpty()) {
                    $penempatanBySiswa = PenempatanPKL::with([
                        'siswa.user',
                        'siswa.jurusan',
                        'industri',
                        'usulanIndustri',
                        'guruPembimbing.user',
                    ])
                        ->whereIn('siswa_id', $currentSiswaIds)
                        ->orderByDesc('id')
                        ->get()
                        ->unique('siswa_id')
                        ->keyBy('siswa_id');
                }
            }
        }

        return [
            'tahunAjaranList' => $tahunAjaranList,
            'statusList' => $statusList,
            'statusLabels' => $statusLabels,
            'pilihanLabels' => $pilihanLabels,
            'jurusanOptions' => $jurusanOptions,
            'bobotKriteria' => $bobotKriteria,
            'selectedJurusan' => $selectedJurusan,
            'selectedTahun' => $selectedTahun,
            'selectedStatus' => $selectedStatus,
            'search' => $search,
            'penempatanData' => $penempatanData,
            'statusCounts' => $statusCounts,
            'latestSawRun' => $latestSawRun,
            'rekomendasiBySiswa' => $rekomendasiBySiswa,
            'penempatanBySiswa' => $penempatanBySiswa,
            'usulanList' => $usulanList,
            'totalBobot' => $totalBobot,
            'isBobotValid' => $isBobotValid,
            'guruOptions' => $guruOptions,
            'siswaOptions' => $siswaOptions,
            'industriOptions' => $industriOptions,
            'penempatanLangsung' => $penempatanLangsung,
        ];
    }
}
