<?php

namespace App\Services;

use App\Enums\AbsensiStatus;
use App\Enums\PenempatanStatus;
use App\Models\AbsensiPkl;
use App\Models\Jurusan;
use App\Models\Logbook;
use App\Models\PenempatanPKL;
use App\Models\RiskScore;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PeringatanDiniReadService
{
    /**
     * @param array<int>|null $allowedSiswaIds
     */
    public function getJurusanOptions(?array $allowedSiswaIds = null): Collection
    {
        if ($allowedSiswaIds !== null) {
            if ($allowedSiswaIds === []) {
                return collect();
            }

            return Jurusan::whereIn(
                'id',
                Siswa::whereIn('id', $allowedSiswaIds)->pluck('jurusan_id')->unique()
            )->orderBy('nama')->get();
        }

        return Jurusan::orderBy('nama')->get();
    }

    /**
     * @param array<int>|null $allowedSiswaIds
     */
    public function getTahunAjaranOptions(?string $jurusanId = null, ?array $allowedSiswaIds = null): Collection
    {
        if ($allowedSiswaIds !== null && $allowedSiswaIds === []) {
            return collect();
        }

        return Siswa::query()
            ->when($allowedSiswaIds !== null, function ($query) use ($allowedSiswaIds) {
                $query->whereIn('id', $allowedSiswaIds);
            }, function ($query) {
                $query->whereHas('penempatanPkl', function ($penempatanQuery) {
                    $penempatanQuery->where('status', PenempatanStatus::DITERIMA_INDUSTRI->value);
                });
            })
            ->when($jurusanId, function ($query, $selectedJurusanId) {
                $query->where('jurusan_id', $selectedJurusanId);
            })
            ->select('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');
    }

    /**
     * @param array{q?:string,category?:string,jurusan_id?:string,tahun_ajaran?:string} $filters
     * @param array<int>|null $allowedSiswaIds
     * @return array{riskScores:LengthAwarePaginator|Collection,weekStart:?Carbon,weekEnd:?Carbon,detailByRiskId:array}
     */
    public function getLatestRiskData(array $filters, ?array $allowedSiswaIds = null): array
    {
        if ($allowedSiswaIds !== null && $allowedSiswaIds === []) {
            return [
                'riskScores' => collect(),
                'weekStart' => null,
                'weekEnd' => null,
                'detailByRiskId' => [],
            ];
        }

        $latestWeekEnd = $this->buildRiskBaseQuery($filters, $allowedSiswaIds)->max('week_end');
        $riskScores = collect();
        $detailByRiskId = [];
        $weekStart = null;
        $weekEnd = null;

        if ($latestWeekEnd) {
            $weekEnd = Carbon::parse($latestWeekEnd);
            $latestWeekStart = $this->buildRiskBaseQuery($filters, $allowedSiswaIds)
                ->where('week_end', $latestWeekEnd)
                ->max('week_start');
            $weekStart = $latestWeekStart ? Carbon::parse($latestWeekStart) : null;

            if (!$weekStart) {
                return [
                    'riskScores' => $riskScores,
                    'weekStart' => $weekStart,
                    'weekEnd' => $weekEnd,
                    'detailByRiskId' => $detailByRiskId,
                ];
            }

            $riskQuery = $this->buildRiskBaseQuery($filters, $allowedSiswaIds)
                ->where('week_start', $latestWeekStart)
                ->where('week_end', $latestWeekEnd);

            if (!empty($filters['q'])) {
                $riskQuery->whereHas('siswa.user', function ($query) use ($filters) {
                    $query->where('name', 'like', '%' . $filters['q'] . '%');
                });
            }

            if (!empty($filters['category']) && $filters['category'] !== 'all') {
                $riskQuery->where('category', $filters['category']);
            }

            $riskScores = $riskQuery
                ->orderBy('score')
                ->paginate(15)
                ->withQueryString();

            // Detail modal dibangun terpisah supaya query list tetap fokus ke pagination.
            $detailByRiskId = $this->buildRiskDetails(
                $riskScores->getCollection(),
                $weekStart,
                $weekEnd
            );
        }

        return [
            'riskScores' => $riskScores,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'detailByRiskId' => $detailByRiskId,
        ];
    }

    /**
     * @param array{q?:string,category?:string,jurusan_id?:string,tahun_ajaran?:string} $filters
     * @param array<int>|null $allowedSiswaIds
     */
    private function buildRiskBaseQuery(array $filters, ?array $allowedSiswaIds = null)
    {
        return RiskScore::with(['siswa.user', 'siswa.jurusan'])
            ->when($allowedSiswaIds !== null, function ($query) use ($allowedSiswaIds) {
                $query->whereIn('siswa_id', $allowedSiswaIds);
            })
            ->when(!empty($filters['jurusan_id']) || !empty($filters['tahun_ajaran']), function ($query) use ($filters) {
                $query->whereHas('siswa', function ($siswaQuery) use ($filters) {
                    if (!empty($filters['jurusan_id'])) {
                        $siswaQuery->where('jurusan_id', $filters['jurusan_id']);
                    }

                    if (!empty($filters['tahun_ajaran'])) {
                        $siswaQuery->where('tahun_ajaran', $filters['tahun_ajaran']);
                    }
                });
            });
    }

    private function countWeekdays(Carbon $start, Carbon $end): int
    {
        $count = 0;
        $cursor = $start->copy()->startOfDay();
        $endCursor = $end->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($endCursor)) {
            if (!$cursor->isWeekend()) {
                $count++;
            }

            $cursor->addDay();
        }

        return $count;
    }

    /**
     * @return array<int, array{total_logs:int,late_logs:int,target_logs:int,freq_score:float,late_score:float,total_absensi:int,valid_absensi:int,target_absensi:int,absensi_score:float,laporan_status:string,laporan_score:float}>
     */
    private function buildRiskDetails(Collection $riskItems, Carbon $weekStart, Carbon $weekEnd): array
    {
        $detailByRiskId = [];
        $siswaIds = $riskItems->pluck('siswa_id')->all();

        $penempatanBySiswa = PenempatanPKL::whereIn('siswa_id', $siswaIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('siswa_id')
            ->map(fn ($rows) => $rows->first());
        $logbooks = Logbook::whereIn('siswa_id', $siswaIds)
            ->whereBetween('tanggal', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get()
            ->groupBy('siswa_id');
        $absensiList = AbsensiPkl::whereIn('siswa_id', $siswaIds)
            ->whereBetween('tanggal', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get()
            ->groupBy('siswa_id');

        $targetLogbookPerWeek = $this->countWeekdays($weekStart, $weekEnd);
        $laporanScores = [
            'selesai' => 1.0,
            'ditindak' => 0.5,
            'menunggu' => 0.1,
        ];

        foreach ($riskItems as $row) {
            $logs = $logbooks->get($row->siswa_id, collect());
            $totalLogs = $logs->count();
            $lateLogs = $logs->filter(function ($log) {
                if (!$log->tanggal || !$log->created_at) {
                    return false;
                }

                return $log->created_at->toDateString() > $log->tanggal->toDateString();
            })->count();

            $absensiLogs = $absensiList->get($row->siswa_id, collect());
            $validAbsensi = $absensiLogs
                ->where('status', AbsensiStatus::HADIR_VALID->value)
                ->count();
            $totalAbsensi = $absensiLogs->count();

            $freqScore = ($totalLogs > 0 && $targetLogbookPerWeek > 0)
                ? min($totalLogs / $targetLogbookPerWeek, 1)
                : 0;
            $lateScore = $totalLogs > 0
                ? 1 - min($lateLogs / $totalLogs, 1)
                : 0;
            $absensiScore = $targetLogbookPerWeek > 0
                ? min($validAbsensi / $targetLogbookPerWeek, 1)
                : 0;
            $laporanStatus = $penempatanBySiswa->get($row->siswa_id)?->laporan_status ?? null;
            $laporanScore = $laporanScores[$laporanStatus] ?? 1;

            $detailByRiskId[$row->id] = [
                'total_logs' => $totalLogs,
                'late_logs' => $lateLogs,
                'target_logs' => $targetLogbookPerWeek,
                'freq_score' => $freqScore,
                'late_score' => $lateScore,
                'total_absensi' => $totalAbsensi,
                'valid_absensi' => $validAbsensi,
                'target_absensi' => $targetLogbookPerWeek,
                'absensi_score' => $absensiScore,
                'laporan_status' => $laporanStatus ?? 'belum ada',
                'laporan_score' => $laporanScore,
            ];
        }

        return $detailByRiskId;
    }
}
