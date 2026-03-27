<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Services\GuruPerizinanService;
use Illuminate\Http\Request;

class GuruPerizinanController extends Controller
{
    public function index(Request $request, GuruPerizinanService $service)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, __('guru_perizinan.errors.akun'));
        }

        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'status' => (string) $request->input('status', ''),
            'tanggal' => (string) $request->input('tanggal', ''),
            'jurusan_id' => (string) $request->input('jurusan_id', ''),
            'industri_id' => (string) $request->input('industri_id', ''),
        ];

        $perizinanList = $service->getPerizinanForGuru($guru, $filters);
        $options = $service->getFilterOptionsForGuru($guru);

        return view('guru.perizinan.guru-perizinan', [
            'perizinanList' => $perizinanList,
            'filters' => $filters,
            'jurusanOptions' => $options['jurusanOptions'],
            'industriOptions' => $options['industriOptions'],
        ]);
    }
}
