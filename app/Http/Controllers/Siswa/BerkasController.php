<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BerkasController extends Controller
{
    public function index(Request $request)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, 'Akun siswa belum terhubung.');
        }

        return view('siswa.berkas.index', [
            'siswa' => $siswa,
        ]);
    }

    public function update(Request $request)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, 'Akun siswa belum terhubung.');
        }

        $validated = $request->validate([
            'bpjs_file' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'kartu_pelajar_file' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'cv_link' => 'nullable|url|max:2048',
            'portofolio_links' => 'nullable|array',
            'portofolio_links.*' => 'nullable|url|max:2048',
        ]);

        $portofolioLinks = collect($validated['portofolio_links'] ?? [])
            ->filter()
            ->values()
            ->all();

        $updates = [
            'cv_link' => $validated['cv_link'] ?? null,
            'portofolio_links' => $portofolioLinks ?: null,
        ];

        if ($request->hasFile('bpjs_file')) {
            if ($siswa->bpjs_link) {
                Storage::disk('public')->delete($siswa->bpjs_link);
            }
            $updates['bpjs_link'] = $request->file('bpjs_file')->store('berkas-siswa', 'public');
        }

        if ($request->hasFile('kartu_pelajar_file')) {
            if ($siswa->kartu_pelajar_link) {
                Storage::disk('public')->delete($siswa->kartu_pelajar_link);
            }
            $updates['kartu_pelajar_link'] = $request->file('kartu_pelajar_file')->store('berkas-siswa', 'public');
        }

        $siswa->update($updates);

        return back()->with('success', 'Berkas siswa berhasil diperbarui.');
    }
}
