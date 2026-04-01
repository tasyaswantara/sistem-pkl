<?php

namespace App\Http\Controllers\Industri;

use App\Http\Controllers\Controller;
use App\Models\AbsensiPkl;
use App\Services\AppNotificationService;
use App\Services\IndustriPresensiService;
use Illuminate\Http\Request;

class IndustriPresensiController extends Controller
{
    public function index(Request $request, IndustriPresensiService $service)
    {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, __('industri_presensi.errors.akun'));
        }

        $filters = [
            'date' => $request->input('date', now()->toDateString()),
            'jurusan_id' => $request->input('jurusan_id'),
            'status' => $request->input('status', 'all'),
            'q' => $request->input('q', ''),
        ];

        $options = $service->getOptions($industri);
        $data = $service->getIndexData($industri, $filters);

        return view('industri.presensi.industri-presensi', [
            'filters' => $filters,
            'jurusanOptions' => $options['jurusanOptions'],
            'absensiList' => $data['absensiList'],
            'statusCounts' => $data['statusCounts'],
            'mapPoints' => $data['mapPoints'],
            'statusLabels' => __('presensi.status'),
        ]);
    }

    public function review(
        Request $request,
        AbsensiPkl $absensi,
        IndustriPresensiService $service,
        AppNotificationService $notificationService
    ) {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, __('industri_presensi.errors.akun'));
        }

        $validated = $request->validate([
            'approval_status' => 'required|in:disetujui,ditolak',
            'approval_note' => 'nullable|string|max:500',
        ]);

        $reviewedAbsensi = $service->reviewOutsideLocationPresensi(
            $industri,
            $absensi,
            $validated['approval_status'],
            $validated['approval_note'] ?? null,
            (int) $request->user()->id
        );

        $notificationService->notifyStudentOfPresensiDecision($reviewedAbsensi);

        return back()->with('success', __('industri_presensi.success.review'));
    }
}
