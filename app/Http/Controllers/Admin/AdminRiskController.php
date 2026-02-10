<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use App\Models\PenempatanPKL;
use App\Models\RiskScore;
use App\Models\Siswa;
use App\Models\Jurusan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminRiskController extends Controller
{
    public function runRisk(Request $request)
    {
        $weekStartInput = $request->input('week_start');
        $weekEndInput = $request->input('week_end');
        if ($weekStartInput && $weekEndInput) {
            $weekStartCheck = Carbon::parse($weekStartInput);
            $weekEndCheck = Carbon::parse($weekEndInput);
            if ($weekEndCheck->lt($weekStartCheck)) {
                return back()
                    ->withErrors(['week_end' => 'Tanggal akhir harus sama atau setelah tanggal awal.'])
                    ->withInput();
            }
            if ($weekEndCheck->gt(now()->endOfDay())) {
                return back()
                    ->withErrors(['week_end' => 'Tanggal akhir tidak boleh melewati tanggal hari ini.'])
                    ->withInput();
            }
        }

        $weekStart = $weekStartInput
            ? Carbon::parse($weekStartInput)->startOfDay()
            : now()->subDays(6)->startOfDay();
        $weekEnd = $weekEndInput
            ? Carbon::parse($weekEndInput)->endOfDay()
            : now()->endOfDay();

        $siswaList = Siswa::whereHas('penempatanPkl', function ($query) {
                $query->where('status', 'diterima_industri');
            })
            ->select('id')
            ->get();
        $rows = $this->calculateRiskScores($siswaList, $weekStart, $weekEnd);
        $updatedCount = $this->storeRiskScores($rows, $weekStart, $weekEnd);

        return back()->with('success', 'Risk score diperbarui untuk ' . $updatedCount . ' siswa.');
    }

    public function index()
    {
        $latestWeekEnd = RiskScore::max('week_end');
        $riskScores = collect();
        $detailByRiskId = [];
        $weekStart = null;
        $weekEnd = null;
        $filters = [
            'q' => request()->input('q'),
            'category' => request()->input('category', 'all'),
            'jurusan_id' => request()->input('jurusan_id'),
        ];

        $jurusanOptions = Jurusan::orderBy('nama')->get();

        if ($latestWeekEnd) {
            $weekEnd = Carbon::parse($latestWeekEnd);
            $latestWeekStart = RiskScore::where('week_end', $latestWeekEnd)->max('week_start');
            $weekStart = $latestWeekStart ? Carbon::parse($latestWeekStart) : null;

            if (!$weekStart) {
                return view('admin.risk.admin-risk', [
                    'riskScores' => $riskScores,
                    'weekStart' => $weekStart,
                    'weekEnd' => $weekEnd,
                    'detailByRiskId' => $detailByRiskId,
                    'filters' => $filters,
                    'jurusanOptions' => $jurusanOptions,
                ]);
            }

            $riskQuery = RiskScore::with(['siswa.user', 'siswa.jurusan'])
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

            if (!empty($filters['jurusan_id'])) {
                $riskQuery->whereHas('siswa', function ($query) use ($filters) {
                    $query->where('jurusan_id', $filters['jurusan_id']);
                });
            }

            $riskScores = $riskQuery
                ->orderBy('score')
                ->paginate(15)
                ->withQueryString();

            $riskItems = $riskScores->getCollection();
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

            $targetLogbookPerWeek = 0;
            $cursor = $weekStart->copy()->startOfDay();
            $endCursor = $weekEnd->copy()->startOfDay();
            while ($cursor->lessThanOrEqualTo($endCursor)) {
                if (!$cursor->isWeekend()) {
                    $targetLogbookPerWeek++;
                }
                $cursor->addDay();
            }

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

                $freqScore = ($totalLogs > 0 && $targetLogbookPerWeek > 0)
                    ? min($totalLogs / $targetLogbookPerWeek, 1)
                    : 0;
                $lateScore = $totalLogs > 0
                    ? 1 - min($lateLogs / $totalLogs, 1)
                    : 0;
                $laporanStatus = $penempatanBySiswa->get($row->siswa_id)?->laporan_status ?? null;
                $laporanScore = $laporanScores[$laporanStatus] ?? 1;

                $detailByRiskId[$row->id] = [
                    'total_logs' => $totalLogs,
                    'late_logs' => $lateLogs,
                    'target_logs' => $targetLogbookPerWeek,
                    'freq_score' => $freqScore,
                    'late_score' => $lateScore,
                    'laporan_status' => $laporanStatus ?? 'belum ada',
                    'laporan_score' => $laporanScore,
                ];
            }
        }

        return view('admin.risk.admin-risk', [
            'riskScores' => $riskScores,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd ? Carbon::parse($weekEnd) : null,
            'detailByRiskId' => $detailByRiskId,
            'filters' => $filters,
            'jurusanOptions' => $jurusanOptions,
        ]);
    }

    private function calculateRiskScores($siswaList, Carbon $weekStart, Carbon $weekEnd): array
    {
        $targetLogbookPerWeek = $this->countWeekdays($weekStart, $weekEnd);
        $weights = [
            'logbook' => 0.45,
            'late' => 0.35,
            'laporan' => 0.2,
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

            $freqScore = ($totalLogs > 0 && $targetLogbookPerWeek > 0)
                ? min($totalLogs / $targetLogbookPerWeek, 1)
                : 0;
            $lateScore = $totalLogs > 0
                ? 1 - min($lateLogs / $totalLogs, 1)
                : 0;
            $laporanStatus = $penempatanBySiswa->get($siswa->id)?->laporan_status ?? null;
            $laporanScore = $laporanScores[$laporanStatus] ?? 1;

            $score = ($weights['logbook'] * $freqScore)
                + ($weights['late'] * $lateScore)
                + ($weights['laporan'] * $laporanScore);

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
        foreach ($rows as $row) {
            RiskScore::updateOrCreate(
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
