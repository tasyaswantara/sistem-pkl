<?php

namespace App\Http\Controllers\Siswa;

use App\Enums\LogbookStatus;
use App\Enums\PerizinanStatus;
use App\Http\Controllers\Controller;
use App\Services\SiswaAbsensiService;
use App\Services\SiswaPresensiService;
use Illuminate\Http\Request;

class SiswaAbsensiController extends Controller
{
    public function index(Request $request, SiswaPresensiService $service)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, __('absensi.errors.akun'));
        }

        $data = $service->getPageData($siswa);

        return view('siswa.absensi.siswa-absensi', [
            'penempatan' => $data['penempatan'],
            'todayAbsensi' => $data['todayAbsensi'],
            'canCheckIn' => $data['canCheckIn'],
            'canRequestIzin' => $data['canRequestIzin'],
            'weekDays' => $data['weekDays'],
            'weekCounts' => $data['weekCounts'],
            'logbooks' => $data['logbooks'],
            'logbookTotal' => $data['logbookTotal'],
            'perizinanLatest' => $data['perizinanLatest'],
            'logbookStatusLabels' => [
                LogbookStatus::PENDING->value => 'Pending',
                LogbookStatus::DISETUJUI->value => 'Disetujui',
                LogbookStatus::DITOLAK->value => 'Ditolak',
            ],
            'perizinanStatusLabels' => [
                PerizinanStatus::MENUNGGU->value => 'Menunggu',
                PerizinanStatus::DISETUJUI->value => 'Disetujui',
                PerizinanStatus::DITOLAK->value => 'Ditolak',
            ],
        ]);
    }

    public function store(Request $request, SiswaAbsensiService $service)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, __('absensi.errors.akun'));
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy_m' => 'nullable|numeric|min:0|max:1000',
            'catatan' => 'nullable|string|max:500',
        ]);

        $result = $service->createCheckIn($siswa, $validated);
        if (!$result['ok']) {
            return back()->withErrors([
                'absensi' => __($result['error_key'] ?? 'absensi.errors.lokasi'),
            ])->withInput();
        }

        return back()
            ->with('success', __('absensi.success.checkin'))
            ->with('checkin_at', $result['absensi']?->check_in_at?->format('H:i:s'));
    }
}
