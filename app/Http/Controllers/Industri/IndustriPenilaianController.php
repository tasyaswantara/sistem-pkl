<?php

namespace App\Http\Controllers\Industri;

use App\Http\Controllers\Controller;
use App\Models\PenempatanPKL;
use App\Services\IndustriPenilaianService;
use Illuminate\Http\Request;

class IndustriPenilaianController extends Controller
{
    public function index(Request $request, IndustriPenilaianService $service)
    {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, __('industri_penilaian.errors.akun'));
        }

        $penempatanList = $service->getPenempatanList($industri);
        $aspekList = $service->getAspekList();
        $penilaianMap = $service->getPenilaianMap($industri, $penempatanList);

        return view('industri.penilaian.industri-penilaian', [
            'penempatanList' => $penempatanList,
            'aspekList' => $aspekList,
            'penilaianMap' => $penilaianMap,
        ]);
    }

    public function store(Request $request, PenempatanPKL $penempatan, IndustriPenilaianService $service)
    {
        $industri = $request->user()->industri;
        if (!$industri || $penempatan->industri_id !== $industri->id) {
            abort(403, __('industri_penilaian.errors.akses'));
        }

        $validated = $request->validate([
            'nilai' => 'required|array',
            'nilai.*' => 'required|numeric|min:0|max:100',
        ]);

        $service->savePenilaian($industri, $penempatan, $validated);

        return back()->with('success', __('industri_penilaian.success.simpan'));
    }
}
