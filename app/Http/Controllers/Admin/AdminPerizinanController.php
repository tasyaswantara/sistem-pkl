<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PerizinanStatus;
use App\Http\Controllers\Controller;
use App\Models\Perizinan;
use App\Services\AdminPerizinanService;
use Illuminate\Http\Request;

class AdminPerizinanController extends Controller
{
    public function index(Request $request, AdminPerizinanService $service)
    {
        $filters = [
            'tanggal_dari' => $request->input('tanggal_dari'),
            'tanggal_sampai' => $request->input('tanggal_sampai'),
            'industri_id' => $request->input('industri_id'),
            'status' => $request->input('status', 'all'),
            'q' => $request->input('q', ''),
        ];

        $options = $service->getOptions();
        $data = $service->getPerizinanData($filters);

        $statusLabels = [
            'all' => 'Semua Status',
            PerizinanStatus::MENUNGGU->value => 'Menunggu',
            PerizinanStatus::DISETUJUI->value => 'Disetujui',
            PerizinanStatus::DITOLAK->value => 'Ditolak',
        ];

        return view('admin.perizinan.admin-perizinan', [
            'industriOptions' => $options['industriOptions'],
            'siswaPenempatanOptions' => $options['siswaPenempatanOptions'],
            'filters' => $filters,
            'statusCounts' => $data['statusCounts'],
            'statusLabels' => $statusLabels,
            'perizinanList' => $data['perizinanList'],
        ]);
    }

    public function store(Request $request, AdminPerizinanService $service)
    {
        $validated = $request->validate([
            'scope' => 'required|in:all,selected',
            'siswa_ids' => 'nullable|array',
            'siswa_ids.*' => 'exists:siswa,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        if ($validated['scope'] === 'selected' && empty($validated['siswa_ids'])) {
            return back()->withErrors(['siswa_ids' => __('admin_perizinan.errors.min')])->withInput();
        }

        $created = $service->createBulkPerizinan($request->user()->id, $validated);

        if ($created === 0) {
            return back()->withErrors(['siswa_ids' => __('admin_perizinan.errors.aktif')])->withInput();
        }

        return back()->with('success', __('admin_perizinan.success.kirim', ['count' => $created]));
    }

    public function update(Request $request, Perizinan $perizinan, AdminPerizinanService $service)
    {
        $validated = $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $service->updatePerizinan($perizinan, $validated);

        return back()->with('success', __('admin_perizinan.success.ubah'));
    }

    public function destroy(Perizinan $perizinan, AdminPerizinanService $service)
    {
        $service->deletePerizinan($perizinan);

        return back()->with('success', __('admin_perizinan.success.hapus'));
    }
}
