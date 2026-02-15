<?php

namespace App\Services;

use App\Enums\LogbookStatus;
use App\Enums\PenempatanStatus;
use App\Models\Logbook;
use App\Models\PenempatanPKL;
use App\Models\Siswa;
use Illuminate\Pagination\LengthAwarePaginator;

class SiswaLogbookService
{
    public function getLogbooksForSiswa(Siswa $siswa): LengthAwarePaginator
    {
        return Logbook::with(['industri', 'komentar.guruPembimbing.user'])
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
    }

    public function getActivePenempatan(Siswa $siswa): ?PenempatanPKL
    {
        return PenempatanPKL::where('siswa_id', $siswa->id)
            ->where('status', PenempatanStatus::DITERIMA_INDUSTRI->value)
            ->whereNotNull('industri_id')
            ->first();
    }

    /**
     * @param array{tanggal:string,aktivitas:string} $data
     */
    public function createLogbook(Siswa $siswa, PenempatanPKL $penempatan, array $data): Logbook
    {
        return Logbook::create([
            'siswa_id' => $siswa->id,
            'industri_id' => $penempatan->industri_id,
            'tanggal' => $data['tanggal'],
            'aktivitas' => $data['aktivitas'],
            'status_validasi' => LogbookStatus::PENDING->value,
        ]);
    }
}
