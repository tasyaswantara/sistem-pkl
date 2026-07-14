<?php

namespace App\Services;
// kirim notifikasi internal admin, guru, siswa, perwakilan industri
use App\Enums\PenempatanStatus;
use App\Enums\PerizinanStatus;
use App\Models\Industri;
use App\Models\PenempatanPKL;
use App\Models\Perizinan;
use App\Models\RiskScore;
use App\Models\User;
use App\Models\UsulanIndustri;
use App\Notifications\SystemDatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class AppNotificationService
{
    public function notifyAdminsOfUsulanIndustri(UsulanIndustri $usulan): void
    {
        $this->sendToUsers(
            User::role('admin')->get(),
            'Usulan industri baru',
            sprintf(
                '%s mengusulkan industri %s.',
                $usulan->siswa?->user?->name ?? 'Siswa',
                $usulan->nama_industri ?? '-'
            ),
            route('admin.penempatan', ['tab' => 'usulan']),
            [
                'type' => 'usulan_industri_baru',
                'usulan_industri_id' => $usulan->id,
            ]
        );
    }

    public function notifyIndustryOfPengajuanBaru(Industri $industri): void
    {
        $this->sendToUser(
            $industri->user,
            'Pengajuan baru dari sekolah',
            sprintf(
                'Sekolah mengirim pengajuan kerja sama untuk %s.',
                $industri->nama_industri ?? 'industri Anda'
            ),
            route('industri.pengajuan'),
            [
                'type' => 'pengajuan_baru',
                'industri_id' => $industri->id,
            ]
        );
    }

    public function notifyStudentOfJadwalWawancara(PenempatanPKL $penempatan, array $jadwalData): void
    {
        $tanggal = $jadwalData['tanggal'] ?? '-';
        $waktu = $jadwalData['waktu'] ?? '-';
        $lokasi = $jadwalData['lokasi'] ?? '-';

        $this->sendToUser(
            $penempatan->siswa?->user,
            'Jadwal wawancara ditetapkan',
            sprintf(
                'Wawancara di %s dijadwalkan pada %s %s di %s.',
                $penempatan->industri?->nama_industri ?? 'industri',
                $tanggal,
                $waktu !== '-' ? "pukul {$waktu}" : '',
                $lokasi
            ),
            route('siswa.dashboard'),
            [
                'type' => 'jadwal_wawancara',
                'penempatan_id' => $penempatan->id,
            ]
        );
    }

    public function notifyPenempatanDecision(PenempatanPKL $penempatan): void
    {
        $status = (string) $penempatan->status;
        $statusLabel = $this->labelPenempatanStatus($status);
        $body = sprintf(
            '%s memberikan keputusan %s untuk %s.',
            $penempatan->industri?->nama_industri ?? 'Industri',
            strtolower($statusLabel),
            $penempatan->siswa?->user?->name ?? 'siswa'
        );

        $payload = [
            'type' => 'keputusan_industri',
            'penempatan_id' => $penempatan->id,
            'status' => $status,
        ];

        $this->sendToUsers(
            User::role('admin')->get(),
            'Keputusan industri',
            $body,
            route('admin.penempatan', ['tab' => 'hasil']),
            $payload
        );

        $this->sendToUser(
            $penempatan->siswa?->user,
            'Hasil pengajuan industri',
            sprintf(
                'Status pengajuan Anda di %s: %s.',
                $penempatan->industri?->nama_industri ?? 'industri',
                $statusLabel
            ),
            route('siswa.dashboard'),
            $payload
        );
    }

    public function notifyPerizinanCreated(Perizinan $perizinan): void
    {
        $tanggal = $perizinan->tanggal_mulai?->format('d/m/Y') . ' - ' . $perizinan->tanggal_selesai?->format('d/m/Y');
        $body = sprintf(
            '%s mengajukan perizinan %s untuk periode %s.',
            $perizinan->siswa?->user?->name ?? 'Siswa',
            $perizinan->jenis_izin ?? 'izin',
            $tanggal
        );

        $payload = [
            'type' => 'perizinan_baru',
            'perizinan_id' => $perizinan->id,
        ];

        $this->sendToUsers(
            User::role('admin')->get(),
            'Perizinan siswa baru',
            $body,
            route('admin.perizinan'),
            $payload
        );

        $this->sendToUser(
            $perizinan->industri?->user,
            'Perizinan perlu diproses',
            $body,
            route('industri.perizinan'),
            $payload
        );
    }

    public function notifyStudentOfPerizinanDecision(Perizinan $perizinan): void
    {
        $status = (string) $perizinan->status;
        if (!in_array($status, [PerizinanStatus::DISETUJUI->value, PerizinanStatus::DITOLAK->value], true)) {
            return;
        }

        $this->sendToUser(
            $perizinan->siswa?->user,
            'Status perizinan diperbarui',
            sprintf(
                'Perizinan %s Anda telah %s oleh industri.',
                $perizinan->jenis_izin ?? 'izin',
                strtolower($this->labelPerizinanStatus($status))
            ),
            route('siswa.presensi'),
            [
                'type' => 'perizinan_diputuskan',
                'perizinan_id' => $perizinan->id,
                'status' => $status,
            ]
        );
    }

    public function notifyIndustryOfOutsideLocationPresensi(\App\Models\AbsensiPkl $absensi): void
    {
        $this->sendToUser(
            $absensi->industri?->user,
            'Presensi luar lokasi menunggu persetujuan',
            sprintf(
                '%s mengajukan presensi di luar lokasi pada %s dan menunggu persetujuan.',
                $absensi->siswa?->user?->name ?? 'Siswa',
                $absensi->check_in_at?->format('d/m/Y H:i') ?? '-'
            ),
            route('industri.presensi'),
            [
                'type' => 'presensi_luar_lokasi_menunggu',
                'absensi_id' => $absensi->id,
            ]
        );
    }

    public function notifyStudentOfPresensiDecision(\App\Models\AbsensiPkl $absensi): void
    {
        if (!in_array((string) $absensi->approval_status, ['disetujui', 'ditolak'], true)) {
            return;
        }

        $isApproved = $absensi->approval_status === 'disetujui';
        $this->sendToUser(
            $absensi->siswa?->user,
            'Status presensi luar lokasi diperbarui',
            sprintf(
                'Presensi luar lokasi Anda pada %s telah %s oleh industri.',
                $absensi->check_in_at?->format('d/m/Y H:i') ?? '-',
                $isApproved ? 'disetujui' : 'ditolak'
            ),
            route('siswa.presensi'),
            [
                'type' => 'presensi_luar_lokasi_diputuskan',
                'absensi_id' => $absensi->id,
                'approval_status' => $absensi->approval_status,
                'status' => $absensi->status,
            ]
        );
    }

    public function notifyGuruOfLaporan(PenempatanPKL $penempatan): void
    {
        $this->sendToUser(
            $penempatan->guruPembimbing?->user,
            'Laporan industri masuk',
            sprintf(
                'Laporan industri untuk %s dari %s telah masuk.',
                $penempatan->siswa?->user?->name ?? 'siswa',
                $penempatan->industri?->nama_industri ?? 'industri'
            ),
            route('guru.siswa'),
            [
                'type' => 'laporan_industri_masuk',
                'penempatan_id' => $penempatan->id,
            ]
        );
    }

    public function notifyRiskAlert(RiskScore $riskScore, ?string $previousCategory = null): void
    {
        $category = (string) $riskScore->category;
        if (!in_array($category, ['sedang', 'tinggi'], true)) {
            return;
        }

        if ($previousCategory === $category) {
            return;
        }

        $riskScore->loadMissing(['siswa.user']);
        $penempatan = PenempatanPKL::with('guruPembimbing.user')
            ->where('siswa_id', $riskScore->siswa_id)
            ->latest('id')
            ->first();

        $title = $category === 'tinggi'
            ? 'Peringatan dini kategori tinggi'
            : 'Peringatan dini kategori sedang';
        $body = sprintf(
            '%s masuk kategori %s dengan skor %s.',
            $riskScore->siswa?->user?->name ?? 'Siswa',
            $category,
            number_format((float) $riskScore->score, 2)
        );
        $payload = [
            'type' => 'peringatan_dini_' . $category,
            'risk_score_id' => $riskScore->id,
            'category' => $category,
        ];

        if ($category === 'tinggi') {
            $this->sendToUsers(
                User::role('admin')->get(),
                $title,
                $body,
                route('admin.peringatan-dini'),
                $payload
            );
        }

        $this->sendToUser(
            $penempatan?->guruPembimbing?->user,
            $title,
            $body,
            route('guru.peringatan-dini'),
            $payload
        );
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function sendToUser(?User $user, string $title, string $body, ?string $url = null, array $meta = []): void
    {
        if (!$user) {
            return;
        }

        $user->notify(new SystemDatabaseNotification($title, $body, $url, $meta));
    }

    /**
     * @param Collection<int, User> $users
     * @param array<string, mixed> $meta
     */
    private function sendToUsers(Collection $users, string $title, string $body, ?string $url = null, array $meta = []): void
    {
        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new SystemDatabaseNotification($title, $body, $url, $meta));
    }

    private function labelPenempatanStatus(string $status): string
    {
        return match ($status) {
            PenempatanStatus::DITERIMA_INDUSTRI->value => 'Diterima industri',
            PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value => 'Tidak lolos industri',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function labelPerizinanStatus(string $status): string
    {
        return match ($status) {
            PerizinanStatus::DISETUJUI->value => 'Disetujui',
            PerizinanStatus::DITOLAK->value => 'Ditolak',
            default => 'Menunggu',
        };
    }
}
