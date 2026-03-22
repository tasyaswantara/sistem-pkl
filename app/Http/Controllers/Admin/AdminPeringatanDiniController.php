<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminPeringatanDiniService;
use App\Services\PeringatanDiniReadService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminPeringatanDiniController extends Controller
{
    public function runRisk(Request $request, AdminPeringatanDiniService $service)
    {
        $weekStartInput = $request->input('week_start');
        $weekEndInput = $request->input('week_end');
        $tahunAjaran = $request->input('tahun_ajaran');
        if ($weekStartInput && $weekEndInput) {
            $weekStartCheck = Carbon::parse($weekStartInput);
            $weekEndCheck = Carbon::parse($weekEndInput);
            if ($weekEndCheck->lt($weekStartCheck)) {
                return back()
                    ->withErrors(['week_end' => __('peringatan_dini.akhir')])
                    ->withInput();
            }
            if ($weekEndCheck->gt(now()->endOfDay())) {
                return back()
                    ->withErrors(['week_end' => __('peringatan_dini.batas')])
                    ->withInput();
            }
        }

        $weekStart = $weekStartInput
            ? Carbon::parse($weekStartInput)->startOfDay()
            : now()->subDays(6)->startOfDay();
        $weekEnd = $weekEndInput
            ? Carbon::parse($weekEndInput)->endOfDay()
            : now()->endOfDay();

        $updatedCount = $service->runRisk($weekStart, $weekEnd, $tahunAjaran);

        // return kembali dan menampilkan pesan sukses dalam bahasa indo
        return back()->with('success', __('peringatan_dini.update', ['count' => $updatedCount]));
    }

    public function index(Request $request, PeringatanDiniReadService $service)
    {
        $filters = [
            'q' => $request->input('q'),
            'category' => $request->input('category', 'all'),
            'jurusan_id' => $request->input('jurusan_id'),
            'tahun_ajaran' => $request->input('tahun_ajaran'),
        ];

        $jurusanOptions = $service->getJurusanOptions();
        $tahunAjaranOptions = $service->getTahunAjaranOptions($filters['jurusan_id']);
        $data = $service->getLatestRiskData($filters);

        return view('admin.peringatan-dini.admin-peringatan-dini', [
            'riskScores' => $data['riskScores'],
            'weekStart' => $data['weekStart'],
            'weekEnd' => $data['weekEnd'],
            'detailByRiskId' => $data['detailByRiskId'],
            'filters' => $filters,
            'jurusanOptions' => $jurusanOptions,
            'tahunAjaranOptions' => $tahunAjaranOptions,
        ]);
    }
}
