<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Services\GuruPresensiService;
use Illuminate\Http\Request;

class GuruPresensiController extends Controller
{
    public function index(Request $request, GuruPresensiService $service)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, __('guru_presensi.errors.akun'));
        }

        $filters = [
            'date' => $request->input('date', now()->toDateString()),
            'tahun_ajaran' => $request->input('tahun_ajaran'),
            'jurusan_id' => $request->input('jurusan_id'),
            'industri_id' => $request->input('industri_id'),
            'status' => $request->input('status', 'all'),
            'q' => $request->input('q', ''),
        ];

        $options = $service->getOptions($guru);
        $data = $service->getIndexData($guru, $filters);

        return view('guru.presensi.guru-presensi', [
            'filters' => $filters,
            'tahunAjaranOptions' => $options['tahunAjaranOptions'],
            'jurusanOptions' => $options['jurusanOptions'],
            'industriOptions' => $options['industriOptions'],
            'absensiList' => $data['absensiList'],
            'statusCounts' => $data['statusCounts'],
            'mapPoints' => $data['mapPoints'],
            'statusLabels' => __('presensi.status'),
        ]);
    }
}
