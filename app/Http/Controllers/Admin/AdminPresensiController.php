<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminPresensiService;
use Illuminate\Http\Request;

class AdminPresensiController extends Controller
{
    public function index(Request $request, AdminPresensiService $service)
    {
        $filters = [
            'date' => $request->input('date', now()->toDateString()),
            'tahun_ajaran' => $request->input('tahun_ajaran'),
            'jurusan_id' => $request->input('jurusan_id'),
            'industri_id' => $request->input('industri_id'),
            'status' => $request->input('status', 'all'),
            'q' => $request->input('q', ''),
        ];

        $options = $service->getOptions();
        $data = $service->getIndexData($filters);

        return view('admin.presensi.admin-presensi', [
            'filters' => $filters,
            'tahunAjaranOptions' => $options['tahunAjaranOptions'],
            'jurusanOptions' => $options['jurusanOptions'],
            'industriOptions' => $options['industriOptions'],
            'absensiList' => $data['absensiList'],
            'statusCounts' => $data['statusCounts'],
            'mapPoints' => $data['mapPoints'],
            'geofenceList' => $data['geofenceList'],
            'globalRadiusM' => $data['globalRadiusM'],
            'radiusUniform' => $data['radiusUniform'],
            'statusLabels' => __('presensi.status'),
        ]);
    }

    public function updateGlobalRadius(Request $request, AdminPresensiService $service)
    {
        $validated = $request->validate([
            'geofence_radius_m' => 'required|integer|min:20|max:5000',
        ]);

        $service->updateGlobalRadius((int) $validated['geofence_radius_m']);

        return back()->with('success', __('presensi.success.global_radius'));
    }
}
