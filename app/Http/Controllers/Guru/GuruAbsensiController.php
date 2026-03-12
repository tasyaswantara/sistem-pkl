<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Services\GuruAbsensiService;
use Illuminate\Http\Request;

class GuruAbsensiController extends Controller
{
    public function index(Request $request, GuruAbsensiService $service)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, __('guru_absensi.errors.akun'));
        }

        $filters = [
            'date' => $request->input('date', now()->toDateString()),
            'jurusan_id' => $request->input('jurusan_id'),
            'industri_id' => $request->input('industri_id'),
            'status' => $request->input('status', 'all'),
            'q' => $request->input('q', ''),
        ];

        $options = $service->getOptions($guru);
        $data = $service->getIndexData($guru, $filters);

        return view('guru.absensi.guru-absensi', [
            'filters' => $filters,
            'jurusanOptions' => $options['jurusanOptions'],
            'industriOptions' => $options['industriOptions'],
            'absensiList' => $data['absensiList'],
            'statusCounts' => $data['statusCounts'],
            'mapPoints' => $data['mapPoints'],
            'statusLabels' => __('absensi.status'),
        ]);
    }
}
