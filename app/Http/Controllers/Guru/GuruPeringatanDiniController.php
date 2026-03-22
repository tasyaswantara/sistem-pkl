<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\PenempatanPKL;
use App\Services\PeringatanDiniReadService;
use Illuminate\Http\Request;

class GuruPeringatanDiniController extends Controller
{
    public function index(Request $request, PeringatanDiniReadService $service)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, __('guru_peringatan_dini.errors.akun'));
        }

        $filters = [
            'q' => $request->input('q'),
            'category' => $request->input('category', 'all'),
            'jurusan_id' => $request->input('jurusan_id'),
            'tahun_ajaran' => $request->input('tahun_ajaran'),
        ];

        // Scope data ke siswa bimbingan guru aktif, bukan seluruh risk score global.
        $siswaIds = PenempatanPKL::where('guru_pembimbing_id', $guru->id)->pluck('siswa_id')->all();
        $jurusanOptions = $service->getJurusanOptions($siswaIds);
        $tahunAjaranOptions = $service->getTahunAjaranOptions($filters['jurusan_id'], $siswaIds);
        $data = $service->getLatestRiskData($filters, $siswaIds);

        return view('guru.peringatan-dini.guru-peringatan-dini', [
            'riskScores' => $data['riskScores'],
            'weekStart' => $data['weekStart'],
            'weekEnd' => $data['weekEnd'],
            'detailByRiskId' => $data['detailByRiskId'],
            'filters' => $filters,
            'jurusanOptions' => $jurusanOptions,
            'tahunAjaranOptions' => $tahunAjaranOptions,
        ]);
    }
}
