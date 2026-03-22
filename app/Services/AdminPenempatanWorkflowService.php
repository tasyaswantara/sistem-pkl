<?php

namespace App\Services;

use App\Enums\PenempatanStatus;
use App\Enums\PengajuanStatus;
use App\Enums\PilihanSiswa;
use App\Enums\UsulanStatus;
use App\Models\BobotKriteria;
use App\Models\Industri;
use App\Models\Kriteria;
use App\Models\PenempatanPKL;
use App\Models\User;
use App\Models\UsulanIndustri;
use Illuminate\Support\Facades\DB;

class AdminPenempatanWorkflowService
{
    public function __construct(
        private AdminUserService $adminUserService,
        private PenempatanStatusService $penempatanStatusService,
    ) {
    }

    /**
     * @param array{jurusan_id:int|string,bobot:array<int|string,mixed>} $data
     * @return array{ok:bool,error_field?:string,error_key?:string}
     */
    public function storeBobot(array $data): array
    {
        $totalBobot = collect($data['bobot'])->sum(fn ($value) => (float) ($value ?? 0));
        if (abs($totalBobot - 100) > 0.01) {
            return $this->error('bobot', 'penempatan.errors.bobot');
        }

        $jurusanId = (int) $data['jurusan_id'];
        $kriteriaIds = Kriteria::whereIn('id', array_keys($data['bobot']))->pluck('id');

        DB::transaction(function () use ($data, $jurusanId, $kriteriaIds) {
            foreach ($kriteriaIds as $kriteriaId) {
                $persen = (float) ($data['bobot'][$kriteriaId] ?? 0);

                BobotKriteria::updateOrCreate(
                    [
                        'jurusan_id' => $jurusanId,
                        'kriteria_id' => $kriteriaId,
                    ],
                    [
                        'bobot' => round($persen / 100, 2),
                    ]
                );
            }
        });

        return ['ok' => true];
    }

    /**
     * @return array{ok:bool,success_key?:string,error_field?:string,error_key?:string}
     */
    public function approveUsulanIndustri(UsulanIndustri $usulan): array
    {
        if ($result = $this->validateUsulanReadiness($usulan)) {
            return $result;
        }

        $industri = $this->adminUserService->createIndustryRepresentativeFromUsulan($usulan);

        $usulan->update([
            'status' => UsulanStatus::DISETUJUI->value,
        ]);

        $existingPenempatan = PenempatanPKL::where('siswa_id', $usulan->siswa_id)->first();
        $penempatan = PenempatanPKL::updateOrCreate(
            ['siswa_id' => $usulan->siswa_id],
            [
                'industri_id' => $industri->id,
                'usulan_industri_id' => $usulan->id,
                'pilihan_siswa' => PilihanSiswa::USULAN_LAIN->value,
                'status' => PenempatanStatus::PROSES_PENGAJUAN->value,
            ]
        );

        $this->syncStatusChange($penempatan, $existingPenempatan?->status);

        return $this->success('penempatan.success.usul_ok');
    }

    /**
     * @return array{ok:bool,success_key?:string,error_field?:string,error_key?:string}
     */
    public function rejectUsulanIndustri(UsulanIndustri $usulan): array
    {
        if ($usulan->status !== UsulanStatus::MENUNGGU->value) {
            return $this->error('usulan', 'penempatan.errors.usulan_proses');
        }

        $usulan->update([
            'status' => UsulanStatus::DITOLAK->value,
        ]);

        $penempatan = PenempatanPKL::where('siswa_id', $usulan->siswa_id)
            ->where('usulan_industri_id', $usulan->id)
            ->first();

        if ($penempatan) {
            $oldStatus = $penempatan->status;
            $penempatan->update([
                'industri_id' => null,
                'usulan_industri_id' => null,
                'pilihan_siswa' => null,
                'status' => PenempatanStatus::DITOLAK_SEKOLAH->value,
            ]);
            $this->syncStatusChange($penempatan, $oldStatus);
        }

        return $this->success('penempatan.success.usul_tolak');
    }

    /**
     * @return array{ok:bool,success_key?:string,error_field?:string,error_key?:string}
     */
    public function confirmPilihan(PenempatanPKL $penempatan): array
    {
        if ($penempatan->status !== PenempatanStatus::MENUNGGU_KONFIRMASI->value) {
            return $this->error('penempatan', 'penempatan.errors.tunggu');
        }

        if ($penempatan->pilihan_siswa === PilihanSiswa::REKOMENDASI->value) {
            $industri = $penempatan->industri;
            if (!$industri) {
                return $this->error('penempatan', 'penempatan.errors.rekom');
            }

            $industri->update([
                'status_pengajuan' => PengajuanStatus::MENUNGGU->value,
                'pengajuan_dikirim_at' => now(),
                'pengajuan_dijawab_at' => null,
            ]);

            $oldStatus = $penempatan->status;
            $penempatan->update([
                'status' => PenempatanStatus::PROSES_PENGAJUAN->value,
            ]);
            $this->syncStatusChange($penempatan, $oldStatus);

            return $this->success('penempatan.success.pilih_ok');
        }

        if ($penempatan->pilihan_siswa === PilihanSiswa::USULAN_LAIN->value) {
            $usulan = $penempatan->usulanIndustri;
            if (!$usulan) {
                return $this->error('penempatan', 'penempatan.errors.usul_tidak');
            }

            $result = $this->validateUsulanReadiness($usulan, 'penempatan');
            if ($result) {
                return $result;
            }

            $industri = $this->adminUserService->createIndustryRepresentativeFromUsulan($usulan);

            $usulan->update([
                'status' => UsulanStatus::DISETUJUI->value,
            ]);

            $oldStatus = $penempatan->status;
            $penempatan->update([
                'industri_id' => $industri->id,
                'status' => PenempatanStatus::PROSES_PENGAJUAN->value,
            ]);
            $this->syncStatusChange($penempatan, $oldStatus);

            return $this->success('penempatan.success.usul_siswa');
        }

        return $this->error('penempatan', 'penempatan.errors.pilihan');
    }

    /**
     * @return array{ok:bool,success_key?:string,error_field?:string,error_key?:string}
     */
    public function rejectPilihan(PenempatanPKL $penempatan): array
    {
        if ($penempatan->status !== PenempatanStatus::MENUNGGU_KONFIRMASI->value) {
            return $this->error('penempatan', 'penempatan.errors.tunggu');
        }

        if ($penempatan->pilihan_siswa === PilihanSiswa::USULAN_LAIN->value && $penempatan->usulanIndustri) {
            $penempatan->usulanIndustri->update([
                'status' => UsulanStatus::DITOLAK->value,
            ]);
        }

        $oldStatus = $penempatan->status;
        $penempatan->update([
            'industri_id' => null,
            'usulan_industri_id' => null,
            'pilihan_siswa' => null,
            'status' => PenempatanStatus::DITOLAK_SEKOLAH->value,
        ]);
        $this->syncStatusChange($penempatan, $oldStatus);

        return $this->success('penempatan.success.pilih_tolak');
    }

    /**
     * @return array{ok:bool,success_key?:string,error_field?:string,error_key?:string}
     */
    public function assignGuruPembimbing(PenempatanPKL $penempatan, int $guruPembimbingId): array
    {
        if ($penempatan->status !== PenempatanStatus::DITERIMA_INDUSTRI->value) {
            return $this->error('guru_pembimbing_id', 'penempatan.errors.guru');
        }

        $penempatan->update([
            'guru_pembimbing_id' => $guruPembimbingId,
        ]);

        return $this->success('penempatan.success.guru');
    }

    public function updateLaporanStatus(PenempatanPKL $penempatan, string $laporanStatus): void
    {
        $penempatan->update([
            'laporan_status' => $laporanStatus,
        ]);
    }

    /**
     * Validasi ini dipusatkan agar approval usulan dari beberapa alur
     * tetap memakai aturan bisnis yang sama.
     *
     * @return array{ok:bool,error_field:string,error_key:string}|null
     */
    private function validateUsulanReadiness(UsulanIndustri $usulan, string $field = 'usulan'): ?array
    {
        if ($usulan->status !== UsulanStatus::MENUNGGU->value) {
            return $this->error($field, 'penempatan.errors.usulan_proses');
        }

        if (User::where('email', $usulan->email)->exists()) {
            return $this->error($field, 'penempatan.errors.email');
        }

        if (Industri::where('nama_industri', $usulan->nama_industri)->exists()) {
            return $this->error($field, 'penempatan.errors.nama');
        }

        return null;
    }

    private function syncStatusChange(PenempatanPKL $penempatan, ?string $oldStatus): void
    {
        $this->penempatanStatusService->handleStatusChange($penempatan, $oldStatus);
    }

    /**
     * @return array{ok:false,error_field:string,error_key:string}
     */
    private function error(string $field, string $key): array
    {
        return [
            'ok' => false,
            'error_field' => $field,
            'error_key' => $key,
        ];
    }

    /**
     * @return array{ok:true,success_key:string}
     */
    private function success(string $key): array
    {
        return [
            'ok' => true,
            'success_key' => $key,
        ];
    }
}
