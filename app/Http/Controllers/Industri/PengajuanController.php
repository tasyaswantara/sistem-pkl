<?php

namespace App\Http\Controllers\Industri;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PengajuanController extends Controller
{
    public function index(Request $request)
    {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, 'Akun industri belum terhubung.');
        }

        return view('industri.pengajuan.index', [
            'industri' => $industri,
        ]);
    }

    public function konfirmasi(Request $request)
    {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, 'Akun industri belum terhubung.');
        }

        $validated = $request->validate([
            'status_pengajuan' => 'required|in:disetujui,ditolak',
        ]);

        $industri->update([
            'status_pengajuan' => $validated['status_pengajuan'],
            'pengajuan_dijawab_at' => now(),
        ]);

        return back()->with('success', 'Status pengajuan berhasil diperbarui.');
    }
}
