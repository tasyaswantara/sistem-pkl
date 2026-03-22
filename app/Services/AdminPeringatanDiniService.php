<?php

namespace App\Services;

use App\Enums\AbsensiStatus;
use App\Enums\PenempatanStatus;
use App\Models\AbsensiPkl;
use App\Models\Logbook;
use App\Models\PenempatanPKL;
use App\Models\RiskScore;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdminPeringatanDiniService
{
    public function __construct(private AppNotificationService $notificationService)
    {
    }

    public function runRisk(Carbon $weekStart, Carbon $weekEnd, ?string $tahunAjaran = null): int
    {
        $siswaList = Siswa::whereHas('penempatanPkl', function ($query) {
                $query->where('status', PenempatanStatus::DITERIMA_INDUSTRI->value);
            })
            ->when($tahunAjaran, function ($query, $selectedTahunAjaran) {
                $query->where('tahun_ajaran', $selectedTahunAjaran);
            })
            ->select('id')
            ->get();

        $rows = $this->calculateRiskScores($siswaList, $weekStart, $weekEnd);

        return $this->storeRiskScores($rows, $weekStart, $weekEnd);
    }

    /**
     * @return array<int, array{siswa_id:int,score:float,category:string}>
     */
    private function calculateRiskScores(Collection $siswaList, Carbon $weekStart, Carbon $weekEnd): array
    {
        $targetLogbookPerWeek = $this->countWeekdays($weekStart, $weekEnd);
        $weights = [
            'logbook' => 0.35,
            'late' => 0.25,
            'laporan' => 0.2,
            'absensi' => 0.2,
        ];
        $laporanScores = [
            'selesai' => 1.0,
            'ditindak' => 0.5,
            'menunggu' => 0.1,
        ];

        $siswaIds = $siswaList->pluck('id')->all();
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

        $rows = [];
        foreach ($siswaList as $siswa) {
            $logs = $logbooks->get($siswa->id, collect());
            $totalLogs = $logs->count();
            $lateLogs = $logs->filter(function ($log) {
                if (!$log->tanggal || !$log->created_at) {
                    return false;
                }

                return $log->created_at->toDateString() > $log->tanggal->toDateString();
            })->count();

            $absensiLogs = $absensiList->get($siswa->id, collect());
            $validAbsensi = $absensiLogs
                ->where('status', AbsensiStatus::HADIR_VALID->value)
                ->count();

            $freqScore = ($totalLogs > 0 && $targetLogbookPerWeek > 0)
                ? min($totalLogs / $targetLogbookPerWeek, 1)
                : 0;
            $lateScore = $totalLogs > 0
                ? 1 - min($lateLogs / $totalLogs, 1)
                : 0;
            $absensiScore = $targetLogbookPerWeek > 0
                ? min($validAbsensi / $targetLogbookPerWeek, 1)
                : 0;
            $laporanStatus = $penempatanBySiswa->get($siswa->id)?->laporan_status ?? null;
            $laporanScore = $laporanScores[$laporanStatus] ?? 1;

            $score = ($weights['logbook'] * $freqScore)
                + ($weights['late'] * $lateScore)
                + ($weights['laporan'] * $laporanScore)
                + ($weights['absensi'] * $absensiScore);

            $rows[] = [
                'siswa_id' => $siswa->id,
                'score' => $score,
                'category' => $score >= 0.7 ? 'rendah' : ($score >= 0.4 ? 'sedang' : 'tinggi'),
            ];
        }

        return $rows;
    }

    private function storeRiskScores(array $rows, Carbon $weekStart, Carbon $weekEnd): int
    {
        $updatedCount = 0;
        $siswaIds = collect($rows)->pluck('siswa_id')->all();
        $currentWeekScores = RiskScore::where('week_start', $weekStart->toDateString())
            ->whereIn('siswa_id', $siswaIds)
            ->get()
            ->keyBy('siswa_id');
        $latestPreviousBySiswa = RiskScore::whereIn('siswa_id', $siswaIds)
            ->where('week_start', '!=', $weekStart->toDateString())
            ->orderByDesc('week_end')
            ->orderByDesc('id')
            ->get()
            ->groupBy('siswa_id')
            ->map(fn ($items) => $items->first());

        foreach ($rows as $row) {
            $baselineCategory = $currentWeekScores->get($row['siswa_id'])?->category
                ?? $latestPreviousBySiswa->get($row['siswa_id'])?->category;

            $riskScore = RiskScore::updateOrCreate(
                [
                    'siswa_id' => $row['siswa_id'],
                    'week_start' => $weekStart->toDateString(),
                ],
                [
                    'week_end' => $weekEnd->toDateString(),
                    'score' => $row['score'],
                    'category' => $row['category'],
                ]
            );

            $this->notificationService->notifyRiskAlert($riskScore, $baselineCategory);
            $updatedCount++;
        }

        return $updatedCount;
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

}
