<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Services\GuruPenilaianService;
use Illuminate\Http\Request;

class GuruPenilaianController extends Controller
{
    public function index(Request $request, GuruPenilaianService $service)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, __('guru_penilaian.errors.akun'));
        }

        $filters = [
            'q' => $request->input('q'),
            'jurusan_id' => $request->input('jurusan_id'),
            'industri_id' => $request->input('industri_id'),
            'tahun_ajaran' => $request->input('tahun_ajaran'),
            'tanggal' => $request->input('tanggal'),
        ];

        $filterOptions = $service->getFilterOptionsForGuru($guru);
        $penilaianList = $service->getPenilaianForGuru($guru, $filters);

        return view('guru.penilaian.guru-penilaian', [
            'penilaianList' => $penilaianList,
            'aspekList' => $service->getAspekList(),
            'filters' => $filters,
            'jurusanOptions' => $filterOptions['jurusanOptions'],
            'tahunAjaranOptions' => $filterOptions['tahunAjaranOptions'],
            'industriOptions' => $filterOptions['industriOptions'],
        ]);
    }
}
