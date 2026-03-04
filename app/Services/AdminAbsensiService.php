<?php

namespace App\Services;

use App\Enums\AbsensiStatus;
use App\Models\AbsensiPkl;
use App\Models\Industri;
use App\Models\Jurusan;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdminAbsensiService
{
    /**
     * @param array{date?:string,jurusan_id?:string,industri_id?:string,status?:string,q?:string} $filters
     * @return array{
     *  absensiList:LengthAwarePaginator,
     *  statusCounts:array<string,int>,
     *  mapPoints:array<int,array<string,mixed>>,
     *  geofenceList:Collection<int,Industri>,
     *  globalRadiusM:int,
     *  radiusUniform:bool
     * }
     */
    public function getIndexData(array $filters): array
    {
        $date = $filters['date'] ?? now()->toDateString();
        $baseQuery = AbsensiPkl::query()
            ->with(['siswa.user', 'siswa.jurusan', 'industri'])
            ->whereDate('tanggal', $date);

        if (!empty($filters['jurusan_id'])) {
            $baseQuery->whereHas('siswa', function ($query) use ($filters) {
                $query->where('jurusan_id', $filters['jurusan_id']);
            });
        }

        if (!empty($filters['industri_id'])) {
            $baseQuery->where('industri_id', $filters['industri_id']);
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
                    'latitude' => (float) $row->latitude,
                    'longitude' => (float) $row->longitude,
                    'distance' => $row->distance_to_industri_m,
                    'check_in_at' => optional($row->check_in_at)->format('d/m/Y H:i'),
                ];
            })
            ->values()
            ->all();

        $geofenceList = Industri::with('jurusan')
            ->when(!empty($filters['jurusan_id']), function ($query) use ($filters) {
                $query->where('jurusan_id', $filters['jurusan_id']);
            })
            ->orderBy('nama_industri')
            ->get();

        $radiusStats = Industri::query()
            ->selectRaw('MIN(geofence_radius_m) as min_radius, MAX(geofence_radius_m) as max_radius')
            ->first();
        $minRadius = $radiusStats?->min_radius !== null ? (int) $radiusStats->min_radius : null;
        $maxRadius = $radiusStats?->max_radius !== null ? (int) $radiusStats->max_radius : null;
        $globalRadiusM = $maxRadius ?? 200;
        $radiusUniform = $minRadius !== null && $maxRadius !== null && $minRadius === $maxRadius;

        return [
            'absensiList' => $absensiList,
            'statusCounts' => $statusCounts,
            'mapPoints' => $mapPoints,
            'geofenceList' => $geofenceList,
            'globalRadiusM' => $globalRadiusM,
            'radiusUniform' => $radiusUniform,
        ];
    }

    /**
     * @return array{jurusanOptions:Collection<int,Jurusan>,industriOptions:Collection<int,Industri>}
     */
    public function getOptions(): array
    {
        return [
            'jurusanOptions' => Jurusan::orderBy('nama')->get(),
            'industriOptions' => Industri::orderBy('nama_industri')->get(),
        ];
    }

    /**
     * @param array{latitude:float,longitude:float,geofence_radius_m:int} $data
     */
    public function updateGeofence(Industri $industri, array $data): void
    {
        $industri->update([
            'latitude' => round((float) $data['latitude'], 7),
            'longitude' => round((float) $data['longitude'], 7),
            'geofence_radius_m' => (int) $data['geofence_radius_m'],
        ]);
    }

    public function updateGlobalRadius(int $radiusMeter): void
    {
        Industri::query()->update([
            'geofence_radius_m' => $radiusMeter,
        ]);
    }
}
