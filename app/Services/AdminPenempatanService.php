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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;

class AdminPenempatanService
{
    /**
     * @param array{tab?:string,jurusan_id?:string,tahun_ajaran?:string,status?:string,q?:string,has_jurusan_filter?:bool,has_tahun_ajaran_filter?:bool} $filters
     * @return array<string,mixed>
     */
    public function getIndexData(array $filters): array
    {
        $state = $this->normalizeFilters($filters);
        $jurusanOptions = $this->getJurusanOptions();
        [$state['selectedJurusan'], $state['selectedTahun']] = $this->resolveSelectedJurusanAndTahun(
            $state['tab'],
            $state['selectedJurusan'],
            $state['selectedTahun'],
            $jurusanOptions,
            $state['hasJurusanFilter'],
            $state['hasTahunFilter']
        );

        $tahunAjaranList = $this->getTahunAjaranList($state['selectedJurusan']);
        if ($tahunAjaranList->isNotEmpty() && (
            !$state['selectedTahun']
            || !$tahunAjaranList->contains((string) $state['selectedTahun'])
        )) {
            $state['selectedTahun'] = $tahunAjaranList->first();
        }

        $labels = $this->getLanguageLabels();
        $bobotSummary = $this->getBobotSummary($state['selectedJurusan']);
        $queryParams = $this->buildQueryParams($state);

        $penempatanBaseQuery = $this->buildPenempatanBaseQuery(
            $state['selectedJurusan'],
            $state['selectedTahun'],
            $state['selectedStatus'],
            $state['search']
        );

        $penempatanData = (clone $penempatanBaseQuery)
            ->orderByDesc('id')
            ->paginate(10)
            ->appends($queryParams);
        $penempatanLangsung = (clone $penempatanBaseQuery)
            ->where('jenis_penempatan', JenisPenempatan::LANGSUNG->value)
            ->orderByDesc('id')
            ->get();
        $statusCounts = $this->buildStatusCountsFromPenempatanQuery($penempatanBaseQuery);

        $latestSawRun = null;
        $rekomendasiBySiswa = collect();
        $penempatanBySiswa = collect();

        if ($state['tab'] === 'hasil') {
            [
                'penempatanData' => $penempatanData,
                'rekomendasiBySiswa' => $rekomendasiBySiswa,
                'penempatanBySiswa' => $penempatanBySiswa,
                'statusCounts' => $statusCounts,
            ] = $this->buildHasilTabData($state, $queryParams, $statusCounts);
        } elseif ($state['selectedJurusan'] && $state['selectedTahun']) {
            [
                'latestSawRun' => $latestSawRun,
                'penempatanData' => $penempatanData,
                'rekomendasiBySiswa' => $rekomendasiBySiswa,
                'penempatanBySiswa' => $penempatanBySiswa,
            ] = $this->buildSingleRunData($state, $queryParams, $penempatanData);
        }

        $options = $this->getMasterOptions();

        return [
            'tahunAjaranList' => $tahunAjaranList,
            'statusList' => $labels['statusList'],
            'statusLabels' => $labels['statusLabels'],
            'pilihanLabels' => $labels['pilihanLabels'],
            'jurusanOptions' => $jurusanOptions,
            'bobotKriteria' => $bobotSummary['bobotKriteria'],
            'selectedJurusan' => $state['selectedJurusan'],
            'selectedTahun' => $state['selectedTahun'],
            'selectedStatus' => $state['selectedStatus'],
            'search' => $state['search'],
            'penempatanData' => $penempatanData,
            'statusCounts' => $statusCounts,
            'latestSawRun' => $latestSawRun,
            'rekomendasiBySiswa' => $rekomendasiBySiswa,
            'penempatanBySiswa' => $penempatanBySiswa,
            'usulanList' => $options['usulanList'],
            'totalBobot' => $bobotSummary['totalBobot'],
            'isBobotValid' => $bobotSummary['isBobotValid'],
            'guruOptions' => $options['guruOptions'],
            'siswaOptions' => $options['siswaOptions'],
            'industriOptions' => $options['industriOptions'],
            'penempatanLangsung' => $penempatanLangsung,
        ];
    }

    /**
     * @param array{tab?:string,jurusan_id?:string,tahun_ajaran?:string,status?:string,q?:string,has_jurusan_filter?:bool,has_tahun_ajaran_filter?:bool} $filters
     * @return array{tab:string,selectedJurusan:mixed,selectedTahun:mixed,selectedStatus:string,search:string,hasJurusanFilter:bool,hasTahunFilter:bool}
     */
    private function normalizeFilters(array $filters): array
    {
        return [
            'tab' => $filters['tab'] ?? 'konfigurasi',
            'selectedJurusan' => $filters['jurusan_id'] ?? null,
            'selectedTahun' => $filters['tahun_ajaran'] ?? null,
            'selectedStatus' => $filters['status'] ?? 'all',
            'search' => $filters['q'] ?? '',
            'hasJurusanFilter' => (bool) ($filters['has_jurusan_filter'] ?? false),
            'hasTahunFilter' => (bool) ($filters['has_tahun_ajaran_filter'] ?? false),
        ];
    }

    private function getJurusanOptions(): Collection
    {
        return Jurusan::orderBy('nama')->get();
    }

    /**
     * @param mixed $selectedJurusan
     * @param mixed $selectedTahun
     * @return array{0:mixed,1:mixed}
     */
    private function resolveSelectedJurusanAndTahun(
        string $tab,
        $selectedJurusan,
        $selectedTahun,
        Collection $jurusanOptions,
        bool $hasJurusanFilter = false,
        bool $hasTahunFilter = false
    ): array {
        if ($tab === 'hasil' && !$hasJurusanFilter && !$hasTahunFilter) {
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

        return [$selectedJurusan, $selectedTahun];
    }

    private function getTahunAjaranList($selectedJurusan): Collection
    {
        return Siswa::query()
            ->when($selectedJurusan, function ($query) use ($selectedJurusan) {
                $query->where('jurusan_id', $selectedJurusan);
            })
            ->select('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');
    }

    /**
     * @return array{statusList:array,statusLabels:array,pilihanLabels:array}
     */
    private function getLanguageLabels(): array
    {
        return [
            'statusList' => Lang::get('penempatan.list'),
            'statusLabels' => Lang::get('penempatan.label'),
            'pilihanLabels' => Lang::get('penempatan.pilih'),
        ];
    }

    private function getBobotSummary($selectedJurusan): array
    {
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

        return [
            'bobotKriteria' => $bobotKriteria,
            'totalBobot' => $totalBobot,
            'isBobotValid' => abs($totalBobot - 1) <= 0.02,
        ];
    }

    /**
     * @param array{tab:string,selectedJurusan:mixed,selectedTahun:mixed,selectedStatus:string,search:string} $state
     * @return array{tab:string,jurusan_id:mixed,tahun_ajaran:mixed,status:string,q:string}
     */
    private function buildQueryParams(array $state): array
    {
        return [
            'tab' => $state['tab'],
            'jurusan_id' => $state['selectedJurusan'],
            'tahun_ajaran' => $state['selectedTahun'],
            'status' => $state['selectedStatus'],
            'q' => $state['search'],
        ];
    }

    private function buildPenempatanBaseQuery(
        $selectedJurusan,
        $selectedTahun,
        string $selectedStatus,
        string $search
    ): Builder {
        $query = PenempatanPKL::with([
            'siswa.user',
            'siswa.jurusan',
            'industri',
            'usulanIndustri',
            'guruPembimbing.user',
        ]);

        if ($selectedJurusan) {
            $query->whereHas('siswa', function ($q) use ($selectedJurusan) {
                $q->where('jurusan_id', $selectedJurusan);
            });
        }

        if ($selectedTahun) {
            $query->whereHas('siswa', function ($q) use ($selectedTahun) {
                $q->where('tahun_ajaran', $selectedTahun);
            });
        }

        if ($selectedStatus !== 'all') {
            $query->where('status', $selectedStatus);
        }

        if ($search !== '') {
            $query->whereHas('siswa.user', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    private function buildStatusCountsFromPenempatanQuery(Builder $penempatanBaseQuery): array
    {
        return [
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
    }

    private function getMasterOptions(): array
    {
        return [
            'siswaOptions' => Siswa::with('user', 'jurusan')->orderBy('id')->get(),
            'industriOptions' => Industri::orderBy('nama_industri')->get(),
            'usulanList' => UsulanIndustri::with(['siswa.user', 'jurusan'])->orderByDesc('id')->get(),
            'guruOptions' => GuruPembimbing::with('user', 'jurusan')
                ->orderBy('id')
                ->get()
                ->groupBy('jurusan_id'),
        ];
    }

    /**
     * @param array{tab:string,selectedJurusan:mixed,selectedTahun:mixed,selectedStatus:string,search:string} $state
     * @param array{tab:string,jurusan_id:mixed,tahun_ajaran:mixed,status:string,q:string} $queryParams
     */
    private function buildHasilTabData(array $state, array $queryParams, array $defaultStatusCounts): array
    {
        $latestRunIds = $this->resolveHasilRunIds($state['selectedJurusan'], $state['selectedTahun']);
        if ($latestRunIds->isEmpty()) {
            return [
                'penempatanData' => $this->buildEmptyHasilPaginator($queryParams),
                'rekomendasiBySiswa' => collect(),
                'penempatanBySiswa' => collect(),
                'statusCounts' => $defaultStatusCounts,
            ];
        }

        $rekomendasiBySiswa = HasilRekomendasi::with('industri')
            ->whereIn('saw_run_id', $latestRunIds)
            ->orderBy('peringkat')
            ->get()
            ->groupBy(function ($item) {
                return $item->saw_run_id . '-' . $item->siswa_id;
            });

        $hasilBaseQuery = $this->buildHasilTopRankedQueryByRunIds($latestRunIds);
        $this->applyHasilFilters($hasilBaseQuery, $state['selectedStatus'], $state['search']);

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

        $statusCounts = $this->buildStatusCountsFromSiswaIds($latestRunSiswaIds);
        $penempatanBySiswa = $this->getPenempatanBySiswaFromPaginator($penempatanData);

        return [
            'penempatanData' => $penempatanData,
            'rekomendasiBySiswa' => $rekomendasiBySiswa,
            'penempatanBySiswa' => $penempatanBySiswa,
            'statusCounts' => $statusCounts,
        ];
    }

    private function resolveHasilRunIds($selectedJurusan, $selectedTahun): Collection
    {
        if ($selectedJurusan && $selectedTahun) {
            $latestRun = SawRun::query()
                ->where('jurusan_id', $selectedJurusan)
                ->where('tahun_ajaran', $selectedTahun)
                ->latest('run_at')
                ->latest('id')
                ->first();

            return $latestRun ? collect([$latestRun->id]) : collect();
        }

        return $this->getLatestRunIdsPerJurusan($selectedTahun);
    }

    /**
     * @param array{tab:string,selectedJurusan:mixed,selectedTahun:mixed,selectedStatus:string,search:string} $state
     * @param array{tab:string,jurusan_id:mixed,tahun_ajaran:mixed,status:string,q:string} $queryParams
     */
    private function buildSingleRunData(array $state, array $queryParams, $fallbackPenempatanData): array
    {
        $latestSawRun = SawRun::query()
            ->where('jurusan_id', $state['selectedJurusan'])
            ->where('tahun_ajaran', $state['selectedTahun'])
            ->latest('run_at')
            ->first();

        if (!$latestSawRun) {
            return [
                'latestSawRun' => null,
                'penempatanData' => $fallbackPenempatanData,
                'rekomendasiBySiswa' => collect(),
                'penempatanBySiswa' => collect(),
            ];
        }

        $rekomendasiBySiswa = HasilRekomendasi::with('industri')
            ->where('saw_run_id', $latestSawRun->id)
            ->orderBy('peringkat')
            ->get()
            ->groupBy(function ($item) {
                return $item->saw_run_id . '-' . $item->siswa_id;
            });

        $hasilBaseQuery = $this->buildHasilTopRankedQueryByRunIds(collect([$latestSawRun->id]));
        $this->applyHasilFilters($hasilBaseQuery, $state['selectedStatus'], $state['search']);

        $penempatanData = $hasilBaseQuery
            ->orderBy('siswa_id')
            ->paginate(10)
            ->appends($queryParams);

        return [
            'latestSawRun' => $latestSawRun,
            'penempatanData' => $penempatanData,
            'rekomendasiBySiswa' => $rekomendasiBySiswa,
            'penempatanBySiswa' => $this->getPenempatanBySiswaFromPaginator($penempatanData),
        ];
    }

    private function getLatestRunIdsPerJurusan($selectedTahun = null): Collection
    {
        return SawRun::query()
            ->when($selectedTahun, function ($query) use ($selectedTahun) {
                $query->where('tahun_ajaran', $selectedTahun);
            })
            ->orderByDesc('run_at')
            ->orderByDesc('id')
            ->get()
            ->unique('jurusan_id')
            ->pluck('id')
            ->values();
    }

    private function buildHasilTopRankedQueryByRunIds(Collection $runIds): Builder
    {
        return HasilRekomendasi::with([
            'siswa.user',
            'siswa.jurusan',
            'industri',
        ])
            ->whereIn('saw_run_id', $runIds)
            ->where('peringkat', 1);
    }

    private function applyHasilFilters(Builder $hasilBaseQuery, string $selectedStatus, string $search): void
    {
        if ($selectedStatus !== 'all') {
            $siswaIdsByStatus = PenempatanPKL::query()
                ->where('status', $selectedStatus)
                ->pluck('siswa_id');
            $hasilBaseQuery->whereIn('siswa_id', $siswaIdsByStatus);
        }

        if ($search !== '') {
            $hasilBaseQuery->whereHas('siswa.user', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }
    }

    private function buildStatusCountsFromSiswaIds(Collection $siswaIds): array
    {
        return [
            PenempatanStatus::DITERIMA_INDUSTRI->value => PenempatanPKL::query()
                ->whereIn('siswa_id', $siswaIds)
                ->where('status', PenempatanStatus::DITERIMA_INDUSTRI->value)
                ->count(),
            PenempatanStatus::PROSES_PENGAJUAN->value => PenempatanPKL::query()
                ->whereIn('siswa_id', $siswaIds)
                ->where('status', PenempatanStatus::PROSES_PENGAJUAN->value)
                ->count(),
            PenempatanStatus::MENUNGGU_KONFIRMASI->value => PenempatanPKL::query()
                ->whereIn('siswa_id', $siswaIds)
                ->where('status', PenempatanStatus::MENUNGGU_KONFIRMASI->value)
                ->count(),
        ];
    }

    private function getPenempatanBySiswaFromPaginator($penempatanData): Collection
    {
        $currentSiswaIds = $penempatanData->getCollection()
            ->pluck('siswa_id')
            ->filter()
            ->values();

        if ($currentSiswaIds->isEmpty()) {
            return collect();
        }

        return PenempatanPKL::with([
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

    private function buildEmptyHasilPaginator(array $queryParams)
    {
        return HasilRekomendasi::with([
            'siswa.user',
            'siswa.jurusan',
            'industri',
        ])
            ->whereRaw('1 = 0')
            ->paginate(10)
            ->appends($queryParams);
    }
}
