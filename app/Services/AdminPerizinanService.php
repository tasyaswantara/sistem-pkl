<?php

namespace App\Services;

use App\Enums\JenisIzin;
use App\Enums\PenempatanStatus;
use App\Enums\PerizinanStatus;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\Perizinan;
use App\Models\PenempatanPKL;
use App\Models\Siswa;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminPerizinanService
{
    /**
     * @return array{jurusanOptions:\Illuminate\Support\Collection,industriOptions:\Illuminate\Support\Collection,siswaPenempatanOptions:\Illuminate\Support\Collection,tahunAjaranList:\Illuminate\Support\Collection}
     */
    public function getOptions(): array
    {
        $jurusanOptions = Jurusan::orderBy('nama')->get();
        $industriOptions = Industri::orderBy('nama_industri')->get();
        $siswaPenempatanOptions = PenempatanPKL::with(['siswa.user', 'siswa.jurusan', 'industri'])
            ->where('status', PenempatanStatus::DITERIMA_INDUSTRI->value)
            ->whereNotNull('industri_id')
            ->orderByDesc('id')
            ->get();

        $tahunAjaranList = Siswa::query()
            ->select('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');

        return [
            'jurusanOptions' => $jurusanOptions,
            'industriOptions' => $industriOptions,
            'siswaPenempatanOptions' => $siswaPenempatanOptions,
            'tahunAjaranList' => $tahunAjaranList,
        ];
    }

    /**
     * @param array{tahun_ajaran?:string,jurusan_id?:string,industri_id?:string,status?:string,q?:string} $filters
     * @return array{perizinanList:LengthAwarePaginator,statusCounts:array<string,int>}
     */
    public function getPerizinanData(array $filters): array
    {
        $baseQuery = Perizinan::query()
            ->with(['siswa.user', 'siswa.jurusan', 'industri', 'pembuat']);

        if (!empty($filters['tahun_ajaran'])) {
            $baseQuery->whereHas('siswa', function ($query) use ($filters) {
                $query->where('tahun_ajaran', $filters['tahun_ajaran']);
            });
        }

        if (!empty($filters['jurusan_id'])) {
            $baseQuery->whereHas('siswa', function ($query) use ($filters) {
                $query->where('jurusan_id', $filters['jurusan_id']);
            });
        }

        if (!empty($filters['industri_id'])) {
            $baseQuery->where('industri_id', $filters['industri_id']);
        }

        if (!empty($filters['q'])) {
            $baseQuery->whereHas('siswa.user', function ($query) use ($filters) {
                $query->where('name', 'like', '%' . $filters['q'] . '%');
            });
        }

        $statusCounts = [
            PerizinanStatus::MENUNGGU->value => (clone $baseQuery)
                ->where('status', PerizinanStatus::MENUNGGU->value)
                ->count(),
            PerizinanStatus::DISETUJUI->value => (clone $baseQuery)
                ->where('status', PerizinanStatus::DISETUJUI->value)
                ->count(),
            PerizinanStatus::DITOLAK->value => (clone $baseQuery)
                ->where('status', PerizinanStatus::DITOLAK->value)
                ->count(),
        ];

        $perizinanQuery = clone $baseQuery;
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $perizinanQuery->where('status', $filters['status']);
        }

        $perizinanList = $perizinanQuery
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return [
            'perizinanList' => $perizinanList,
            'statusCounts' => $statusCounts,
        ];
    }

    /**
     * @param array{scope:string,siswa_ids?:array<int,int>,tanggal_mulai:string,tanggal_selesai:string} $data
     */
    public function createBulkPerizinan(int $creatorId, array $data): int
    {
        $penempatanQuery = PenempatanPKL::query()
            ->where('status', PenempatanStatus::DITERIMA_INDUSTRI->value)
            ->whereNotNull('industri_id');

        if ($data['scope'] === 'selected') {
            $penempatanQuery->whereIn('siswa_id', $data['siswa_ids'] ?? []);
        }

        $penempatanList = $penempatanQuery->get();

        $created = 0;
        foreach ($penempatanList as $penempatan) {
            Perizinan::create([
                'siswa_id' => $penempatan->siswa_id,
                'industri_id' => $penempatan->industri_id,
                'created_by' => $creatorId,
                'jenis_izin' => JenisIzin::IZIN_KEGIATAN_SEKOLAH->value,
                'tanggal_mulai' => $data['tanggal_mulai'],
                'tanggal_selesai' => $data['tanggal_selesai'],
                'status' => PerizinanStatus::MENUNGGU->value,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * @param array{tanggal_mulai:string,tanggal_selesai:string} $data
     */
    public function updatePerizinan(Perizinan $perizinan, array $data): void
    {
        $perizinan->update([
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
        ]);
    }

    public function deletePerizinan(Perizinan $perizinan): void
    {
        $perizinan->delete();
    }
}
