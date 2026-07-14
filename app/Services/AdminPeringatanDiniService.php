<?php

namespace App\Services;
// hitung skor risiko siswa
use App\Enums\AbsensiStatus;
use App\Enums\PenempatanStatus;
use App\Enums\PerizinanStatus;
use App\Models\AbsensiPkl;
use App\Models\Logbook;
use App\Models\PenempatanPKL;
use App\Models\Perizinan;
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
            'logbook' => 0.5,
            'laporan' => 0.15,
            'absensi' => 0.2,
            'alpha' => 0.15,
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
        $perizinanList = Perizinan::whereIn('siswa_id', $siswaIds)
            ->where('status', PerizinanStatus::DISETUJUI->value)
            ->whereDate('tanggal_mulai', '<=', $weekEnd->toDateString())
            ->whereDate('tanggal_selesai', '>=', $weekStart->toDateString())
            ->get()
            ->groupBy('siswa_id');

        $rows = [];
        foreach ($siswaList as $siswa) {
            $logs = $logbooks->get($siswa->id, collect());
            $totalLogs = $logs->count();

            $absensiLogs = $absensiList->get($siswa->id, collect());
            $validAbsensi = $absensiLogs
                ->whereIn('status', AbsensiStatus::validStatuses())
                ->count();
            $izinDays = $this->countApprovedIzinDays(
                $perizinanList->get($siswa->id, collect()),
                $weekStart,
                $weekEnd
            );
            $alphaDays = max($targetLogbookPerWeek - $validAbsensi - $izinDays, 0);

            $freqScore = ($totalLogs > 0 && $targetLogbookPerWeek > 0)
                ? min($totalLogs / $targetLogbookPerWeek, 1)
                : 0;
            $absensiScore = $targetLogbookPerWeek > 0
                ? min($validAbsensi / $targetLogbookPerWeek, 1)
                : 0;
            $alphaScore = $targetLogbookPerWeek > 0
                ? max(1 - min($alphaDays / $targetLogbookPerWeek, 1), 0)
                : 0;
            $laporanStatus = $penempatanBySiswa->get($siswa->id)?->laporan_status ?? null;
            $laporanScore = $laporanScores[$laporanStatus] ?? 1;

            $score = ($weights['logbook'] * $freqScore)
                + ($weights['laporan'] * $laporanScore)
                + ($weights['absensi'] * $absensiScore)
                + ($weights['alpha'] * $alphaScore);

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

    private function countApprovedIzinDays(Collection $perizinanRows, Carbon $weekStart, Carbon $weekEnd): int
    {
        $days = [];

        foreach ($perizinanRows as $row) {
            $start = $row->tanggal_mulai?->copy()->startOfDay();
            $end = $row->tanggal_selesai?->copy()->startOfDay();
            if (!$start || !$end) {
                continue;
            }

            if ($start->lt($weekStart)) {
                $start = $weekStart->copy();
            }

            if ($end->gt($weekEnd)) {
                $end = $weekEnd->copy();
            }

            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                if (!$cursor->isWeekend()) {
                    $days[$cursor->toDateString()] = true;
                }
            }
        }

        return count($days);
    }

}
