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

        $penempatan = PenempatanPKL::with(['industri', 'usulanIndustri', 'guruPembimbing.user', 'guruPembimbing.jurusan'])
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
            'belum_memilih' => 'Belum memilih',
            'menunggu_konfirmasi' => 'Menunggu konfirmasi',
            'ditolak_sekolah' => 'Ditolak sekolah',
            'proses_pengajuan' => 'Proses pengajuan',
            'pengajuan_ditolak_industri' => 'Pengajuan ditolak industri',
            'proses_wawancara' => 'Proses wawancara',
            'diterima_industri' => 'Diterima industri',
            'tidak_lolos_industri' => 'Tidak lolos industri',
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
        if ($penempatan && $penempatan->jenis_penempatan === 'langsung') {
            return back()->withErrors(['pilihan' => 'Penempatan langsung sudah ditetapkan oleh admin.']);
        }
        if ($penempatan && !in_array($penempatan->status, ['belum_memilih', 'ditolak_sekolah', 'pengajuan_ditolak_industri', 'tidak_lolos_industri'], true)) {
            return back()->withErrors(['pilihan' => 'Pilihan tidak dapat diubah pada status saat ini.']);
        }

        PenempatanPKL::updateOrCreate(
            ['siswa_id' => $siswa->id],
            [
                'industri_id' => $validated['industri_id'],
                'usulan_industri_id' => null,
                'pilihan_siswa' => 'rekomendasi',
                'status' => 'menunggu_konfirmasi',
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
        if ($penempatan && $penempatan->jenis_penempatan === 'langsung') {
            return back()->withErrors(['pilihan' => 'Penempatan langsung sudah ditetapkan oleh admin.']);
        }
        if ($penempatan && !in_array($penempatan->status, ['belum_memilih', 'ditolak_sekolah', 'pengajuan_ditolak_industri', 'tidak_lolos_industri'], true)) {
            return back()->withErrors(['pilihan' => 'Pilihan tidak dapat diubah pada status saat ini.']);
        }

        $validated = $request->validate([
            'nama_industri' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'kapasitas' => 'required|integer|min:1',
            'alamat' => 'required|string',
            'kontak' => 'nullable|string|max:100',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $usulan = UsulanIndustri::create([
            'siswa_id' => $siswa->id,
            'jurusan_id' => $siswa->jurusan_id,
            'nama_industri' => $validated['nama_industri'],
            'email' => $validated['email'],
            'kapasitas' => $validated['kapasitas'],
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
                'status' => 'menunggu_konfirmasi',
            ]
        );

        return back()->with('success', 'Usulan industri berhasil dikirim.');
    }
}
