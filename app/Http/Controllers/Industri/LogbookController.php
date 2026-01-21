<?php

namespace App\Http\Controllers\Industri;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use Illuminate\Http\Request;

class LogbookController extends Controller
{
    public function index(Request $request)
    {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, 'Akun industri belum terhubung.');
        }

        $logbooks = Logbook::with(['siswa.user', 'siswa.jurusan'])
            ->where('industri_id', $industri->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('industri.elogbook.index', [
            'logbooks' => $logbooks,
        ]);
    }

    public function update(Request $request, Logbook $logbook)
    {
        $industri = $request->user()->industri;
        if (!$industri || $logbook->industri_id !== $industri->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $validated = $request->validate([
            'status_validasi' => 'required|in:pending,disetujui,ditolak',
            'catatan_industri' => 'nullable|string|max:1000',
        ]);

        $logbook->update([
            'status_validasi' => $validated['status_validasi'],
            'catatan_industri' => $validated['catatan_industri'],
            'validated_at' => $validated['status_validasi'] === 'pending' ? null : now(),
        ]);

        return back()->with('success', 'Logbook berhasil divalidasi.');
    }
}
