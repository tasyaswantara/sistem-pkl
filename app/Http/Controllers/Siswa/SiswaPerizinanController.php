<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Services\SiswaPerizinanService;
use Illuminate\Http\Request;

class SiswaPerizinanController extends Controller
{
    public function index(Request $request, SiswaPerizinanService $service)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, 'Akun siswa belum terhubung.');
        }

        $perizinanList = $service->getPerizinanForSiswa($siswa);

        return view('siswa.perizinan.siswa-perizinan', [
            'perizinanList' => $perizinanList,
        ]);
    }
}
