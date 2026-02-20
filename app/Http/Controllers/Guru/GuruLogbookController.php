<?php

namespace App\Http\Controllers\Guru;

use App\Enums\LogbookStatus;
use App\Http\Controllers\Controller;
use App\Models\Logbook;
use App\Services\GuruLogbookService;
use Illuminate\Http\Request;

class GuruLogbookController extends Controller
{
    public function index(Request $request, GuruLogbookService $service)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, __('guru_logbook.errors.akun'));
        }

        $filters = [
            'q' => $request->input('q'),
            'jurusan_id' => $request->input('jurusan_id'),
            'industri_id' => $request->input('industri_id'),
            'status' => $request->input('status'),
            'tahun_ajaran' => $request->input('tahun_ajaran'),
            'tanggal' => $request->input('tanggal'),
        ];

        $statusLabels = [
            '' => 'Semua Status',
            LogbookStatus::PENDING->value => 'Pending',
            LogbookStatus::DISETUJUI->value => 'Disetujui',
            LogbookStatus::DITOLAK->value => 'Ditolak',
        ];

        $filterOptions = $service->getFilterOptionsForGuru($guru);
        $logbooks = $service->getLogbooksForGuru($guru, $filters);

        return view('guru.elogbook.guru-elogbook', [
            'logbooks' => $logbooks,
            'filters' => $filters,
            'jurusanOptions' => $filterOptions['jurusanOptions'],
            'tahunAjaranOptions' => $filterOptions['tahunAjaranOptions'],
            'industriOptions' => $filterOptions['industriOptions'],
            'statusLabels' => $statusLabels,
        ]);
    }

    public function storeKomentar(Request $request, Logbook $logbook, GuruLogbookService $service)
    {
        $guru = $request->user()->guruPembimbing;
        if (!$guru) {
            abort(403, __('guru_logbook.errors.akun'));
        }

        if (!$service->isBimbingan($guru, $logbook)) {
            abort(403, __('guru_logbook.errors.bimbingan'));
        }

        $validated = $request->validate([
            'komentar' => 'required|string|max:1000',
        ]);

        $service->createKomentar($guru, $logbook, $validated);

        return back()->with('success', __('guru_logbook.success.komentar'));
    }
}
