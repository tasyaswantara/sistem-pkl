<?php

namespace App\Services;
// cek lokasi, hitung jarak, penentuan status
use App\Enums\AbsensiStatus;
use App\Enums\PenempatanStatus;
use App\Models\AbsensiPkl;
use App\Models\Industri;
use App\Models\PenempatanPKL;
use App\Models\Siswa;
use Illuminate\Pagination\LengthAwarePaginator;

class SiswaPresensiCheckInService
{
    /**
     * @return array{penempatan:?PenempatanPKL,todayAbsensi:?AbsensiPkl,absensiList:LengthAwarePaginator}
     */
    public function getIndexData(Siswa $siswa): array
    {
        $penempatan = PenempatanPKL::with('industri')
            ->where('siswa_id', $siswa->id)
            ->latest('id')
            ->first();

        $todayAbsensi = AbsensiPkl::with('industri')
            ->where('siswa_id', $siswa->id)
            ->whereDate('tanggal', now()->toDateString())
            ->first();

        $absensiList = AbsensiPkl::with('industri')
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return [
            'penempatan' => $penempatan,
            'todayAbsensi' => $todayAbsensi,
            'absensiList' => $absensiList,
        ];
    }

    /**
     * @param array{latitude:float,longitude:float,accuracy_m?:float,catatan?:string|null} $data
     * @return array{ok:bool,error_key?:string,success_key?:string,absensi?:AbsensiPkl}
     */
    public function createCheckIn(Siswa $siswa, array $data): array
    {
        $penempatan = PenempatanPKL::with('industri')
            ->where('siswa_id', $siswa->id)
            ->where('status', PenempatanStatus::DITERIMA_INDUSTRI->value)
            ->whereNotNull('industri_id')
            ->latest('id')
            ->first();

        if (!$penempatan || !$penempatan->industri) {
            return [
                'ok' => false,
                'error_key' => 'presensi.errors.terima',
            ];
        }

        $industri = $penempatan->industri;
        if ($industri->latitude === null || $industri->longitude === null) {
            return [
                'ok' => false,
                'error_key' => 'presensi.errors.geofence',
            ];
        }

        $today = now()->toDateString();
        // menentukan status sudah absen hari ini
        $alreadyCheckedIn = AbsensiPkl::where('siswa_id', $siswa->id)
            ->whereDate('tanggal', $today)
            ->exists();

        if ($alreadyCheckedIn) {
            return [
                'ok' => false,
                'error_key' => 'presensi.errors.duplikat',
            ];
        }

        $geofenceResult = $this->resolveGeofenceStatus(
            $industri,
            (float) $data['latitude'],
            (float) $data['longitude']
        );
        $catatan = isset($data['catatan']) ? trim((string) $data['catatan']) : null;
        if (!$geofenceResult['is_within_geofence'] && $catatan === '') {
            $catatan = null;
        }

        if (!$geofenceResult['is_within_geofence'] && !$catatan) {
            return [
                'ok' => false,
                'error_key' => 'presensi.errors.alasan_luar_area',
            ];
        }

        $absensi = AbsensiPkl::create([
            'siswa_id' => $siswa->id,
            'industri_id' => $penempatan->industri_id,
            'tanggal' => $today,
            'check_in_at' => now(),
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'accuracy_m' => isset($data['accuracy_m']) ? (float) $data['accuracy_m'] : null,
            'distance_to_industri_m' => $geofenceResult['distance'],
            'is_within_geofence' => $geofenceResult['is_within_geofence'],
            'status' => $geofenceResult['status'],
            'approval_status' => $geofenceResult['approval_status'],
            'catatan' => $catatan,
        ]);

        return [
            'ok' => true,
            'absensi' => $absensi,
            'success_key' => $geofenceResult['is_within_geofence']
                ? 'presensi.success.checkin'
                : 'presensi.success.checkin_pending',
        ];
    }

    /**
     * @return array{distance:?float,is_within_geofence:bool,status:string,approval_status:?string}
     */
    private function resolveGeofenceStatus(Industri $industri, float $latitude, float $longitude): array
    {
        $distance = $this->calculateDistanceMeters(
            (float) $industri->latitude,
            (float) $industri->longitude,
            $latitude,
            $longitude
        );

        $radius = max((int) ($industri->geofence_radius_m ?? 200), 10);
        $isWithin = $distance <= $radius;

        return [
            'distance' => round($distance, 2),
            'is_within_geofence' => $isWithin,
            'status' => $isWithin
                ? AbsensiStatus::HADIR_VALID_LOKASI->value
                : AbsensiStatus::MENUNGGU_PERSETUJUAN_LUAR_LOKASI->value,
            'approval_status' => $isWithin ? null : 'menunggu',
        ];
    }

    private function calculateDistanceMeters(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadius = 6371000; // meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
