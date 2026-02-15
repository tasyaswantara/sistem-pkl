<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Logbook;
use App\Models\PenempatanPKL;
use App\Services\SiswaLogbookService;
use Illuminate\Http\Request;

class SiswaLogbookController extends Controller
{
    public function index(Request $request, SiswaLogbookService $service)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, 'Akun siswa belum terhubung.');
        }

        $penempatan = PenempatanPKL::with('industri')
            ->where('siswa_id', $siswa->id)
            ->first();

        $logbooks = $service->getLogbooksForSiswa($siswa);

        return view('siswa.elogbook.siswa-elogbook', [
            'siswa' => $siswa,
            'penempatan' => $penempatan,
            'logbooks' => $logbooks,
        ]);
    }

    public function store(Request $request, SiswaLogbookService $service)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, 'Akun siswa belum terhubung.');
        }

        $penempatan = $service->getActivePenempatan($siswa);

        if (!$penempatan) {
            return back()->withErrors(['logbook' => 'Logbook hanya bisa diisi setelah status penempatan diterima industri.']);
        }

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'aktivitas' => 'required|string|max:2000',
        ]);

        $service->createLogbook($siswa, $penempatan, $validated);

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
