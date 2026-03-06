<?php

namespace App\Services;

use App\Enums\PenempatanStatus;
use App\Enums\PerizinanStatus;
use App\Models\PenempatanPKL;
use App\Models\Perizinan;
use App\Models\Siswa;
use Illuminate\Pagination\LengthAwarePaginator;

class SiswaPerizinanService
{
    public function getPerizinanForSiswa(Siswa $siswa): LengthAwarePaginator
    {
        return Perizinan::with('industri')
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
    }

    public function getActivePenempatan(Siswa $siswa): ?PenempatanPKL
    {
        return PenempatanPKL::query()
            ->where('siswa_id', $siswa->id)
            ->where('status', PenempatanStatus::DITERIMA_INDUSTRI->value)
            ->whereNotNull('industri_id')
            ->latest('id')
            ->first();
    }

    public function hasOverlapPerizinan(Siswa $siswa, string $tanggalMulai, string $tanggalSelesai): bool
    {
        return Perizinan::query()
            ->where('siswa_id', $siswa->id)
            ->whereIn('status', [PerizinanStatus::MENUNGGU->value, PerizinanStatus::DISETUJUI->value])
            ->whereDate('tanggal_mulai', '<=', $tanggalSelesai)
            ->whereDate('tanggal_selesai', '>=', $tanggalMulai)
            ->exists();
    }

    /**
     * @param array{jenis_izin:string,tanggal_mulai:string,tanggal_selesai:string} $data
     */
    public function createPerizinan(Siswa $siswa, PenempatanPKL $penempatan, int $createdBy, array $data): Perizinan
    {
        return Perizinan::create([
            'siswa_id' => $siswa->id,
            'industri_id' => $penempatan->industri_id,
            'created_by' => $createdBy,
            'jenis_izin' => $data['jenis_izin'],
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
            'status' => PerizinanStatus::MENUNGGU->value,
        ]);
    }
}
