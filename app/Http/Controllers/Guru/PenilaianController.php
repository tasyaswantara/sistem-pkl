<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\AspekPenilaian;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\PenempatanPKL;
use App\Models\Penilaian;
use App\Models\Siswa;
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

        $filters = [
            'q' => $request->input('q'),
            'jurusan_id' => $request->input('jurusan_id'),
            'industri_id' => $request->input('industri_id'),
            'tahun_ajaran' => $request->input('tahun_ajaran'),
            'tanggal' => $request->input('tanggal'),
        ];

        $jurusanOptions = Jurusan::whereIn(
            'id',
            Siswa::whereIn('id', $siswaIds)->pluck('jurusan_id')->unique()
        )->orderBy('nama')->get();

        $tahunAjaranOptions = Siswa::whereIn('id', $siswaIds)
            ->select('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');

        $industriOptions = Industri::whereIn(
            'id',
            Penilaian::whereIn('siswa_id', $siswaIds)->pluck('industri_id')->unique()
        )->orderBy('nama_industri')->get();

        $latestPenilaianIds = Penilaian::selectRaw('MAX(id) as id')
            ->whereIn('siswa_id', $siswaIds)
            ->groupBy('siswa_id', 'industri_id');

        $penilaianList = Penilaian::with(['siswa.user', 'siswa.jurusan', 'industri', 'detailPenilaian.aspekPenilaian'])
            ->whereIn('id', $latestPenilaianIds)
            ->when($filters['jurusan_id'], function ($query, $jurusanId) {
                $query->whereHas('siswa', function ($sq) use ($jurusanId) {
                    $sq->where('jurusan_id', $jurusanId);
                });
            })
            ->when($filters['tahun_ajaran'], function ($query, $tahunAjaran) {
                $query->whereHas('siswa', function ($sq) use ($tahunAjaran) {
                    $sq->where('tahun_ajaran', $tahunAjaran);
                });
            })
            ->when($filters['industri_id'], function ($query, $industriId) {
                $query->where('industri_id', $industriId);
            })
            ->when($filters['tanggal'], function ($query, $tanggal) {
                $query->whereDate('tanggal_penilaian', $tanggal);
            })
            ->when($filters['q'], function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('siswa.user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%' . $search . '%');
                    })->orWhereHas('siswa', function ($sq) use ($search) {
                        $sq->where('nis', 'like', '%' . $search . '%');
                    });
                });
            })
            ->orderByDesc('tanggal_penilaian')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $aspekList = AspekPenilaian::orderBy('nama_aspek')->get();

        return view('guru.penilaian.index', [
            'penilaianList' => $penilaianList,
            'aspekList' => $aspekList,
            'filters' => $filters,
            'jurusanOptions' => $jurusanOptions,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'industriOptions' => $industriOptions,
        ]);
    }
}
