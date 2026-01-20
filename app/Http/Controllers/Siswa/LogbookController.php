<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use App\Models\PenempatanPKL;
use Illuminate\Http\Request;

class LogbookController extends Controller
{
    public function index(Request $request)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, 'Akun siswa belum terhubung.');
        }

        $penempatan = PenempatanPKL::with('industri')
            ->where('siswa_id', $siswa->id)
            ->first();

        $logbooks = Logbook::with(['industri', 'komentar.guruPembimbing.user'])
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('siswa.elogbook.index', [
            'siswa' => $siswa,
            'penempatan' => $penempatan,
            'logbooks' => $logbooks,
        ]);
    }

    public function store(Request $request)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, 'Akun siswa belum terhubung.');
        }

        $penempatan = PenempatanPKL::where('siswa_id', $siswa->id)
            ->whereNotNull('industri_id')
            ->first();

        if (!$penempatan) {
            return back()->withErrors(['logbook' => 'Belum ada industri yang ditetapkan untuk logbook.']);
        }

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'aktivitas' => 'required|string|max:2000',
        ]);

        Logbook::create([
            'siswa_id' => $siswa->id,
            'industri_id' => $penempatan->industri_id,
            'tanggal' => $validated['tanggal'],
            'aktivitas' => $validated['aktivitas'],
            'status_validasi' => 'pending',
        ]);

        return back()->with('success', 'Logbook berhasil ditambahkan.');
    }

    public function update(Request $request, Logbook $logbook)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa || $logbook->siswa_id !== $siswa->id) {
            abort(403, 'Logbook bukan milik Anda.');
        }

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'aktivitas' => 'required|string|max:2000',
        ]);

        $logbook->update([
            'tanggal' => $validated['tanggal'],
            'aktivitas' => $validated['aktivitas'],
        ]);

        return back()->with('success', 'Logbook berhasil diperbarui.');
    }

    public function destroy(Request $request, Logbook $logbook)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa || $logbook->siswa_id !== $siswa->id) {
            abort(403, 'Logbook bukan milik Anda.');
        }

        $logbook->delete();

        return back()->with('success', 'Logbook berhasil dihapus.');
    }
}
