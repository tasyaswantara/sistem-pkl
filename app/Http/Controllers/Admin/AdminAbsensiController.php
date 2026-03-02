<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Industri;
use App\Services\AdminAbsensiService;
use App\Services\NominatimGeocodingService;
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
            'statusLabels' => __('absensi.status'),
        ]);
    }

    public function updateGeofence(Request $request, Industri $industri, AdminAbsensiService $service)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'geofence_radius_m' => 'required|integer|min:20|max:5000',
        ]);

        $service->updateGeofence($industri, $validated);

        return back()->with('success', __('absensi.success.geofence'));
    }

    public function geocodeGeofence(
        Industri $industri,
        AdminAbsensiService $service,
        NominatimGeocodingService $geocodingService
    ) {
        if (trim((string) $industri->alamat) === '') {
            return back()->withErrors(['geocode' => __('absensi.errors.alamat_kosong')]);
        }

        // Geocode hanya sebagai titik awal; admin tetap bisa koreksi manual di peta.
        $result = $geocodingService->geocode((string) $industri->alamat);
        if (!$result) {
            return back()->withErrors(['geocode' => __('absensi.errors.geocode_gagal')]);
        }

        $service->updateGeofence($industri, [
            'latitude' => $result['lat'],
            'longitude' => $result['lng'],
            'geofence_radius_m' => (int) ($industri->geofence_radius_m ?? 200),
        ]);

        return back()->with('success', __('absensi.success.geocode_ok', [
            'industri' => $industri->nama_industri,
            'lat' => number_format($result['lat'], 7),
            'lng' => number_format($result['lng'], 7),
        ]));
    }
}
