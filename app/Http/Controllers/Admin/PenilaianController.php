<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\AspekPenilaian;
use App\Models\Penilaian;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenilaianController extends Controller
{
    public function index(Request $request)
    {
        $jurusanOptions = Jurusan::orderBy('nama')->get();
        $industriOptions = Industri::orderBy('nama_industri')->get();

        $tahunAjaranList = Siswa::query()
            ->select('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');

        $filters = [
            'tahun_ajaran' => $request->input('tahun_ajaran'),
            'jurusan_id' => $request->input('jurusan_id'),
            'industri_id' => $request->input('industri_id'),
            'q' => $request->input('q', ''),
        ];

        $baseQuery = Penilaian::query()
            ->with(['siswa.user', 'siswa.jurusan', 'industri', 'detailPenilaian.aspekPenilaian']);

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

        $penilaianList = $baseQuery
            ->orderByDesc('tanggal_penilaian')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $aspekList = AspekPenilaian::orderBy('nama_aspek')->get();
        $totalBobot = $aspekList->sum('bobot');
        $isBobotValid = abs($totalBobot - 1) <= 0.02;

        return view('admin.penilaian.index', [
            'jurusanOptions' => $jurusanOptions,
            'industriOptions' => $industriOptions,
            'tahunAjaranList' => $tahunAjaranList,
            'filters' => $filters,
            'penilaianList' => $penilaianList,
            'aspekList' => $aspekList,
            'totalBobot' => $totalBobot,
            'isBobotValid' => $isBobotValid,
        ]);
    }

    public function updateRubrik(Request $request)
    {
        $validated = $request->validate([
            'bobot' => 'required|array',
            'bobot.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $total = 0;
        foreach ($validated['bobot'] as $value) {
            $total += (float) ($value ?? 0);
        }

        if (abs($total - 100) > 0.01) {
            return back()->withErrors(['bobot' => 'Total bobot rubrik harus 100%.'])->withInput();
        }

        $aspekIds = AspekPenilaian::whereIn('id', array_keys($validated['bobot']))->pluck('id');

        DB::transaction(function () use ($validated, $aspekIds) {
            foreach ($aspekIds as $aspekId) {
                $persen = (float) ($validated['bobot'][$aspekId] ?? 0);
                AspekPenilaian::where('id', $aspekId)->update([
                    'bobot' => round($persen / 100, 2),
                ]);
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'total' => $total,
                'is_valid' => abs($total - 100) <= 0.01,
                'message' => 'Rubrik penilaian berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Rubrik penilaian berhasil diperbarui.');
    }

    public function storeAspek(Request $request)
    {
        $validated = $request->validate([
            'nama_aspek' => 'required|string|max:255',
        ]);

        AspekPenilaian::create([
            'nama_aspek' => $validated['nama_aspek'],
            'bobot' => 0,
        ]);

        return back()->with('success', 'Aspek penilaian berhasil ditambahkan.');
    }

    public function destroyAspek(AspekPenilaian $aspek)
    {
        $aspek->delete();

        return back()->with('success', 'Aspek penilaian berhasil dihapus.');
    }
}
