<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\HasilRekomendasi;
use App\Models\JadwalWawancara;
use App\Models\PenempatanPKL;
use App\Models\UsulanIndustri;
use Illuminate\Http\Request;

class PenempatanController extends Controller
{
    public function index(Request $request)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, 'Akun siswa belum terhubung.');
        }

        $penempatan = PenempatanPKL::with(['industri', 'usulanIndustri'])
            ->where('siswa_id', $siswa->id)
            ->first();

        $latestRunId = HasilRekomendasi::where('siswa_id', $siswa->id)->max('saw_run_id');

        $rekomendasi = collect();
        if ($latestRunId) {
            $rekomendasi = HasilRekomendasi::with('industri')
                ->where('saw_run_id', $latestRunId)
                ->where('siswa_id', $siswa->id)
                ->orderBy('peringkat')
                ->get();
        }

        $jadwalWawancara = JadwalWawancara::with('industri')
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $statusWawancaraLabels = [
            'menunggu' => 'Menunggu',
            'dijadwalkan' => 'Dijadwalkan',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
        ];

        $statusLabels = [
            'belum_diproses' => 'Belum Diproses',
            'proses_pengajuan' => 'Proses Pengajuan',
            'diterima_industri' => 'Diterima Industri',
            'ditolak_industri' => 'Ditolak Industri',
        ];

        return view('siswa.penempatan.index', [
            'siswa' => $siswa,
            'penempatan' => $penempatan,
            'rekomendasi' => $rekomendasi,
            'jadwalWawancara' => $jadwalWawancara,
            'statusWawancaraLabels' => $statusWawancaraLabels,
            'statusLabels' => $statusLabels,
        ]);
    }

    public function pilihRekomendasi(Request $request)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, 'Akun siswa belum terhubung.');
        }

        $validated = $request->validate([
            'industri_id' => 'required|exists:industri,id',
        ]);

        $penempatan = PenempatanPKL::where('siswa_id', $siswa->id)->first();
        if ($penempatan && $penempatan->status === 'diterima_industri') {
            return back()->withErrors(['pilihan' => 'Penempatan sudah diterima, tidak dapat diubah.']);
        }

        PenempatanPKL::updateOrCreate(
            ['siswa_id' => $siswa->id],
            [
                'industri_id' => $validated['industri_id'],
                'usulan_industri_id' => null,
                'pilihan_siswa' => 'rekomendasi',
                'status' => 'proses_pengajuan',
            ]
        );

        return back()->with('success', 'Pilihan rekomendasi berhasil dikirim.');
    }

    public function usulkanIndustri(Request $request)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, 'Akun siswa belum terhubung.');
        }

        $penempatan = PenempatanPKL::where('siswa_id', $siswa->id)->first();
        if ($penempatan && $penempatan->status === 'diterima_industri') {
            return back()->withErrors(['pilihan' => 'Penempatan sudah diterima, tidak dapat diubah.']);
        }

        $validated = $request->validate([
            'nama_industri' => 'required|string|max:255',
            'email' => 'required|email',
            'alamat' => 'required|string',
            'kontak' => 'nullable|string|max:100',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $usulan = UsulanIndustri::create([
            'siswa_id' => $siswa->id,
            'jurusan_id' => $siswa->jurusan_id,
            'nama_industri' => $validated['nama_industri'],
            'email' => $validated['email'],
            'alamat' => $validated['alamat'],
            'kontak' => $validated['kontak'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
            'status' => 'menunggu',
        ]);

        PenempatanPKL::updateOrCreate(
            ['siswa_id' => $siswa->id],
            [
                'industri_id' => null,
                'usulan_industri_id' => $usulan->id,
                'pilihan_siswa' => 'usulan_lain',
                'status' => 'proses_pengajuan',
            ]
        );

        return back()->with('success', 'Usulan industri berhasil dikirim.');
    }
}
