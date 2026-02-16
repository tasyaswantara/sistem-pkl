<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Services\AdminRiskService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminRiskController extends Controller
{
    public function runRisk(Request $request, AdminRiskService $service)
    {
        $weekStartInput = $request->input('week_start');
        $weekEndInput = $request->input('week_end');
        if ($weekStartInput && $weekEndInput) {
            $weekStartCheck = Carbon::parse($weekStartInput);
            $weekEndCheck = Carbon::parse($weekEndInput);
            if ($weekEndCheck->lt($weekStartCheck)) {
                return back()
                    ->withErrors(['week_end' => __('risk.end_date_after_start')])
                    ->withInput();
            }
            if ($weekEndCheck->gt(now()->endOfDay())) {
                return back()
                    ->withErrors(['week_end' => __('risk.end_date_not_future')])
                    ->withInput();
            }
        }

        $weekStart = $weekStartInput
            ? Carbon::parse($weekStartInput)->startOfDay()
            : now()->subDays(6)->startOfDay();
        $weekEnd = $weekEndInput
            ? Carbon::parse($weekEndInput)->endOfDay()
            : now()->endOfDay();

        $updatedCount = $service->runRisk($weekStart, $weekEnd);

        // return kembali dan menampilkan pesan sukses dalam bahasa indo
        return back()->with('success', __('risk.updated', ['count' => $updatedCount]));
    }

    public function index(AdminRiskService $service)
    {
        $filters = [
            'q' => request()->input('q'),
            'category' => request()->input('category', 'all'),
            'jurusan_id' => request()->input('jurusan_id'),
        ];

        $jurusanOptions = Jurusan::orderBy('nama')->get();

        $data = $service->getLatestRiskData($filters);

        return view('admin.risk.admin-risk', [
            'riskScores' => $data['riskScores'],
            'weekStart' => $data['weekStart'],
            'weekEnd' => $data['weekEnd'],
            'detailByRiskId' => $data['detailByRiskId'],
            'filters' => $filters,
            'jurusanOptions' => $jurusanOptions,
        ]);
    }
}
