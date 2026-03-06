<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Services\SiswaPerizinanService;
use Illuminate\Http\Request;

class SiswaPerizinanController extends Controller
{
    public function index(Request $request, SiswaPerizinanService $service)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, __('siswa_perizinan.errors.akun'));
        }

        $perizinanList = $service->getPerizinanForSiswa($siswa);

        return view('siswa.perizinan.siswa-perizinan', [
            'perizinanList' => $perizinanList,
        ]);
    }

    public function store(Request $request, SiswaPerizinanService $service)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, __('siswa_perizinan.errors.akun'));
        }

        $penempatan = $service->getActivePenempatan($siswa);
        if (!$penempatan) {
            return back()->withErrors(['perizinan' => __('siswa_perizinan.errors.terima')])->withInput();
        }

        $validated = $request->validate([
            'jenis_izin' => 'required|string|max:100',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        if ($service->hasOverlapPerizinan($siswa, $validated['tanggal_mulai'], $validated['tanggal_selesai'])) {
            return back()->withErrors(['perizinan' => __('siswa_perizinan.errors.tumpang_tindih')])->withInput();
        }

        $service->createPerizinan($siswa, $penempatan, $request->user()->id, $validated);

        return back()->with('success', __('siswa_perizinan.success.tambah'));
    }
}
