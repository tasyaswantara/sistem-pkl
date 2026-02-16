<?php

namespace App\Services;

use App\Enums\PenempatanStatus;
use App\Enums\StatusPKL;
use App\Models\AspekPenilaian;
use App\Models\DetailPenilaian;
use App\Models\Industri;
use App\Models\PenempatanPKL;
use App\Models\Penilaian;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IndustriPenilaianService
{
    public function getPenempatanList(Industri $industri): Collection
    {
        return PenempatanPKL::with(['siswa.user', 'siswa.jurusan'])
            ->where('industri_id', $industri->id)
            ->where('status', PenempatanStatus::DITERIMA_INDUSTRI->value)
            ->orderByDesc('id')
            ->get();
    }

    public function getAspekList(): Collection
    {
        return AspekPenilaian::orderBy('nama_aspek')->get();
    }

    public function getPenilaianMap(Industri $industri, Collection $penempatanList): Collection
    {
        return Penilaian::with('detailPenilaian')
            ->where('industri_id', $industri->id)
            ->whereIn('siswa_id', $penempatanList->pluck('siswa_id'))
            ->get()
            ->keyBy('siswa_id');
    }

    /**
     * @param array{nilai:array<int,int|float>} $data
     */
    public function savePenilaian(Industri $industri, PenempatanPKL $penempatan, array $data): void
    {
        $aspekList = $this->getAspekList();

        DB::transaction(function () use ($aspekList, $data, $penempatan, $industri) {
            $total = 0;
            foreach ($aspekList as $aspek) {
                $nilai = (float) ($data['nilai'][$aspek->id] ?? 0);
                $total += $nilai * (float) $aspek->bobot;
            }

            $penilaian = Penilaian::updateOrCreate(
                [
                    'siswa_id' => $penempatan->siswa_id,
                    'industri_id' => $industri->id,
                ],
                [
                    'tanggal_penilaian' => now()->toDateString(),
                    'total_nilai' => round($total, 2),
                ]
            );

            DetailPenilaian::where('penilaian_id', $penilaian->id)->delete();

            foreach ($aspekList as $aspek) {
                $nilai = (float) ($data['nilai'][$aspek->id] ?? 0);
                DetailPenilaian::create([
                    'penilaian_id' => $penilaian->id,
                    'aspek_penilaian_id' => $aspek->id,
                    'nilai' => $nilai,
                ]);
            }

            $penempatan->siswa?->update(['status_pkl' => StatusPKL::SELESAI->value]);
        });
    }
}
