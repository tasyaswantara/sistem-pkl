<?php

namespace App\Http\Controllers\Industri;

use App\Http\Controllers\Controller;
use App\Models\AspekPenilaian;
use App\Models\DetailPenilaian;
use App\Models\PenempatanPKL;
use App\Models\Penilaian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndustriPenilaianController extends Controller
{
    public function index(Request $request)
    {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, 'Akun industri belum terhubung.');
        }

        $penempatanList = PenempatanPKL::with(['siswa.user', 'siswa.jurusan'])
            ->where('industri_id', $industri->id)
            ->where('status', 'diterima_industri')
            ->orderByDesc('id')
            ->get();

        $aspekList = AspekPenilaian::orderBy('nama_aspek')->get();

        $penilaianMap = Penilaian::with('detailPenilaian')
            ->where('industri_id', $industri->id)
            ->whereIn('siswa_id', $penempatanList->pluck('siswa_id'))
            ->get()
            ->keyBy('siswa_id');

        return view('industri.penilaian.index', [
            'penempatanList' => $penempatanList,
            'aspekList' => $aspekList,
            'penilaianMap' => $penilaianMap,
        ]);
    }

    public function store(Request $request, PenempatanPKL $penempatan)
    {
        $industri = $request->user()->industri;
        if (!$industri || $penempatan->industri_id !== $industri->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $validated = $request->validate([
            'nilai' => 'required|array',
            'nilai.*' => 'required|numeric|min:0|max:100',
        ]);

        $aspekList = AspekPenilaian::orderBy('nama_aspek')->get();

        DB::transaction(function () use ($aspekList, $validated, $penempatan, $industri) {
            $total = 0;
            foreach ($aspekList as $aspek) {
                $nilai = (float) ($validated['nilai'][$aspek->id] ?? 0);
                $total += $nilai * (float) $aspek->bobot;
            }

            $penilaian = Penilaian::updateOrCreate(
                [
                    'siswa_id' => $penempatan->siswa_id,
                    'industri_id' => $industri->id,
                ],
                [
                    'tanggal_penilaian' => now()->toDateString(),
                    'total_nilai' => round($total, 2),
                ]
            );

            DetailPenilaian::where('penilaian_id', $penilaian->id)->delete();

            foreach ($aspekList as $aspek) {
                $nilai = (float) ($validated['nilai'][$aspek->id] ?? 0);
                DetailPenilaian::create([
                    'penilaian_id' => $penilaian->id,
                    'aspek_penilaian_id' => $aspek->id,
                    'nilai' => $nilai,
                ]);
            }

            $penempatan->siswa?->update(['status_pkl' => 'selesai']);
        });

        return back()->with('success', 'Penilaian berhasil disimpan.');
    }
}
