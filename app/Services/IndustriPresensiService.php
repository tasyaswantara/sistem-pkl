<?php

namespace App\Services;

use App\Enums\AbsensiStatus;
use App\Enums\PenempatanStatus;
use App\Models\AbsensiPkl;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\PenempatanPKL;
use App\Models\Siswa;
use Illuminate\Pagination\LengthAwarePaginator;

class IndustriPresensiService
{
    /**
     * @param array{date?:string,jurusan_id?:string,status?:string,q?:string} $filters
     * @return array{absensiList:LengthAwarePaginator,statusCounts:array<string,int>,mapPoints:array<int,array<string,mixed>>}
     */
    public function getIndexData(Industri $industri, array $filters): array
    {
        $date = $filters['date'] ?? now()->toDateString();
        $siswaIds = PenempatanPKL::where('industri_id', $industri->id)
            ->where('status', PenempatanStatus::DITERIMA_INDUSTRI->value)
            ->pluck('siswa_id');

        $baseQuery = AbsensiPkl::query()
            ->with(['siswa.user', 'siswa.jurusan', 'industri'])
            ->whereIn('siswa_id', $siswaIds)
            ->where('industri_id', $industri->id)
            ->whereDate('tanggal', $date);

        if (!empty($filters['jurusan_id'])) {
            $baseQuery->whereHas('siswa', function ($query) use ($filters) {
                $query->where('jurusan_id', $filters['jurusan_id']);
            });
        }

        if (!empty($filters['q'])) {
            $baseQuery->where(function ($query) use ($filters) {
                $query->whereHas('siswa.user', function ($innerQuery) use ($filters) {
                    $innerQuery->where('name', 'like', '%' . $filters['q'] . '%');
                })->orWhereHas('siswa', function ($innerQuery) use ($filters) {
                    $innerQuery->where('nis', 'like', '%' . $filters['q'] . '%');
                });
            });
        }

        $statusCounts = [
            AbsensiStatus::HADIR_VALID->value => (clone $baseQuery)
                ->where('status', AbsensiStatus::HADIR_VALID->value)
                ->count(),
            AbsensiStatus::DI_LUAR_AREA->value => (clone $baseQuery)
                ->where('status', AbsensiStatus::DI_LUAR_AREA->value)
                ->count(),
        ];

        $absensiQuery = clone $baseQuery;
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $absensiQuery->where('status', $filters['status']);
        }

        $absensiList = $absensiQuery
            ->orderByDesc('check_in_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $mapPoints = $absensiList->getCollection()
            ->map(function (AbsensiPkl $row) {
                return [
                    'id' => $row->id,
                    'siswa' => $row->siswa?->user?->name ?? '-',
                    'nis' => $row->siswa?->nis ?? '-',
                    'industri' => $row->industri?->nama_industri ?? '-',
                    'status' => $row->status,
                    'catatan' => $row->catatan,
                    'latitude' => (float) $row->latitude,
                    'longitude' => (float) $row->longitude,
                    'distance' => $row->distance_to_industri_m,
                    'check_in_at' => optional($row->check_in_at)->format('d/m/Y H:i'),
                ];
            })
            ->values()
            ->all();

        return [
            'absensiList' => $absensiList,
            'statusCounts' => $statusCounts,
            'mapPoints' => $mapPoints,
        ];
    }

    /**
     * @return array{jurusanOptions:\Illuminate\Support\Collection}
     */
    public function getOptions(Industri $industri): array
    {
        $siswaIds = PenempatanPKL::where('industri_id', $industri->id)
            ->where('status', PenempatanStatus::DITERIMA_INDUSTRI->value)
            ->pluck('siswa_id');

        $jurusanOptions = Jurusan::whereIn(
            'id',
            Siswa::whereIn('id', $siswaIds)->pluck('jurusan_id')->unique()
        )->orderBy('nama')->get();

        return [
            'jurusanOptions' => $jurusanOptions,
        ];
    }
}
