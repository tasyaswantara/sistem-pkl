<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Perizinan;
use Illuminate\Http\Request;

class PerizinanController extends Controller
{
    public function index(Request $request)
    {
        $siswa = $request->user()->siswa;
        if (!$siswa) {
            abort(403, 'Akun siswa belum terhubung.');
        }

        $perizinanList = Perizinan::with('industri')
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('siswa.perizinan.index', [
            'perizinanList' => $perizinanList,
        ]);
    }
}
