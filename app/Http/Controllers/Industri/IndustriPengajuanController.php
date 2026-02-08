<?php

namespace App\Http\Controllers\Industri;

use App\Http\Controllers\Controller;
use App\Models\PenempatanPKL;
use Illuminate\Http\Request;

class IndustriPengajuanController extends Controller
{
    public function index(Request $request)
    {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, 'Akun industri belum terhubung.');
        }

        $penempatanList = PenempatanPKL::with(['siswa.user', 'siswa.jurusan'])
            ->where('industri_id', $industri->id)
            ->orderByDesc('id')
            ->get();

        return view('industri.pengajuan.industri-pengajuan', [
            'industri' => $industri,
            'penempatanList' => $penempatanList,
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
