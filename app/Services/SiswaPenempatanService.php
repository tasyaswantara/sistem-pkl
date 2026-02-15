<?php

namespace App\Services;

use App\Enums\JenisPenempatan;
use App\Enums\PenempatanStatus;
use App\Enums\PilihanSiswa;
use App\Enums\UsulanStatus;
use App\Models\PenempatanPKL;
use App\Models\UsulanIndustri;
use App\Models\Siswa;

class SiswaPenempatanService
{
    public function canUpdatePilihan(?PenempatanPKL $penempatan): bool
    {
        if (!$penempatan) {
            return true;
        }

        if ($penempatan->jenis_penempatan === JenisPenempatan::LANGSUNG->value) {
            return false;
        }

        return in_array($penempatan->status, [
            PenempatanStatus::BELUM_MEMILIH->value,
            PenempatanStatus::DITOLAK_SEKOLAH->value,
            PenempatanStatus::PENGAJUAN_DITOLAK_INDUSTRI->value,
            PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value,
        ], true);
    }

    public function handlePilihanRekomendasi(Siswa $siswa, int $industriId): PenempatanPKL
    {
        return PenempatanPKL::updateOrCreate(
            ['siswa_id' => $siswa->id],
            [
                'industri_id' => $industriId,
                'usulan_industri_id' => null,
                'pilihan_siswa' => PilihanSiswa::REKOMENDASI->value,
                'status' => PenempatanStatus::MENUNGGU_KONFIRMASI->value,
            ]
        );
    }

    public function handleUsulanIndustri(Siswa $siswa, array $data): array
    {
        $usulan = UsulanIndustri::create([
            'siswa_id' => $siswa->id,
            'jurusan_id' => $siswa->jurusan_id,
            'nama_industri' => $data['nama_industri'],
            'email' => $data['email'],
            'kapasitas' => $data['kapasitas'],
            'alamat' => $data['alamat'],
            'kontak' => $data['kontak'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
            'status' => UsulanStatus::MENUNGGU->value,
        ]);

        $penempatan = PenempatanPKL::updateOrCreate(
            ['siswa_id' => $siswa->id],
            [
                'industri_id' => null,
                'usulan_industri_id' => $usulan->id,
                'pilihan_siswa' => PilihanSiswa::USULAN_LAIN->value,
                'status' => PenempatanStatus::MENUNGGU_KONFIRMASI->value,
            ]
        );

        return [$usulan, $penempatan];
    }
    public function berkasLengkap(Siswa $siswa): bool
    {
        $hasBpjs = !empty($siswa->bpjs_link);
        $hasKartu = !empty($siswa->kartu_pelajar_link);
        $hasCv = !empty($siswa->cv_link);
        $portofolio = is_array($siswa->portofolio_links) ? $siswa->portofolio_links : [];
        $hasPortofolio = count($portofolio) > 0;

        return $hasBpjs && $hasKartu && $hasCv && $hasPortofolio;
    }
}
