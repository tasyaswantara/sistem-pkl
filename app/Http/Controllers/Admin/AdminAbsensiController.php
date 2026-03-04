<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAbsensiService;
use Illuminate\Http\Request;

class AdminAbsensiController extends Controller
{
    public function index(Request $request, AdminAbsensiService $service)
    {
        $filters = [
            'date' => $request->input('date', now()->toDateString()),
            'jurusan_id' => $request->input('jurusan_id'),
            'industri_id' => $request->input('industri_id'),
            'status' => $request->input('status', 'all'),
            'q' => $request->input('q', ''),
        ];

        $options = $service->getOptions();
        $data = $service->getIndexData($filters);

        return view('admin.absensi.admin-absensi', [
            'filters' => $filters,
            'jurusanOptions' => $options['jurusanOptions'],
            'industriOptions' => $options['industriOptions'],
            'absensiList' => $data['absensiList'],
            'statusCounts' => $data['statusCounts'],
            'mapPoints' => $data['mapPoints'],
            'geofenceList' => $data['geofenceList'],
            'globalRadiusM' => $data['globalRadiusM'],
            'radiusUniform' => $data['radiusUniform'],
            'statusLabels' => __('absensi.status'),
        ]);
    }

    public function updateGlobalRadius(Request $request, AdminAbsensiService $service)
    {
        $validated = $request->validate([
            'geofence_radius_m' => 'required|integer|min:20|max:5000',
        ]);

        $service->updateGlobalRadius((int) $validated['geofence_radius_m']);

        return back()->with('success', __('absensi.success.global_radius'));
    }
}
