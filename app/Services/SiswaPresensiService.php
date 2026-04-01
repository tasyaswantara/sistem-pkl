<?php

namespace App\Services;

use App\Enums\PenempatanStatus;
use App\Enums\PerizinanStatus;
use App\Enums\AbsensiStatus;
use App\Models\AbsensiPkl;
use App\Models\Logbook;
use App\Models\PenempatanPKL;
use App\Models\Perizinan;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SiswaPresensiService
{
    /**
     * @return array{
     *   penempatan:?PenempatanPKL,
     *   todayAbsensi:?AbsensiPkl,
     *   canCheckIn:bool,
     *   canRequestIzin:bool,
     *   weekDays:array<int,array<string,mixed>>,
     *   weekCounts:array{hadir:int,izin:int,alpha:int},
     *   logbooks:LengthAwarePaginator,
     *   logbookTotal:int,
     *   perizinanLatest:Collection<int,Perizinan>
     * }
     */
    public function getPageData(Siswa $siswa): array
    {
        $penempatan = PenempatanPKL::with('industri')
            ->where('siswa_id', $siswa->id)
            ->latest('id')
            ->first();

        $industri = $penempatan?->industri;
        $isPenempatanAktif = $penempatan?->status === PenempatanStatus::DITERIMA_INDUSTRI->value && $industri;
        $geofenceSet = $industri?->latitude !== null && $industri?->longitude !== null;

        $todayAbsensi = AbsensiPkl::with('industri')
            ->where('siswa_id', $siswa->id)
            ->whereDate('tanggal', now()->toDateString())
            ->first();

        $weekStart = now()->copy()->subDays(6)->startOfDay();
        $weekEnd = now()->copy()->endOfDay();

        $absensiThisWeek = AbsensiPkl::query()
            ->where('siswa_id', $siswa->id)
            ->whereBetween('tanggal', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get()
            ->keyBy(static fn (AbsensiPkl $row) => $row->tanggal?->toDateString());

        $perizinanThisWeek = Perizinan::query()
            ->where('siswa_id', $siswa->id)
            ->whereIn('status', [PerizinanStatus::MENUNGGU->value, PerizinanStatus::DISETUJUI->value])
            ->whereDate('tanggal_mulai', '<=', $weekEnd->toDateString())
            ->whereDate('tanggal_selesai', '>=', $weekStart->toDateString())
            ->get();

        $weekDays = [];
        $weekCounts = [
            'hadir' => 0,
            'izin' => 0,
            'alpha' => 0,
        ];

        for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
            $date = $weekStart->copy()->addDays($dayOffset);
            $dateKey = $date->toDateString();
            $absensiRow = $absensiThisWeek->get($dateKey);
            $hasAbsensi = $absensiRow && in_array((string) $absensiRow->status, AbsensiStatus::validStatuses(), true);
            $hasIzin = $this->hasIzinOnDate($perizinanThisWeek, $date);

            $state = 'alpha';
            if ($hasAbsensi) {
                $state = 'hadir';
            } elseif ($hasIzin) {
                $state = 'izin';
            }

            $weekCounts[$state]++;

            $weekDays[] = [
                'date' => $dateKey,
                'day_name' => $date->locale('id')->translatedFormat('D'),
                'day_number' => $date->format('d'),
                'state' => $state,
                'is_today' => $date->isToday(),
            ];
        }

        $logbooks = Logbook::with(['industri', 'komentar.guruPembimbing.user'])
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(8, ['*'], 'logbook_page')
            ->withQueryString();

        $logbookTotal = Logbook::where('siswa_id', $siswa->id)->count();

        $perizinanLatest = Perizinan::with('industri')
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return [
            'penempatan' => $penempatan,
            'todayAbsensi' => $todayAbsensi,
            'canCheckIn' => (bool) ($isPenempatanAktif && $geofenceSet && !$todayAbsensi),
            'canRequestIzin' => (bool) $isPenempatanAktif,
            'weekDays' => $weekDays,
            'weekCounts' => $weekCounts,
            'logbooks' => $logbooks,
            'logbookTotal' => $logbookTotal,
            'perizinanLatest' => $perizinanLatest,
        ];
    }

    private function hasIzinOnDate(Collection $perizinanRows, Carbon $date): bool
    {
        foreach ($perizinanRows as $row) {
            $start = $row->tanggal_mulai?->copy()->startOfDay();
            $end = $row->tanggal_selesai?->copy()->endOfDay();
            if (!$start || !$end) {
                continue;
            }

            if ($date->between($start, $end)) {
                return true;
            }
        }

        return false;
    }
}
