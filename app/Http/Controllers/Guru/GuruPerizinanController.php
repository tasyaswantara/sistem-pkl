<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\PenempatanPKL;
use App\Models\Perizinan;
use Illuminate\Http\Request;

class GuruPerizinanController extends Controller
{
    public function index(Request $request)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, 'Akun guru belum terhubung.');
        }

        $siswaIds = PenempatanPKL::where('guru_pembimbing_id', $guru->id)->pluck('siswa_id');

        $perizinanList = Perizinan::with(['siswa.user', 'siswa.jurusan', 'industri'])
            ->whereIn('siswa_id', $siswaIds)
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('guru.perizinan.guru-perizinan', [
            'perizinanList' => $perizinanList,
        ]);
    }
}
