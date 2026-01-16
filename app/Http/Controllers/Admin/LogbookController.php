<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\Logbook;
use App\Models\Siswa;
use Illuminate\Http\Request;

class LogbookController extends Controller
{
    public function index(Request $request)
    {
        $jurusanOptions = Jurusan::orderBy('nama')->get();
        $industriOptions = Industri::orderBy('nama_industri')->get();

        $tahunAjaranList = Siswa::query()
            ->select('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');

        $filters = [
            'tahun_ajaran' => $request->input('tahun_ajaran'),
            'jurusan_id' => $request->input('jurusan_id'),
            'industri_id' => $request->input('industri_id'),
            'status' => $request->input('status', 'all'),
            'q' => $request->input('q', ''),
        ];

        $baseQuery = Logbook::query()
            ->with(['siswa.user', 'siswa.jurusan', 'industri']);

        if ($filters['tahun_ajaran']) {
            $baseQuery->whereHas('siswa', function ($query) use ($filters) {
                $query->where('tahun_ajaran', $filters['tahun_ajaran']);
            });
        }

        if ($filters['jurusan_id']) {
            $baseQuery->whereHas('siswa', function ($query) use ($filters) {
                $query->where('jurusan_id', $filters['jurusan_id']);
            });
        }

        if ($filters['industri_id']) {
            $baseQuery->where('industri_id', $filters['industri_id']);
        }

        if ($filters['q']) {
            $baseQuery->whereHas('siswa.user', function ($query) use ($filters) {
                $query->where('name', 'like', '%' . $filters['q'] . '%');
            });
        }

        $statusCounts = [
            'pending' => (clone $baseQuery)->where('status_validasi', 'pending')->count(),
            'disetujui' => (clone $baseQuery)->where('status_validasi', 'disetujui')->count(),
            'ditolak' => (clone $baseQuery)->where('status_validasi', 'ditolak')->count(),
        ];

        $logbookQuery = clone $baseQuery;
        if ($filters['status'] !== 'all') {
            $logbookQuery->where('status_validasi', $filters['status']);
        }

        $logbooks = $logbookQuery
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $statusLabels = [
            'all' => 'Semua Status',
            'pending' => 'Pending',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
        ];

        return view('admin.elogbook.index', [
            'jurusanOptions' => $jurusanOptions,
            'industriOptions' => $industriOptions,
            'tahunAjaranList' => $tahunAjaranList,
            'filters' => $filters,
            'statusCounts' => $statusCounts,
            'statusLabels' => $statusLabels,
            'logbooks' => $logbooks,
        ]);
    }
}
