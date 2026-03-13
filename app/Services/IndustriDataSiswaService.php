<?php

namespace App\Services;

use App\Enums\LaporanStatus;
use App\Enums\PenempatanStatus;
use App\Models\Industri;
use App\Models\JadwalWawancara;
use App\Models\PenempatanPKL;
use Illuminate\Support\Collection;

class IndustriDataSiswaService
{
    public function getPenempatanList(Industri $industri, ?string $statusFilter): Collection
    {
        $penempatanQuery = PenempatanPKL::with(['siswa.user', 'siswa.jurusan'])
            ->where('industri_id', $industri->id);

        if ($statusFilter && $statusFilter !== 'all') {
            $penempatanQuery->where('status', $statusFilter);
        }

        return $penempatanQuery
            ->orderByDesc('id')
            ->get();
    }

    public function getJadwalMap(Industri $industri): Collection
    {
        return JadwalWawancara::where('industri_id', $industri->id)
            ->get()
            ->keyBy('siswa_id');
    }

    public function updatePenempatanStatus(PenempatanPKL $penempatan, string $status): string
    {
        $oldStatus = $penempatan->status;
        $penempatan->update([
            'status' => $status,
        ]);

        return $oldStatus;
    }

    /**
     * @param array{tanggal:string,waktu?:string,lokasi?:string,catatan?:string} $data
     */
    public function saveJadwal(Industri $industri, PenempatanPKL $penempatan, array $data): string
    {
        JadwalWawancara::updateOrCreate(
            [
                'siswa_id' => $penempatan->siswa_id,
                'industri_id' => $industri->id,
            ],
            [
                'tanggal' => $data['tanggal'],
                'waktu' => $data['waktu'] ?? null,
                'lokasi' => $data['lokasi'] ?? null,
                'catatan' => $data['catatan'] ?? null,
            ]
        );

        $oldStatus = $penempatan->status;
        $penempatan->update([
            'status' => PenempatanStatus::PROSES_WAWANCARA->value,
        ]);

        return $oldStatus;
    }

    /**
     * @param array{laporan:string} $data
     */
    public function saveLaporan(PenempatanPKL $penempatan, array $data): void
    {
        $penempatan->update([
            'laporan_industri' => $data['laporan'],
            'laporan_status' => LaporanStatus::MENUNGGU->value,
            'laporan_at' => now(),
        ]);
    }
}
