<?php

namespace App\Http\Controllers\Industri;

use App\Http\Controllers\Controller;
use App\Services\IndustriAbsensiService;
use Illuminate\Http\Request;

class IndustriAbsensiController extends Controller
{
    public function index(Request $request, IndustriAbsensiService $service)
    {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, __('industri_absensi.errors.akun'));
        }

        $filters = [
            'date' => $request->input('date', now()->toDateString()),
            'jurusan_id' => $request->input('jurusan_id'),
            'status' => $request->input('status', 'all'),
            'q' => $request->input('q', ''),
        ];

        $options = $service->getOptions($industri);
        $data = $service->getIndexData($industri, $filters);

        return view('industri.absensi.industri-absensi', [
            'filters' => $filters,
            'jurusanOptions' => $options['jurusanOptions'],
            'absensiList' => $data['absensiList'],
            'statusCounts' => $data['statusCounts'],
            'mapPoints' => $data['mapPoints'],
            'statusLabels' => __('absensi.status'),
        ]);
    }
}
