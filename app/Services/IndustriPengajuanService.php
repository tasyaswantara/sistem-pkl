<?php

namespace App\Services;

use App\Models\Industri;
use App\Models\PenempatanPKL;
use Illuminate\Support\Collection;

class IndustriPengajuanService
{
    public function getPenempatanList(Industri $industri): Collection
    {
        return PenempatanPKL::with(['siswa.user', 'siswa.jurusan'])
            ->where('industri_id', $industri->id)
            ->orderByDesc('id')
            ->get();
    }

    public function updateStatusPengajuan(Industri $industri, string $status): void
    {
        $industri->update([
            'status_pengajuan' => $status,
            'pengajuan_dijawab_at' => now(),
        ]);
    }
}
