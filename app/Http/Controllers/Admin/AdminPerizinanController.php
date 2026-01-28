<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\Perizinan;
use App\Models\PenempatanPKL;
use App\Models\Siswa;
use Illuminate\Http\Request;

class AdminPerizinanController extends Controller
{
    public function index(Request $request)
    {
        $jurusanOptions = Jurusan::orderBy('nama')->get();
        $industriOptions = Industri::orderBy('nama_industri')->get();
        $siswaPenempatanOptions = PenempatanPKL::with(['siswa.user', 'siswa.jurusan', 'industri'])
            ->where('status', 'diterima_industri')
            ->whereNotNull('industri_id')
            ->orderByDesc('id')
            ->get();

        $tahunAjaranList = Siswa::query()
            ->select('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');

        $filters = [
            'tahun_ajaran' => $request->input('tahun_ajaran'),
            'jurusan_id' => $request->input('jurusan_id'),
            'industri_id' => $request->input('industri_id'),
            'status' => $request->input('status', 'all'),
            'q' => $request->input('q', ''),
        ];

        $baseQuery = Perizinan::query()
            ->with(['siswa.user', 'siswa.jurusan', 'industri', 'pembuat']);

        if ($filters['tahun_ajaran']) {
            $baseQuery->whereHas('siswa', function ($query) use ($filters) {
                $query->where('tahun_ajaran', $filters['tahun_ajaran']);
            });
        }

        if ($filters['jurusan_id']) {
            $baseQuery->whereHas('siswa', function ($query) use ($filters) {
                $query->where('jurusan_id', $filters['jurusan_id']);
            });
        }

        if ($filters['industri_id']) {
            $baseQuery->where('industri_id', $filters['industri_id']);
        }

        if ($filters['q']) {
            $baseQuery->whereHas('siswa.user', function ($query) use ($filters) {
                $query->where('name', 'like', '%' . $filters['q'] . '%');
            });
        }

        $statusCounts = [
            'menunggu' => (clone $baseQuery)->where('status', 'menunggu')->count(),
            'disetujui' => (clone $baseQuery)->where('status', 'disetujui')->count(),
            'ditolak' => (clone $baseQuery)->where('status', 'ditolak')->count(),
        ];

        $perizinanQuery = clone $baseQuery;
        if ($filters['status'] !== 'all') {
            $perizinanQuery->where('status', $filters['status']);
        }

        $perizinanList = $perizinanQuery
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $statusLabels = [
            'all' => 'Semua Status',
            'menunggu' => 'Menunggu',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
        ];

        return view('admin.perizinan.index', [
            'jurusanOptions' => $jurusanOptions,
            'industriOptions' => $industriOptions,
            'siswaPenempatanOptions' => $siswaPenempatanOptions,
            'tahunAjaranList' => $tahunAjaranList,
            'filters' => $filters,
            'statusCounts' => $statusCounts,
            'statusLabels' => $statusLabels,
            'perizinanList' => $perizinanList,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|in:all,selected',
            'siswa_ids' => 'nullable|array',
            'siswa_ids.*' => 'exists:siswa,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        if ($validated['scope'] === 'selected' && empty($validated['siswa_ids'])) {
            return back()->withErrors(['siswa_ids' => 'Pilih minimal satu siswa.'])->withInput();
        }

        $penempatanQuery = PenempatanPKL::query()
            ->where('status', 'diterima_industri')
            ->whereNotNull('industri_id');

        if ($validated['scope'] === 'selected') {
            $penempatanQuery->whereIn('siswa_id', $validated['siswa_ids']);
        }

        $penempatanList = $penempatanQuery->get();

        $created = 0;
        foreach ($penempatanList as $penempatan) {
            Perizinan::create([
                'siswa_id' => $penempatan->siswa_id,
                'industri_id' => $penempatan->industri_id,
                'created_by' => $request->user()->id,
                'jenis_izin' => 'Izin Kegiatan Sekolah',
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
                'status' => 'menunggu',
            ]);
            $created++;
        }

        if ($created === 0) {
            return back()->withErrors(['siswa_ids' => 'Tidak ada siswa dengan penempatan aktif untuk dikirim.'])->withInput();
        }

        return back()->with('success', "Perizinan berhasil dikirim ke {$created} siswa.");
    }

    public function update(Request $request, Perizinan $perizinan)
    {
        $validated = $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $perizinan->update([
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
        ]);

        return back()->with('success', 'Perizinan berhasil diperbarui.');
    }

    public function destroy(Perizinan $perizinan)
    {
        $perizinan->delete();

        return back()->with('success', 'Perizinan berhasil dihapus.');
    }
}
