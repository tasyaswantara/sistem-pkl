<?php

namespace App\Services;

use App\Enums\PenempatanStatus;
use App\Enums\PerizinanStatus;
use App\Models\HasilRekomendasi;
use App\Models\JadwalWawancara;
use App\Models\PenempatanPKL;
use App\Models\Perizinan;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiswaDashboardService
{
    /**
     * @return array{
     *  penempatan:?PenempatanPKL,
     *  rekomendasi:Collection<int,mixed>,
     *  profilePhotoUrl:?string,
     *  monthLabel:string,
     *  monthValue:string,
     *  prevMonth:string,
     *  nextMonth:string,
     *  calendarCells:array<int,array<string,mixed>>,
     *  calendarEventMap:array<string,array<int,array<string,string>>>,
     *  timelineSteps:array<int,array<string,string>>,
     *  primaryAction:array{label:string,route:string,description:string}
     * }
     */
    public function getDashboardData(Siswa $siswa, ?string $month = null, bool $berkasComplete = false): array
    {
        $monthStart = $this->resolveMonthStart($month);
        $monthEnd = $monthStart->copy()->endOfMonth();

        $penempatan = PenempatanPKL::with(['industri', 'usulanIndustri'])
            ->where('siswa_id', $siswa->id)
            ->latest('id')
            ->first();

        $latestRunId = HasilRekomendasi::where('siswa_id', $siswa->id)->max('saw_run_id');
        $rekomendasi = collect();
        if ($latestRunId) {
            $rekomendasi = HasilRekomendasi::with('industri')
                ->where('saw_run_id', $latestRunId)
                ->where('siswa_id', $siswa->id)
                ->orderBy('peringkat')
                ->limit(10)
                ->get();
        }

        $jadwalWawancara = JadwalWawancara::with('industri')
            ->where('siswa_id', $siswa->id)
            ->whereBetween('tanggal', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('tanggal')
            ->orderBy('waktu')
            ->get();

        $perizinan = Perizinan::with('industri')
            ->where('siswa_id', $siswa->id)
            ->whereIn('status', [
                PerizinanStatus::MENUNGGU->value,
                PerizinanStatus::DISETUJUI->value,
            ])
            ->whereDate('tanggal_mulai', '<=', $monthEnd->toDateString())
            ->whereDate('tanggal_selesai', '>=', $monthStart->toDateString())
            ->orderBy('tanggal_mulai')
            ->get();

        [$calendarCells, $calendarEventMap] = $this->buildCalendarData($monthStart, $monthEnd, $jadwalWawancara, $perizinan);

        $profilePhotoUrl = $this->resolveProfilePhotoUrl($siswa);

        return [
            'penempatan' => $penempatan,
            'rekomendasi' => $rekomendasi,
            'profilePhotoUrl' => $profilePhotoUrl,
            'monthLabel' => $monthStart->locale('id')->translatedFormat('F Y'),
            'monthValue' => $monthStart->format('Y-m'),
            'prevMonth' => $monthStart->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $monthStart->copy()->addMonth()->format('Y-m'),
            'calendarCells' => $calendarCells,
            'calendarEventMap' => $calendarEventMap,
            'timelineSteps' => $this->buildTimelineSteps($penempatan?->status, $berkasComplete),
            'primaryAction' => $this->resolvePrimaryAction($penempatan?->status),
        ];
    }

    private function resolveMonthStart(?string $month): Carbon
    {
        if (!$month) {
            return now()->startOfMonth();
        }

        try {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    /**
     * @return array{0:array<int,array<string,mixed>>,1:array<string,array<int,array<string,string>>>}
     */
    // digunakan untuk maping jadwal dan perizinan
    private function buildCalendarData(
        Carbon $monthStart,
        Carbon $monthEnd,
        Collection $jadwalWawancara,
        Collection $perizinan
    ): array {
        $eventMap = [];

        foreach ($jadwalWawancara as $jadwal) {
            $dateKey = $jadwal->tanggal?->toDateString();
            if (!$dateKey) {
                continue;
            }

            $eventMap[$dateKey][] = [
                'type' => 'wawancara',
                'title' => 'Wawancara: ' . ($jadwal->industri?->nama_industri ?? 'Industri'),
                'subtitle' => $jadwal->lokasi ?: '-',
                'time' => $jadwal->waktu?->format('H:i') ?? '-',
                'status' => ucfirst((string) $jadwal->status),
            ];
        }
        // mapping data perizinan karena bisa lebih dari sehari
        foreach ($perizinan as $izin) {
            $start = $izin->tanggal_mulai ? $izin->tanggal_mulai->copy()->startOfDay() : null;
            $end = $izin->tanggal_selesai ? $izin->tanggal_selesai->copy()->startOfDay() : null;
            if (!$start || !$end) {
                continue;
            }

            if ($start->lt($monthStart)) {
                $start = $monthStart->copy();
            }
            if ($end->gt($monthEnd)) {
                $end = $monthEnd->copy();
            }

            $statusLabel = match ($izin->status) {
                PerizinanStatus::DISETUJUI->value => 'Disetujui',
                PerizinanStatus::DITOLAK->value => 'Ditolak',
                default => 'Menunggu',
            };

            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                $dateKey = $cursor->toDateString();
                $eventMap[$dateKey][] = [
                    'type' => 'perizinan',
                    'title' => 'Perizinan: ' . ucfirst(str_replace('_', ' ', (string) $izin->jenis_izin)),
                    'subtitle' => $izin->industri?->nama_industri ?? '-',
                    'time' => '-',
                    'status' => $statusLabel,
                ];
            }
        }
        // startnya selalu sunday
        $calendarStart = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $monthEnd->copy()->endOfWeek(Carbon::SATURDAY);

        // memetakan sel sel kalender dari data
        $calendarCells = [];
        for ($cursor = $calendarStart->copy(); $cursor->lte($calendarEnd); $cursor->addDay()) {
            $dateKey = $cursor->toDateString();
            $events = $eventMap[$dateKey] ?? [];

            $hasWawancara = false;
            $hasPerizinan = false;
            foreach ($events as $event) {
                if (($event['type'] ?? '') === 'wawancara') {
                    $hasWawancara = true;
                }
                if (($event['type'] ?? '') === 'perizinan') {
                    $hasPerizinan = true;
                }
            }

            $calendarCells[] = [
                'date' => $dateKey,
                'day' => $cursor->day,
                'in_current_month' => $cursor->month === $monthStart->month,
                'has_wawancara' => $hasWawancara,
                'has_perizinan' => $hasPerizinan,
                'event_count' => count($events),
            ];
        }

        return [$calendarCells, $eventMap];
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function buildTimelineSteps(?string $status, bool $berkasComplete): array
    {
        $status = $status ?? PenempatanStatus::BELUM_MEMILIH->value;

        $steps = [
            ['label' => 'Pengajuan Berkas', 'state' => 'pending', 'hint' => $berkasComplete ? 'Berkas lengkap' : 'Lengkapi berkas terlebih dahulu'],
            ['label' => 'Pengajuan Industri', 'state' => 'pending', 'hint' => 'Pilih rekomendasi atau usulkan industri'],
            ['label' => 'Wawancara', 'state' => 'pending', 'hint' => 'Menunggu jadwal wawancara'],
            ['label' => 'Diterima', 'state' => 'pending', 'hint' => 'Menunggu keputusan akhir industri'],
        ];

        if (!$berkasComplete) {
            $steps[0]['state'] = 'current';
            return $steps;
        }

        $steps[0]['state'] = 'done';

        if ($status === PenempatanStatus::BELUM_MEMILIH->value) {
            $steps[1]['state'] = 'current';
            return $steps;
        }

        if (in_array($status, [
            PenempatanStatus::MENUNGGU_KONFIRMASI->value,
            PenempatanStatus::PROSES_PENGAJUAN->value,
        ], true)) {
            $steps[1]['state'] = 'current';
            return $steps;
        }

        if (in_array($status, [
            PenempatanStatus::DITOLAK_SEKOLAH->value,
            PenempatanStatus::PENGAJUAN_DITOLAK_INDUSTRI->value,
        ], true)) {
            $steps[1]['state'] = 'failed';
            $steps[1]['hint'] = 'Pengajuan perlu diperbaiki';
            return $steps;
        }

        $steps[1]['state'] = 'done';

        if ($status === PenempatanStatus::PROSES_WAWANCARA->value) {
            $steps[2]['state'] = 'current';
            return $steps;
        }

        if ($status === PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value) {
            $steps[2]['state'] = 'failed';
            $steps[2]['hint'] = 'Tidak lolos wawancara';
            return $steps;
        }

        if ($status === PenempatanStatus::DITERIMA_INDUSTRI->value) {
            $steps[2]['state'] = 'done';
            $steps[3]['state'] = 'done';
            $steps[3]['hint'] = 'Selamat, Anda diterima industri';
            return $steps;
        }

        $steps[2]['state'] = 'current';

        return $steps;
    }

    /**
     * @return array{label:string,route:string,description:string}
     */
    private function resolvePrimaryAction(?string $status): array
    {
        if ($status === PenempatanStatus::DITERIMA_INDUSTRI->value) {
            return [
                'label' => 'Presensi',
                'route' => 'siswa.absensi',
                'description' => 'Buka halaman presensi, izin, dan logbook harian.',
            ];
        }

        return [
            'label' => 'Pengajuan Berkas',
            'route' => 'siswa.berkas',
            'description' => 'Lengkapi berkas agar proses penempatan dapat dilanjutkan.',
        ];
    }

    private function resolveProfilePhotoUrl(Siswa $siswa): ?string
{
    $path = $siswa->foto_profil_link;

    if (!$path) {
        return null;
    }

    if (Str::startsWith($path, ['http://', 'https://'])) {
        return $path;
    }

    return asset('storage/' . $path);
}
}
