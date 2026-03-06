<?php

namespace App\Http\Controllers\Siswa;

use App\Enums\PenempatanStatus;
use App\Http\Controllers\Controller;
use App\Models\Penilaian;
use App\Services\SiswaDashboardService;
use App\Services\SiswaPenempatanService;
use Illuminate\Http\Request;

class SiswaDashboardController extends Controller
{
    public function index(
        Request $request,
        SiswaDashboardService $dashboardService,
        SiswaPenempatanService $penempatanService
    ) {
        $siswa = $request->user()?->siswa?->loadMissing(['user', 'jurusan']);
        if (!$siswa) {
            abort(403, __('siswa_penempatan.errors.akun'));
        }

        $berkasComplete = $penempatanService->berkasLengkap($siswa);
        $dashboardData = $dashboardService->getDashboardData(
            $siswa,
            $request->query('month'),
            $berkasComplete
        );

        $penilaianList = Penilaian::with(['industri', 'detailPenilaian.aspekPenilaian'])
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('tanggal_penilaian')
            ->orderByDesc('id')
            ->paginate(5, ['*'], 'penilaian_page')
            ->withQueryString();

        $statusLabels = [
            PenempatanStatus::BELUM_MEMILIH->value => 'Belum memilih',
            PenempatanStatus::MENUNGGU_KONFIRMASI->value => 'Menunggu konfirmasi',
            PenempatanStatus::DITOLAK_SEKOLAH->value => 'Ditolak sekolah',
            PenempatanStatus::PROSES_PENGAJUAN->value => 'Proses pengajuan',
            PenempatanStatus::PENGAJUAN_DITOLAK_INDUSTRI->value => 'Pengajuan ditolak industri',
            PenempatanStatus::PROSES_WAWANCARA->value => 'Proses wawancara',
            PenempatanStatus::DITERIMA_INDUSTRI->value => 'Diterima industri',
            PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value => 'Tidak lolos industri',
        ];

        return view('siswa.dashboard.siswa-dashboard', [
            'siswa' => $siswa,
            'berkasComplete' => $berkasComplete,
            'canUpdatePilihan' => $penempatanService->canUpdatePilihan($dashboardData['penempatan']),
            'statusLabels' => $statusLabels,
            'penilaianList' => $penilaianList,
            ...$dashboardData,
        ]);
    }
}
