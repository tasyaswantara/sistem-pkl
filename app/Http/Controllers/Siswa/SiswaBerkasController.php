<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiswaBerkasController extends Controller
{
    public function index(Request $request)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, __('siswa_berkas.errors.akun'));
        }

        return view('siswa.berkas.siswa-berkas', [
            'siswa' => $siswa,
        ]);
    }

    public function update(Request $request)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, __('siswa_berkas.errors.akun'));
        }

        $validated = $request->validate([
            'bpjs_file' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'kartu_pelajar_file' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'foto_profil_file' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
            'cv_link' => 'nullable|url|max:2048',
            'portofolio_links' => 'nullable|array|min:1',
            'portofolio_links.*' => 'nullable|url|max:2048',
        ]);

        $updates = [];

        if ($request->filled('cv_link')) {
            $updates['cv_link'] = $validated['cv_link'];
        }

        if ($request->has('portofolio_links')) {
            $portofolioLinks = collect($validated['portofolio_links'] ?? [])
                ->filter(fn($link) => filled($link))
                ->values()
                ->all();

            if (count($portofolioLinks) > 0) {
                $updates['portofolio_links'] = $portofolioLinks;
            }
        }

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

        if ($request->hasFile('foto_profil_file')) {
            if ($siswa->foto_profil_link) {
                Storage::disk('public')->delete($siswa->foto_profil_link);
            }
            $updates['foto_profil_link'] = $request->file('foto_profil_file')->store('berkas-siswa', 'public');
        }

        if (empty($updates)) {
            return back()->withErrors([
                'berkas' => __('siswa_berkas.errors.porto'),
            ])->withInput();
        }

        $siswa->update($updates);

        return back()->with('success', __('siswa_berkas.success.ubah'));
    }
}
