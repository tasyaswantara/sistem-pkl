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

        $perizinanList = $service->getPerizinanForGuru($guru);

        return view('guru.perizinan.guru-perizinan', [
            'perizinanList' => $perizinanList,
        ]);
    }
}
