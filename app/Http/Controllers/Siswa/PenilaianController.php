<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\AspekPenilaian;
use App\Models\Penilaian;
use Illuminate\Http\Request;

class PenilaianController extends Controller
{
    public function index(Request $request)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, 'Akun siswa belum terhubung.');
        }

        $penilaianList = Penilaian::with(['industri', 'detailPenilaian.aspekPenilaian'])
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('tanggal_penilaian')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $aspekList = AspekPenilaian::orderBy('nama_aspek')->get();

        return view('siswa.penilaian.index', [
            'penilaianList' => $penilaianList,
            'aspekList' => $aspekList,
        ]);
    }
}
