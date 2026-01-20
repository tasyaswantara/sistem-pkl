<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use App\Models\LogbookKomentar;
use App\Models\PenempatanPKL;
use Illuminate\Http\Request;

class LogbookController extends Controller
{
    public function index(Request $request)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, 'Akun guru belum terhubung.');
        }

        $siswaIds = PenempatanPKL::where('guru_pembimbing_id', $guru->id)->pluck('siswa_id');

        $logbooks = Logbook::with(['siswa.user', 'siswa.jurusan', 'industri', 'komentar.guruPembimbing.user'])
            ->whereIn('siswa_id', $siswaIds)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('guru.elogbook.index', [
            'logbooks' => $logbooks,
        ]);
    }

    public function storeKomentar(Request $request, Logbook $logbook)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, 'Akun guru belum terhubung.');
        }

        $isBimbingan = PenempatanPKL::where('guru_pembimbing_id', $guru->id)
            ->where('siswa_id', $logbook->siswa_id)
            ->exists();

        if (!$isBimbingan) {
            abort(403, 'Logbook bukan siswa bimbingan.');
        }

        $validated = $request->validate([
            'komentar' => 'required|string|max:1000',
        ]);

        LogbookKomentar::create([
            'logbook_id' => $logbook->id,
            'guru_pembimbing_id' => $guru->id,
            'komentar' => $validated['komentar'],
        ]);

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }
}
