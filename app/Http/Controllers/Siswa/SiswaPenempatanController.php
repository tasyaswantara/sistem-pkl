<?php

namespace App\Http\Controllers\Siswa;

use App\Enums\PenempatanStatus;
use App\Http\Controllers\Controller;
use App\Models\HasilRekomendasi;
use App\Models\JadwalWawancara;
use App\Models\PenempatanPKL;
use App\Services\SiswaPenempatanService;
use Illuminate\Http\Request;

class SiswaPenempatanController extends Controller
{
    public function index(Request $request)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, __('siswa_penempatan.errors.akun'));
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
            'statusLabels' => $statusLabels,
        ]);
    }

    public function pilihRekomendasi(Request $request, SiswaPenempatanService $service)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, __('siswa_penempatan.errors.akun'));
        }

        if (!$service->berkasLengkap($siswa)) {
            return back()->withErrors(['berkas' => __('siswa_penempatan.errors.berkas_pilih')]);
        }

        $validated = $request->validate([
            'industri_id' => 'required|exists:industri,id',
        ]);

        $penempatan = PenempatanPKL::where('siswa_id', $siswa->id)->first();
        if (!$service->canUpdatePilihan($penempatan)) {
            return back()->withErrors(['pilihan' => __('siswa_penempatan.errors.pilihan')]);
        }

        $oldStatus = $penempatan?->status;
        $penempatan = $service->handlePilihanRekomendasi($siswa, (int) $validated['industri_id']);

        if ($oldStatus !== null) {
            $this->handlePenempatanStatusChange($penempatan, $oldStatus);
        }

        return back()->with('success', __('siswa_penempatan.success.rekom'));
    }

    public function usulkanIndustri(Request $request, SiswaPenempatanService $service)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, __('siswa_penempatan.errors.akun'));
        }

        if (!$service->berkasLengkap($siswa)) {
            return back()->withErrors(['berkas' => __('siswa_penempatan.errors.berkas_usul')]);
        }

        $validated = $request->validate([
            'nama_industri' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'kapasitas' => 'required|integer|min:1',
            'alamat' => 'required|string',
            'kontak' => 'nullable|string|max:100',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $penempatan = PenempatanPKL::where('siswa_id', $siswa->id)->first();
        if (!$service->canUpdatePilihan($penempatan)) {
            return back()->withErrors(['pilihan' => __('siswa_penempatan.errors.pilihan')]);
        }

        $oldStatus = $penempatan?->status;
        [, $penempatan] = $service->handleUsulanIndustri($siswa, $validated);

        if ($oldStatus !== null) {
            $this->handlePenempatanStatusChange($penempatan, $oldStatus);
        }

        return back()->with('success', __('siswa_penempatan.success.usul'));
    }
}
