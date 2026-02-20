<?php

namespace App\Http\Controllers\Guru;

use App\Enums\PenempatanStatus;
use App\Http\Controllers\Controller;
use App\Services\GuruSiswaService;
use Illuminate\Http\Request;

class GuruSiswaController extends Controller
{
    public function index(Request $request, GuruSiswaService $service)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, __('guru_siswa.errors.akun'));
        }

        $filters = [
            'q' => $request->input('q'),
            'jurusan_id' => $request->input('jurusan_id'),
            'kelas' => $request->input('kelas'),
            'tahun_ajaran' => $request->input('tahun_ajaran'),
        ];

        $filterOptions = $service->getFilterOptionsForGuru($guru);
        $penempatanList = $service->getPenempatanList($guru, $filters);

        return view('guru.siswa.guru-siswa', [
            'penempatanList' => $penempatanList,
            'filters' => $filters,
            'jurusanOptions' => $filterOptions['jurusanOptions'],
            'kelasOptions' => $filterOptions['kelasOptions'],
            'tahunAjaranOptions' => $filterOptions['tahunAjaranOptions'],
            'statusLabels' => [
                PenempatanStatus::BELUM_MEMILIH->value => 'Belum memilih',
                PenempatanStatus::MENUNGGU_KONFIRMASI->value => 'Menunggu konfirmasi',
                PenempatanStatus::DITOLAK_SEKOLAH->value => 'Ditolak sekolah',
                PenempatanStatus::PROSES_PENGAJUAN->value => 'Proses pengajuan',
                PenempatanStatus::PENGAJUAN_DITOLAK_INDUSTRI->value => 'Pengajuan ditolak industri',
                PenempatanStatus::PROSES_WAWANCARA->value => 'Proses wawancara',
                PenempatanStatus::DITERIMA_INDUSTRI->value => 'Diterima industri',
                PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value => 'Tidak lolos industri',
            ],
        ]);
    }
}
