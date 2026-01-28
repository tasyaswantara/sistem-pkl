<?php

namespace App\Http\Controllers\Industri;

use App\Http\Controllers\Controller;
use App\Models\Perizinan;
use Illuminate\Http\Request;

class IndustriPerizinanController extends Controller
{
    public function index(Request $request)
    {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, 'Akun industri belum terhubung.');
        }

        $perizinanList = Perizinan::with('siswa.user')
            ->where('industri_id', $industri->id)
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('industri.perizinan.index', [
            'perizinanList' => $perizinanList,
        ]);
    }

    public function update(Request $request, Perizinan $perizinan)
    {
        $industri = $request->user()->industri;
        if (!$industri || $perizinan->industri_id !== $industri->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $validated = $request->validate([
            'status' => 'required|in:menunggu,disetujui,ditolak',
            'catatan_industri' => 'nullable|string|max:1000',
        ]);

        $perizinan->update([
            'status' => $validated['status'],
            'catatan_industri' => $validated['catatan_industri'],
        ]);

        return back()->with('success', 'Perizinan berhasil diperbarui.');
    }
}
