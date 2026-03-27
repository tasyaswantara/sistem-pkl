<?php

namespace App\Services;

use App\Models\GuruPembimbing;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\PenempatanPKL;
use App\Models\Perizinan;
use App\Models\Siswa;
use Illuminate\Pagination\LengthAwarePaginator;

class GuruPerizinanService
{
    /**
     * @param array{q?:string,status?:string,tanggal?:string,jurusan_id?:string,industri_id?:string} $filters
     */
    public function getPerizinanForGuru(GuruPembimbing $guru, array $filters = []): LengthAwarePaginator
    {
        $siswaIds = PenempatanPKL::where('guru_pembimbing_id', $guru->id)->pluck('siswa_id');

        return Perizinan::with(['siswa.user', 'siswa.jurusan', 'industri'])
            ->whereIn('siswa_id', $siswaIds)
            ->when(!empty($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(!empty($filters['tanggal']), function ($query) use ($filters) {
                $query->whereDate('tanggal_mulai', '<=', $filters['tanggal'])
                    ->whereDate('tanggal_selesai', '>=', $filters['tanggal']);
            })
            ->when(!empty($filters['jurusan_id']), function ($query) use ($filters) {
                $query->whereHas('siswa', function ($siswaQuery) use ($filters) {
                    $siswaQuery->where('jurusan_id', $filters['jurusan_id']);
                });
            })
            ->when(!empty($filters['industri_id']), function ($query) use ($filters) {
                $query->where('industri_id', $filters['industri_id']);
            })
            ->when(!empty($filters['q']), function ($query) use ($filters) {
                $query->where(function ($nestedQuery) use ($filters) {
                    $nestedQuery->whereHas('siswa.user', function ($userQuery) use ($filters) {
                        $userQuery->where('name', 'like', '%' . $filters['q'] . '%');
                    })->orWhereHas('siswa', function ($siswaQuery) use ($filters) {
                        $siswaQuery->where('nis', 'like', '%' . $filters['q'] . '%');
                    });
                });
            })
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
    }

    public function getFilterOptionsForGuru(GuruPembimbing $guru): array
    {
        $siswaIds = PenempatanPKL::where('guru_pembimbing_id', $guru->id)->pluck('siswa_id');

        return [
            'jurusanOptions' => Jurusan::whereIn(
                'id',
                Siswa::whereIn('id', $siswaIds)->pluck('jurusan_id')->unique()
            )->orderBy('nama')->get(),
            'industriOptions' => Industri::whereIn(
                'id',
                Perizinan::whereIn('siswa_id', $siswaIds)->pluck('industri_id')->filter()->unique()
            )->orderBy('nama_industri')->get(),
        ];
    }
}
