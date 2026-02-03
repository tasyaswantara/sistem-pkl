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
    public function calculate(Request $request)
    {
        $weekStart = $request->input('week_start')
            ? Carbon::parse($request->input('week_start'))->startOfDay()
            : now()->subDays(6)->startOfDay();
        $weekEnd = $request->input('week_end')
            ? Carbon::parse($request->input('week_end'))->endOfDay()
            : now()->endOfDay();

        $targetLogbookPerWeek = 0;
        $cursor = $weekStart->copy()->startOfDay();
        $endCursor = $weekEnd->copy()->startOfDay();
        while ($cursor->lessThanOrEqualTo($endCursor)) {
            if (!$cursor->isWeekend()) {
                $targetLogbookPerWeek++;
            }
            $cursor->addDay();
        }
        $weights = [
            'logbook' => 0.5,
            'status' => 0.4,
            'late' => 0.1,
        ];
        $statusScores = [
            'diterima_industri' => 1.0,
            'proses_wawancara' => 0.8,
            'proses_pengajuan' => 0.8,
            'menunggu_konfirmasi' => 0.8,
            'belum_memilih' => 0.2,
            'ditolak_sekolah' => 0.1,
            'pengajuan_ditolak_industri' => 0.1,
            'tidak_lolos_industri' => 0.1,
        ];

        $siswaList = Siswa::select('id')->get();
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

        $updatedCount = 0;
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
            $status = $penempatanBySiswa->get($siswa->id)?->status ?? 'belum_memilih';
            $statusScore = $statusScores[$status] ?? 0.3;

            $score = ($weights['logbook'] * $freqScore)
                + ($weights['late'] * $lateScore)
                + ($weights['status'] * $statusScore);

            $category = $score >= 0.7 ? 'rendah' : ($score >= 0.4 ? 'sedang' : 'tinggi');

            RiskScore::updateOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'week_start' => $weekStart->toDateString(),
                ],
                [
                    'week_end' => $weekEnd->toDateString(),
                    'score' => $score,
                    'category' => $category,
                ]
            );

            $updatedCount++;
        }

        return back()->with('success', 'Risk score diperbarui untuk ' . $updatedCount . ' siswa.');
    }

    public function index()
    {
        $latestWeekStart = RiskScore::max('week_start');
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

        if ($latestWeekStart) {
            $weekStart = Carbon::parse($latestWeekStart);
            $weekEnd = RiskScore::where('week_start', $latestWeekStart)->max('week_end');

            $riskQuery = RiskScore::with(['siswa.user', 'siswa.jurusan'])
                ->where('week_start', $latestWeekStart);

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
                ->whereBetween('tanggal', [$weekStart->toDateString(), Carbon::parse($weekEnd)->toDateString()])
                ->get()
                ->groupBy('siswa_id');

            $targetLogbookPerWeek = 0;
            $cursor = $weekStart->copy()->startOfDay();
            $endCursor = Carbon::parse($weekEnd)->startOfDay();
            while ($cursor->lessThanOrEqualTo($endCursor)) {
                if (!$cursor->isWeekend()) {
                    $targetLogbookPerWeek++;
                }
                $cursor->addDay();
            }

            $statusScores = [
                'diterima_industri' => 1.0,
                'proses_wawancara' => 0.7,
                'proses_pengajuan' => 0.6,
                'menunggu_konfirmasi' => 0.6,
                'belum_memilih' => 0.3,
                'ditolak_sekolah' => 0.2,
                'pengajuan_ditolak_industri' => 0.2,
                'tidak_lolos_industri' => 0.2,
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
                $status = $penempatanBySiswa->get($row->siswa_id)?->status ?? 'belum_memilih';
                $statusScore = $statusScores[$status] ?? 0.3;

                $detailByRiskId[$row->id] = [
                    'total_logs' => $totalLogs,
                    'late_logs' => $lateLogs,
                    'target_logs' => $targetLogbookPerWeek,
                    'freq_score' => $freqScore,
                    'late_score' => $lateScore,
                    'status' => $status,
                    'status_score' => $statusScore,
                ];
            }
        }

        return view('admin.risk.index', [
            'riskScores' => $riskScores,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd ? Carbon::parse($weekEnd) : null,
            'detailByRiskId' => $detailByRiskId,
            'filters' => $filters,
            'jurusanOptions' => $jurusanOptions,
        ]);
    }
}
