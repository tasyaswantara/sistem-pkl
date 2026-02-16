<?php

namespace App\Services;

use App\Models\GuruPembimbing;
use App\Models\PenempatanPKL;
use App\Models\Perizinan;
use Illuminate\Pagination\LengthAwarePaginator;

class GuruPerizinanService
{
    public function getPerizinanForGuru(GuruPembimbing $guru): LengthAwarePaginator
    {
        $siswaIds = PenempatanPKL::where('guru_pembimbing_id', $guru->id)->pluck('siswa_id');

        return Perizinan::with(['siswa.user', 'siswa.jurusan', 'industri'])
            ->whereIn('siswa_id', $siswaIds)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
    }
}
