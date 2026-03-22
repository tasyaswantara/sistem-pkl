<?php

namespace App\Services;

use App\Models\AspekPenilaian;
use App\Models\GuruPembimbing;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\PenempatanPKL;
use App\Models\Penilaian;
use App\Models\Siswa;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GuruPenilaianService
{
    public function getAspekList(): Collection
    {
        return AspekPenilaian::orderBy('nama_aspek')->get();
    }

    /**
     * @param array{q?:string,jurusan_id?:string,industri_id?:string,tahun_ajaran?:string,tanggal?:string} $filters
     */
    public function getPenilaianForGuru(GuruPembimbing $guru, array $filters): LengthAwarePaginator
    {
        $siswaIds = $this->getBimbinganSiswaIds($guru);
        $latestPenilaianIds = Penilaian::selectRaw('MAX(id) as id')
            ->whereIn('siswa_id', $siswaIds)
            ->groupBy('siswa_id', 'industri_id');

        return Penilaian::with(['siswa.user', 'siswa.jurusan', 'industri', 'detailPenilaian.aspekPenilaian'])
            ->whereIn('id', $latestPenilaianIds)
            ->when($filters['jurusan_id'] ?? null, function ($query, $jurusanId) {
                $query->whereHas('siswa', function ($siswaQuery) use ($jurusanId) {
                    $siswaQuery->where('jurusan_id', $jurusanId);
                });
            })
            ->when($filters['tahun_ajaran'] ?? null, function ($query, $tahunAjaran) {
                $query->whereHas('siswa', function ($siswaQuery) use ($tahunAjaran) {
                    $siswaQuery->where('tahun_ajaran', $tahunAjaran);
                });
            })
            ->when($filters['industri_id'] ?? null, function ($query, $industriId) {
                $query->where('industri_id', $industriId);
            })
            ->when($filters['tanggal'] ?? null, function ($query, $tanggal) {
                $query->whereDate('tanggal_penilaian', $tanggal);
            })
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->whereHas('siswa.user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%');
                    })->orWhereHas('siswa', function ($siswaQuery) use ($search) {
                        $siswaQuery->where('nis', 'like', '%' . $search . '%');
                    });
                });
            })
            ->orderByDesc('tanggal_penilaian')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * @return array{jurusanOptions:Collection,tahunAjaranOptions:Collection,industriOptions:Collection}
     */
    public function getFilterOptionsForGuru(GuruPembimbing $guru): array
    {
        $siswaIds = $this->getBimbinganSiswaIds($guru);

        $jurusanOptions = Jurusan::whereIn(
            'id',
            Siswa::whereIn('id', $siswaIds)->pluck('jurusan_id')->unique()
        )->orderBy('nama')->get();

        $tahunAjaranOptions = Siswa::whereIn('id', $siswaIds)
            ->select('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');

        $industriOptions = Industri::whereIn(
            'id',
            Penilaian::whereIn('siswa_id', $siswaIds)->pluck('industri_id')->unique()
        )->orderBy('nama_industri')->get();

        return [
            'jurusanOptions' => $jurusanOptions,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'industriOptions' => $industriOptions,
        ];
    }

    private function getBimbinganSiswaIds(GuruPembimbing $guru): Collection
    {
        return PenempatanPKL::where('guru_pembimbing_id', $guru->id)->pluck('siswa_id');
    }
}
