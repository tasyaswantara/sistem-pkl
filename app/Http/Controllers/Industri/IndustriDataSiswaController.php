<?php

namespace App\Http\Controllers\Industri;

use App\Enums\JadwalWawancaraStatus;
use App\Enums\LaporanStatus;
use App\Enums\PenempatanStatus;
use App\Http\Controllers\Controller;
use App\Models\JadwalWawancara;
use App\Models\PenempatanPKL;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IndustriDataSiswaController extends Controller
{
    public function index(Request $request)
    {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, 'Akun industri belum terhubung.');
        }

        $statusFilter = $request->input('status', 'all');

        $penempatanQuery = PenempatanPKL::with(['siswa.user', 'siswa.jurusan'])
            ->where('industri_id', $industri->id);

        if ($statusFilter !== 'all') {
            $penempatanQuery->where('status', $statusFilter);
        }

        $penempatanList = $penempatanQuery
            ->orderByDesc('id')
            ->get();

        $jadwalMap = JadwalWawancara::where('industri_id', $industri->id)
            ->get()
            ->keyBy('siswa_id');

        $statusLabels = [
            'all' => 'Semua',
            PenempatanStatus::PROSES_PENGAJUAN->value => 'Proses pengajuan',
            PenempatanStatus::PENGAJUAN_DITOLAK_INDUSTRI->value => 'Pengajuan ditolak industri',
            PenempatanStatus::PROSES_WAWANCARA->value => 'Proses wawancara',
            PenempatanStatus::DITERIMA_INDUSTRI->value => 'Diterima industri',
            PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value => 'Tidak lolos industri',
        ];

        return view('industri.siswa.industri-siswa', [
            'penempatanList' => $penempatanList,
            'jadwalMap' => $jadwalMap,
            'statusLabels' => $statusLabels,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function setStatus(Request $request, PenempatanPKL $penempatan)
    {
        $industri = $request->user()->industri;
        if (!$industri || $penempatan->industri_id !== $industri->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    PenempatanStatus::DITERIMA_INDUSTRI->value,
                    PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value,
                ]),
            ],
        ]);

        $oldStatus = $penempatan->status;
        $penempatan->update([
            'status' => $validated['status'],
        ]);
        $this->handlePenempatanStatusChange($penempatan, $oldStatus);

        return back()->with('success', 'Status penerimaan berhasil diperbarui.');
    }

    public function storeJadwal(Request $request, PenempatanPKL $penempatan)
    {
        $industri = $request->user()->industri;
        if (!$industri || $penempatan->industri_id !== $industri->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'waktu' => 'nullable|date_format:H:i',
            'lokasi' => 'nullable|string|max:255',
            'catatan' => 'nullable|string|max:500',
            'status' => [
                'required',
                Rule::in(array_map(
                    static fn (JadwalWawancaraStatus $status) => $status->value,
                    JadwalWawancaraStatus::cases()
                )),
            ],
        ]);

        JadwalWawancara::updateOrCreate(
            [
                'siswa_id' => $penempatan->siswa_id,
                'industri_id' => $industri->id,
            ],
            [
                'tanggal' => $validated['tanggal'],
                'waktu' => $validated['waktu'] ?? null,
                'lokasi' => $validated['lokasi'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
                'status' => $validated['status'],
            ]
        );

        $oldStatus = $penempatan->status;
        $penempatan->update([
            'status' => PenempatanStatus::PROSES_WAWANCARA->value,
        ]);
        $this->handlePenempatanStatusChange($penempatan, $oldStatus);

        return back()->with('success', 'Jadwal wawancara berhasil disimpan.');
    }

    public function storeLaporan(Request $request, PenempatanPKL $penempatan)
    {
        $industri = $request->user()->industri;
        if (!$industri || $penempatan->industri_id !== $industri->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $validated = $request->validate([
            'laporan' => 'required|string|max:1000',
        ]);

        $penempatan->update([
            'laporan_industri' => $validated['laporan'],
            'laporan_status' => LaporanStatus::MENUNGGU->value,
            'laporan_at' => now(),
        ]);

        return back()->with('success', 'Laporan berhasil dikirim ke admin.');
    }
}
