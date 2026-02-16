<?php

namespace App\Services;

use App\Models\GuruPembimbing;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\Logbook;
use App\Models\LogbookKomentar;
use App\Models\PenempatanPKL;
use App\Models\Siswa;
use Illuminate\Pagination\LengthAwarePaginator;

class GuruLogbookService
{
    /**
     * @param array{q?:string,jurusan_id?:string,industri_id?:string,status?:string,tahun_ajaran?:string,tanggal?:string} $filters
     */
    public function getLogbooksForGuru(GuruPembimbing $guru, array $filters): LengthAwarePaginator
    {
        $siswaIds = PenempatanPKL::where('guru_pembimbing_id', $guru->id)->pluck('siswa_id');

        return Logbook::with(['siswa.user', 'siswa.jurusan', 'industri', 'komentar.guruPembimbing.user'])
            ->whereIn('siswa_id', $siswaIds)
            ->when($filters['jurusan_id'] ?? null, function ($query, $jurusanId) {
                $query->whereHas('siswa', function ($sq) use ($jurusanId) {
                    $sq->where('jurusan_id', $jurusanId);
                });
            })
            ->when($filters['tahun_ajaran'] ?? null, function ($query, $tahunAjaran) {
                $query->whereHas('siswa', function ($sq) use ($tahunAjaran) {
                    $sq->where('tahun_ajaran', $tahunAjaran);
                });
            })
            ->when($filters['industri_id'] ?? null, function ($query, $industriId) {
                $query->where('industri_id', $industriId);
            })
            ->when($filters['tanggal'] ?? null, function ($query, $tanggal) {
                $query->whereDate('tanggal', $tanggal);
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status_validasi', $status);
            })
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('siswa.user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%' . $search . '%');
                    })->orWhereHas('siswa', function ($sq) use ($search) {
                        $sq->where('nis', 'like', '%' . $search . '%');
                    });
                });
            })
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * @return array{jurusanOptions:\Illuminate\Support\Collection,tahunAjaranOptions:\Illuminate\Support\Collection,industriOptions:\Illuminate\Support\Collection}
     */
    public function getFilterOptionsForGuru(GuruPembimbing $guru): array
    {
        $siswaIds = PenempatanPKL::where('guru_pembimbing_id', $guru->id)->pluck('siswa_id');

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
            Logbook::whereIn('siswa_id', $siswaIds)->pluck('industri_id')->unique()
        )->orderBy('nama_industri')->get();

        return [
            'jurusanOptions' => $jurusanOptions,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'industriOptions' => $industriOptions,
        ];
    }

    public function isBimbingan(GuruPembimbing $guru, Logbook $logbook): bool
    {
        return PenempatanPKL::where('guru_pembimbing_id', $guru->id)
            ->where('siswa_id', $logbook->siswa_id)
            ->exists();
    }

    /**
     * @param array{komentar:string} $data
     */
    public function createKomentar(GuruPembimbing $guru, Logbook $logbook, array $data): LogbookKomentar
    {
        return LogbookKomentar::create([
            'logbook_id' => $logbook->id,
            'guru_pembimbing_id' => $guru->id,
            'komentar' => $data['komentar'],
        ]);
    }
}
