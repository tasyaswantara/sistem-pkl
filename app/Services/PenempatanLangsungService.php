<?php

namespace App\Services;

use App\Enums\JenisPenempatan;
use App\Enums\PenempatanStatus;
use App\Enums\PengajuanStatus;
use App\Enums\PilihanSiswa;
use App\Enums\StatusPKL;
use App\Models\Industri;
use App\Models\PenempatanPKL;
use App\Models\Siswa;
use App\Notifications\PenempatanLangsungAssigned;

class PenempatanLangsungService
{
    public function __construct(private PenempatanStatusService $statusService)
    {
    }

    /**
     * @param array{mode:string,siswa_id:int,industri_id:int,alasan:string} $data
     * @return array{ok:bool,error_key?:string,penempatan?:PenempatanPKL}
     */
    public function assign(array $data, int $actorId): array
    {
        $siswa = Siswa::find($data['siswa_id']);
        $industri = Industri::find($data['industri_id']);

        if (!$siswa || !$industri) {
            return ['ok' => false, 'error_key' => 'penempatan.errors.data'];
        }

        $status = $data['mode'] === 'sekolah'
            ? PenempatanStatus::DITERIMA_INDUSTRI->value
            : PenempatanStatus::PROSES_PENGAJUAN->value;

        $existingPenempatan = PenempatanPKL::where('siswa_id', $siswa->id)->first();
        $oldStatus = $existingPenempatan?->status;

        $penempatan = PenempatanPKL::updateOrCreate(
            ['siswa_id' => $siswa->id],
            [
                'industri_id' => $industri->id,
                'usulan_industri_id' => null,
                'pilihan_siswa' => PilihanSiswa::LANGSUNG->value,
                'status' => $status,
                'jenis_penempatan' => JenisPenempatan::LANGSUNG->value,
                'alasan_penempatan_langsung' => $data['alasan'],
                'ditetapkan_oleh' => $actorId,
                'ditetapkan_at' => now(),
            ]
        );

        if ($oldStatus !== null) {
            $this->statusService->handleStatusChange($penempatan, $oldStatus);
        }

        if ($status === PenempatanStatus::PROSES_PENGAJUAN->value && !$industri->status_pengajuan) {
            $industri->update([
                'status_pengajuan' => PengajuanStatus::MENUNGGU->value,
                'pengajuan_dikirim_at' => now(),
            ]);
        }

        if ($status === PenempatanStatus::DITERIMA_INDUSTRI->value) {
            $siswa->update(['status_pkl' => StatusPKL::BERJALAN->value]);
        }

        $siswa->user?->notify(new PenempatanLangsungAssigned($penempatan));
        $penempatan->guruPembimbing?->user?->notify(new PenempatanLangsungAssigned($penempatan));

        return ['ok' => true, 'penempatan' => $penempatan];
    }
}
