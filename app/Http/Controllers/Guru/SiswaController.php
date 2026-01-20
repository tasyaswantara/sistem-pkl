<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\PenempatanPKL;
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, 'Akun guru belum terhubung.');
        }

        $penempatanList = PenempatanPKL::with(['siswa.user', 'siswa.jurusan', 'industri'])
            ->where('guru_pembimbing_id', $guru->id)
            ->orderByDesc('id')
            ->get();

        return view('guru.siswa.index', [
            'penempatanList' => $penempatanList,
        ]);
    }
}
