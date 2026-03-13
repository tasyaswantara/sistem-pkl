<?php

namespace App\Http\Controllers\Industri;

use App\Enums\PenempatanStatus;
use App\Http\Controllers\Controller;
use App\Models\PenempatanPKL;
use App\Services\IndustriDataSiswaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IndustriDataSiswaController extends Controller
{
    public function index(Request $request, IndustriDataSiswaService $service)
    {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, __('industri_data_siswa.errors.akun'));
        }

        $statusFilter = $request->input('status', 'all');

        $penempatanList = $service->getPenempatanList($industri, $statusFilter);
        $jadwalMap = $service->getJadwalMap($industri);

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

    public function setStatus(Request $request, PenempatanPKL $penempatan, IndustriDataSiswaService $service)
    {
        $industri = $request->user()->industri;
        if (!$industri || $penempatan->industri_id !== $industri->id) {
            abort(403, __('industri_data_siswa.errors.akses'));
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

        $oldStatus = $service->updatePenempatanStatus($penempatan, $validated['status']);
        $this->handlePenempatanStatusChange($penempatan, $oldStatus);

        return back()->with('success', __('industri_data_siswa.success.status'));
    }

    public function storeJadwal(Request $request, PenempatanPKL $penempatan, IndustriDataSiswaService $service)
    {
        $industri = $request->user()->industri;
        if (!$industri || $penempatan->industri_id !== $industri->id) {
            abort(403, __('industri_data_siswa.errors.akses'));
        }

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'waktu' => 'nullable|date_format:H:i',
            'lokasi' => 'nullable|string|max:255',
            'catatan' => 'nullable|string|max:500',
        ]);

        $oldStatus = $service->saveJadwal($industri, $penempatan, $validated);
        $this->handlePenempatanStatusChange($penempatan, $oldStatus);

        return back()->with('success', __('industri_data_siswa.success.jadwal'));
    }

    public function storeLaporan(Request $request, PenempatanPKL $penempatan, IndustriDataSiswaService $service)
    {
        $industri = $request->user()->industri;
        if (!$industri || $penempatan->industri_id !== $industri->id) {
            abort(403, __('industri_data_siswa.errors.akses'));
        }

        $validated = $request->validate([
            'laporan' => 'required|string|max:1000',
        ]);

        $service->saveLaporan($penempatan, $validated);

        return back()->with('success', __('industri_data_siswa.success.laporan'));
    }
}
