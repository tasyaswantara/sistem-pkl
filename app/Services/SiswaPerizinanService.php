<?php

namespace App\Services;

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
}
