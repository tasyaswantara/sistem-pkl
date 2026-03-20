<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\Logbook;
use App\Models\PenempatanPKL;
use App\Models\RiskScore;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GuruPeringatanDiniController extends Controller
{
    public function index(Request $request)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, __('guru_peringatan_dini.errors.akun'));
        }

        $filters = [
            'q' => $request->input('q'),
            'category' => $request->input('category', 'all'),
            'jurusan_id' => $request->input('jurusan_id'),
            'tahun_ajaran' => $request->input('tahun_ajaran'),
        ];

        $siswaIds = PenempatanPKL::where('guru_pembimbing_id', $guru->id)->pluck('siswa_id');
        $jurusanOptions = Jurusan::whereIn(
            'id',
            Siswa::whereIn('id', $siswaIds)->pluck('jurusan_id')->unique()
        )->orderBy('nama')->get();
        $tahunAjaranOptions = Siswa::whereIn('id', $siswaIds)
            ->select('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');

        $riskBaseQuery = RiskScore::with(['siswa.user', 'siswa.jurusan'])
            ->whereIn('siswa_id', $siswaIds)
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

        $latestWeekEnd = (clone $riskBaseQuery)->max('week_end');
        $riskScores = collect();
        $detailByRiskId = [];
        $weekStart = null;
        $weekEnd = null;

        if ($latestWeekEnd) {
            $weekEnd = Carbon::parse($latestWeekEnd);
            $latestWeekStart = (clone $riskBaseQuery)
                ->where('week_end', $latestWeekEnd)
                ->max('week_start');
            $weekStart = $latestWeekStart ? Carbon::parse($latestWeekStart) : null;

            if (!$weekStart) {
                return view('guru.peringatan-dini.guru-peringatan-dini', [
                    'riskScores' => $riskScores,
                    'weekStart' => $weekStart,
                    'weekEnd' => $weekEnd,
                    'detailByRiskId' => $detailByRiskId,
                    'filters' => $filters,
                    'jurusanOptions' => $jurusanOptions,
                    'tahunAjaranOptions' => $tahunAjaranOptions,
                ]);
            }

            $riskQuery = (clone $riskBaseQuery)
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

            $riskItems = $riskScores->getCollection();
            $pageSiswaIds = $riskItems->pluck('siswa_id')->all();
            $penempatanBySiswa = PenempatanPKL::whereIn('siswa_id', $pageSiswaIds)
                ->orderByDesc('id')
                ->get()
                ->groupBy('siswa_id')
                ->map(fn ($rows) => $rows->first());
            $logbooks = Logbook::whereIn('siswa_id', $pageSiswaIds)
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

        return view('guru.peringatan-dini.guru-peringatan-dini', [
            'riskScores' => $riskScores,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd ? Carbon::parse($weekEnd) : null,
            'detailByRiskId' => $detailByRiskId,
            'filters' => $filters,
            'jurusanOptions' => $jurusanOptions,
            'tahunAjaranOptions' => $tahunAjaranOptions,
        ]);
    }
}
