<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\PenempatanPKL;
use App\Models\Siswa;
use Illuminate\Http\Request;

class GuruSiswaController extends Controller
{
    public function index(Request $request)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, 'Akun guru belum terhubung.');
        }

        $filters = [
            'q' => $request->input('q'),
            'jurusan_id' => $request->input('jurusan_id'),
            'kelas' => $request->input('kelas'),
            'tahun_ajaran' => $request->input('tahun_ajaran'),
        ];

        $siswaIds = PenempatanPKL::where('guru_pembimbing_id', $guru->id)->pluck('siswa_id');

        $jurusanOptions = Jurusan::whereIn(
            'id',
            Siswa::whereIn('id', $siswaIds)->pluck('jurusan_id')->unique()
        )->orderBy('nama')->get();

        $kelasOptions = Siswa::whereIn('id', $siswaIds)
            ->select('kelas')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas');

        $tahunAjaranOptions = Siswa::whereIn('id', $siswaIds)
            ->select('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');

        $penempatanQuery = PenempatanPKL::with(['siswa.user', 'siswa.jurusan', 'industri'])
            ->where('guru_pembimbing_id', $guru->id)
            ->when($filters['jurusan_id'], function ($query, $jurusanId) {
                $query->whereHas('siswa', function ($sq) use ($jurusanId) {
                    $sq->where('jurusan_id', $jurusanId);
                });
            })
            ->when($filters['kelas'], function ($query, $kelas) {
                $query->whereHas('siswa', function ($sq) use ($kelas) {
                    $sq->where('kelas', $kelas);
                });
            })
            ->when($filters['tahun_ajaran'], function ($query, $tahunAjaran) {
                $query->whereHas('siswa', function ($sq) use ($tahunAjaran) {
                    $sq->where('tahun_ajaran', $tahunAjaran);
                });
            })
            ->when($filters['q'], function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('siswa.user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%' . $search . '%');
                    })->orWhereHas('siswa', function ($sq) use ($search) {
                        $sq->where('nis', 'like', '%' . $search . '%');
                    });
                });
            });

        $penempatanList = $penempatanQuery
            ->orderByDesc('id')
            ->get();

        return view('guru.siswa.guru-siswa', [
            'penempatanList' => $penempatanList,
            'filters' => $filters,
            'jurusanOptions' => $jurusanOptions,
            'kelasOptions' => $kelasOptions,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'statusLabels' => [
                'belum_memilih' => 'Belum memilih',
                'menunggu_konfirmasi' => 'Menunggu konfirmasi',
                'ditolak_sekolah' => 'Ditolak sekolah',
                'proses_pengajuan' => 'Proses pengajuan',
                'pengajuan_ditolak_industri' => 'Pengajuan ditolak industri',
                'proses_wawancara' => 'Proses wawancara',
                'diterima_industri' => 'Diterima industri',
                'tidak_lolos_industri' => 'Tidak lolos industri',
            ],
        ]);
    }
}
