<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Services\SiswaAbsensiService;
use Illuminate\Http\Request;

class SiswaAbsensiController extends Controller
{
    public function index(Request $request, SiswaAbsensiService $service)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, __('absensi.errors.akun'));
        }

        $data = $service->getIndexData($siswa);

        return view('siswa.absensi.siswa-absensi', [
            'penempatan' => $data['penempatan'],
            'todayAbsensi' => $data['todayAbsensi'],
            'absensiList' => $data['absensiList'],
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

        return back()->with('success', __('absensi.success.checkin'));
    }
}
