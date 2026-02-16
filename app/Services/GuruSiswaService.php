<?php

namespace App\Services;

use App\Models\GuruPembimbing;
use App\Models\Jurusan;
use App\Models\PenempatanPKL;
use App\Models\Siswa;
use Illuminate\Support\Collection;

class GuruSiswaService
{
    /**
     * @param array{q?:string,jurusan_id?:string,kelas?:string,tahun_ajaran?:string} $filters
     * @return \Illuminate\Support\Collection<int, PenempatanPKL>
     */
    public function getPenempatanList(GuruPembimbing $guru, array $filters): Collection
    {
        $penempatanQuery = PenempatanPKL::with(['siswa.user', 'siswa.jurusan', 'industri'])
            ->where('guru_pembimbing_id', $guru->id)
            ->when($filters['jurusan_id'] ?? null, function ($query, $jurusanId) {
                $query->whereHas('siswa', function ($sq) use ($jurusanId) {
                    $sq->where('jurusan_id', $jurusanId);
                });
            })
            ->when($filters['kelas'] ?? null, function ($query, $kelas) {
                $query->whereHas('siswa', function ($sq) use ($kelas) {
                    $sq->where('kelas', $kelas);
                });
            })
            ->when($filters['tahun_ajaran'] ?? null, function ($query, $tahunAjaran) {
                $query->whereHas('siswa', function ($sq) use ($tahunAjaran) {
                    $sq->where('tahun_ajaran', $tahunAjaran);
                });
            })
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('siswa.user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%' . $search . '%');
                    })->orWhereHas('siswa', function ($sq) use ($search) {
                        $sq->where('nis', 'like', '%' . $search . '%');
                    });
                });
            });

        return $penempatanQuery
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return array{jurusanOptions:\Illuminate\Support\Collection,kelasOptions:\Illuminate\Support\Collection,tahunAjaranOptions:\Illuminate\Support\Collection}
     */
    public function getFilterOptionsForGuru(GuruPembimbing $guru): array
    {
        $siswaIds = PenempatanPKL::where('guru_pembimbing_id', $guru->id)->pluck('siswa_id');

        $jurusanOptions = Jurusan::whereIn(
            'id',
            Siswa::whereIn('id', $siswaIds)->pluck('jurusan_id')->unique()
        )->orderBy('nama')->get();

        $kelasOptions = Siswa::whereIn('id', $siswaIds)
            ->select('kelas')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas');

        $tahunAjaranOptions = Siswa::whereIn('id', $siswaIds)
            ->select('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');

        return [
            'jurusanOptions' => $jurusanOptions,
            'kelasOptions' => $kelasOptions,
            'tahunAjaranOptions' => $tahunAjaranOptions,
        ];
    }
}
