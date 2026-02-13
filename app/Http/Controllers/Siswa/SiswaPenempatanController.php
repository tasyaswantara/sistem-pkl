<?php

namespace App\Http\Controllers\Siswa;

use App\Enums\JenisPenempatan;
use App\Enums\JadwalWawancaraStatus;
use App\Enums\PenempatanStatus;
use App\Enums\PilihanSiswa;
use App\Enums\UsulanStatus;
use App\Http\Controllers\Controller;
use App\Models\HasilRekomendasi;
use App\Models\JadwalWawancara;
use App\Models\PenempatanPKL;
use App\Models\UsulanIndustri;
use Illuminate\Http\Request;

class SiswaPenempatanController extends Controller
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
            JadwalWawancaraStatus::MENUNGGU->value => 'Menunggu',
            JadwalWawancaraStatus::DIJADWALKAN->value => 'Dijadwalkan',
            JadwalWawancaraStatus::SELESAI->value => 'Selesai',
            JadwalWawancaraStatus::DIBATALKAN->value => 'Dibatalkan',
        ];

        $statusLabels = [
            PenempatanStatus::BELUM_MEMILIH->value => 'Belum memilih',
            PenempatanStatus::MENUNGGU_KONFIRMASI->value => 'Menunggu konfirmasi',
            PenempatanStatus::DITOLAK_SEKOLAH->value => 'Ditolak sekolah',
            PenempatanStatus::PROSES_PENGAJUAN->value => 'Proses pengajuan',
            PenempatanStatus::PENGAJUAN_DITOLAK_INDUSTRI->value => 'Pengajuan ditolak industri',
            PenempatanStatus::PROSES_WAWANCARA->value => 'Proses wawancara',
            PenempatanStatus::DITERIMA_INDUSTRI->value => 'Diterima industri',
            PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value => 'Tidak lolos industri',
        ];

        return view('siswa.penempatan.siswa-penempatan', [
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

        if (!$this->berkasLengkap($siswa)) {
            return back()->withErrors(['berkas' => 'Lengkapi berkas siswa terlebih dahulu sebelum memilih industri.']);
        }

        $validated = $request->validate([
            'industri_id' => 'required|exists:industri,id',
        ]);

        $penempatan = PenempatanPKL::where('siswa_id', $siswa->id)->first();
        if ($penempatan && $penempatan->jenis_penempatan === JenisPenempatan::LANGSUNG->value) {
            return back()->withErrors(['pilihan' => 'Penempatan langsung sudah ditetapkan oleh admin.']);
        }
        if ($penempatan && !in_array($penempatan->status, [
            PenempatanStatus::BELUM_MEMILIH->value,
            PenempatanStatus::DITOLAK_SEKOLAH->value,
            PenempatanStatus::PENGAJUAN_DITOLAK_INDUSTRI->value,
            PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value,
        ], true)) {
            return back()->withErrors(['pilihan' => 'Pilihan tidak dapat diubah pada status saat ini.']);
        }

        $existingPenempatan = PenempatanPKL::where('siswa_id', $siswa->id)->first();
        $oldStatus = $existingPenempatan?->status;

        $penempatan = PenempatanPKL::updateOrCreate(
            ['siswa_id' => $siswa->id],
            [
                'industri_id' => $validated['industri_id'],
                'usulan_industri_id' => null,
                'pilihan_siswa' => PilihanSiswa::REKOMENDASI->value,
                'status' => PenempatanStatus::MENUNGGU_KONFIRMASI->value,
            ]
        );

        if ($oldStatus !== null) {
            $this->handlePenempatanStatusChange($penempatan, $oldStatus);
        }

        return back()->with('success', 'Pilihan rekomendasi berhasil dikirim.');
    }

    public function usulkanIndustri(Request $request)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, 'Akun siswa belum terhubung.');
        }

        if (!$this->berkasLengkap($siswa)) {
            return back()->withErrors(['berkas' => 'Lengkapi berkas siswa terlebih dahulu sebelum mengajukan industri.']);
        }

        $penempatan = PenempatanPKL::where('siswa_id', $siswa->id)->first();
        if ($penempatan && $penempatan->jenis_penempatan === JenisPenempatan::LANGSUNG->value) {
            return back()->withErrors(['pilihan' => 'Penempatan langsung sudah ditetapkan oleh admin.']);
        }
        if ($penempatan && !in_array($penempatan->status, [
            PenempatanStatus::BELUM_MEMILIH->value,
            PenempatanStatus::DITOLAK_SEKOLAH->value,
            PenempatanStatus::PENGAJUAN_DITOLAK_INDUSTRI->value,
            PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value,
        ], true)) {
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
            'status' => UsulanStatus::MENUNGGU->value,
        ]);

        $existingPenempatan = PenempatanPKL::where('siswa_id', $siswa->id)->first();
        $oldStatus = $existingPenempatan?->status;

        $penempatan = PenempatanPKL::updateOrCreate(
            ['siswa_id' => $siswa->id],
            [
                'industri_id' => null,
                'usulan_industri_id' => $usulan->id,
                'pilihan_siswa' => PilihanSiswa::USULAN_LAIN->value,
                'status' => PenempatanStatus::MENUNGGU_KONFIRMASI->value,
            ]
        );

        if ($oldStatus !== null) {
            $this->handlePenempatanStatusChange($penempatan, $oldStatus);
        }

        return back()->with('success', 'Usulan industri berhasil dikirim.');
    }

    private function berkasLengkap($siswa): bool
    {
        $hasBpjs = !empty($siswa->bpjs_link);
        $hasKartu = !empty($siswa->kartu_pelajar_link);
        $hasCv = !empty($siswa->cv_link);
        $portofolio = is_array($siswa->portofolio_links) ? $siswa->portofolio_links : [];
        $hasPortofolio = count($portofolio) > 0;

        return $hasBpjs && $hasKartu && $hasCv && $hasPortofolio;
    }
}
