<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\AspekPenilaian;
use App\Models\PenempatanPKL;
use App\Models\Penilaian;
use Illuminate\Http\Request;

class PenilaianController extends Controller
{
    public function index(Request $request)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, 'Akun guru belum terhubung.');
        }

        $siswaIds = PenempatanPKL::where('guru_pembimbing_id', $guru->id)->pluck('siswa_id');

        $penilaianList = Penilaian::with(['siswa.user', 'siswa.jurusan', 'industri', 'detailPenilaian.aspekPenilaian'])
            ->whereIn('siswa_id', $siswaIds)
            ->orderByDesc('tanggal_penilaian')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $aspekList = AspekPenilaian::orderBy('nama_aspek')->get();

        return view('guru.penilaian.index', [
            'penilaianList' => $penilaianList,
            'aspekList' => $aspekList,
        ]);
    }
}
