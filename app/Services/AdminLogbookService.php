<?php

namespace App\Services;

use App\Enums\LogbookStatus;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\Logbook;
use App\Models\Siswa;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminLogbookService
{
    /**
     * @return array{jurusanOptions:\Illuminate\Support\Collection,industriOptions:\Illuminate\Support\Collection,tahunAjaranList:\Illuminate\Support\Collection}
     */
    public function getOptions(): array
    {
        $jurusanOptions = Jurusan::orderBy('nama')->get();
        $industriOptions = Industri::orderBy('nama_industri')->get();

        $tahunAjaranList = Siswa::query()
            ->select('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');

        return [
            'jurusanOptions' => $jurusanOptions,
            'industriOptions' => $industriOptions,
            'tahunAjaranList' => $tahunAjaranList,
        ];
    }

    /**
     * @param array{tahun_ajaran?:string,jurusan_id?:string,industri_id?:string,status?:string,q?:string} $filters
     * @return array{logbooks:LengthAwarePaginator,statusCounts:array<string,int>}
     */
    public function getLogbookData(array $filters): array
    {
        $baseQuery = Logbook::query()
            ->with(['siswa.user', 'siswa.jurusan', 'industri', 'komentar']);

        if (!empty($filters['tahun_ajaran'])) {
            $baseQuery->whereHas('siswa', function ($query) use ($filters) {
                $query->where('tahun_ajaran', $filters['tahun_ajaran']);
            });
        }

        if (!empty($filters['jurusan_id'])) {
            $baseQuery->whereHas('siswa', function ($query) use ($filters) {
                $query->where('jurusan_id', $filters['jurusan_id']);
            });
        }

        if (!empty($filters['industri_id'])) {
            $baseQuery->where('industri_id', $filters['industri_id']);
        }

        if (!empty($filters['q'])) {
            $baseQuery->whereHas('siswa.user', function ($query) use ($filters) {
                $query->where('name', 'like', '%' . $filters['q'] . '%');
            });
        }

        $statusCounts = [
            LogbookStatus::PENDING->value => (clone $baseQuery)
                ->where('status_validasi', LogbookStatus::PENDING->value)
                ->count(),
            LogbookStatus::DISETUJUI->value => (clone $baseQuery)
                ->where('status_validasi', LogbookStatus::DISETUJUI->value)
                ->count(),
            LogbookStatus::DITOLAK->value => (clone $baseQuery)
                ->where('status_validasi', LogbookStatus::DITOLAK->value)
                ->count(),
        ];

        $logbookQuery = clone $baseQuery;
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $logbookQuery->where('status_validasi', $filters['status']);
        }

        $logbooks = $logbookQuery
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return [
            'logbooks' => $logbooks,
            'statusCounts' => $statusCounts,
        ];
    }
}
