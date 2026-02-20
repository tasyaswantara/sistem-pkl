<?php

namespace App\Http\Controllers\Industri;

use App\Http\Controllers\Controller;
use App\Services\IndustriPengajuanService;
use Illuminate\Http\Request;

class IndustriPengajuanController extends Controller
{
    public function index(Request $request, IndustriPengajuanService $service)
    {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, __('industri_pengajuan.errors.akun'));
        }

        $penempatanList = $service->getPenempatanList($industri);

        return view('industri.pengajuan.industri-pengajuan', [
            'industri' => $industri,
            'penempatanList' => $penempatanList,
        ]);
    }

    public function konfirmasi(Request $request, IndustriPengajuanService $service)
    {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, __('industri_pengajuan.errors.akun'));
        }

        $validated = $request->validate([
            'status_pengajuan' => 'required|in:disetujui,ditolak',
        ]);

        $service->updateStatusPengajuan($industri, $validated['status_pengajuan']);

        return back()->with('success', __('industri_pengajuan.success.status'));
    }
}
