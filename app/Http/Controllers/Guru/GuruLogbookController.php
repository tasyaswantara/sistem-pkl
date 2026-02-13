<?php

namespace App\Http\Controllers\Guru;

use App\Enums\LogbookStatus;
use App\Http\Controllers\Controller;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\Logbook;
use App\Models\LogbookKomentar;
use App\Models\PenempatanPKL;
use App\Models\Siswa;
use Illuminate\Http\Request;

class GuruLogbookController extends Controller
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
            'status' => $request->input('status'),
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
            Logbook::whereIn('siswa_id', $siswaIds)->pluck('industri_id')->unique()
        )->orderBy('nama_industri')->get();

        $statusLabels = [
            '' => 'Semua Status',
            LogbookStatus::PENDING->value => 'Pending',
            LogbookStatus::DISETUJUI->value => 'Disetujui',
            LogbookStatus::DITOLAK->value => 'Ditolak',
        ];

        $logbooks = Logbook::with(['siswa.user', 'siswa.jurusan', 'industri', 'komentar.guruPembimbing.user'])
            ->whereIn('siswa_id', $siswaIds)
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
                $query->whereDate('tanggal', $tanggal);
            })
            ->when($filters['status'], function ($query, $status) {
                $query->where('status_validasi', $status);
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
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('guru.elogbook.guru-elogbook', [
            'logbooks' => $logbooks,
            'filters' => $filters,
            'jurusanOptions' => $jurusanOptions,
            'tahunAjaranOptions' => $tahunAjaranOptions,
            'industriOptions' => $industriOptions,
            'statusLabels' => $statusLabels,
        ]);
    }

    public function storeKomentar(Request $request, Logbook $logbook)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, 'Akun guru belum terhubung.');
        }

        $isBimbingan = PenempatanPKL::where('guru_pembimbing_id', $guru->id)
            ->where('siswa_id', $logbook->siswa_id)
            ->exists();

        if (!$isBimbingan) {
            abort(403, 'Logbook bukan siswa bimbingan.');
        }

        $validated = $request->validate([
            'komentar' => 'required|string|max:1000',
        ]);

        LogbookKomentar::create([
            'logbook_id' => $logbook->id,
            'guru_pembimbing_id' => $guru->id,
            'komentar' => $validated['komentar'],
        ]);

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }
}
