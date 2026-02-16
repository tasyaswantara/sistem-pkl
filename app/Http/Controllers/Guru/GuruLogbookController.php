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
            abort(403, 'Akun guru belum terhubung.');
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
            abort(403, 'Akun guru belum terhubung.');
        }

        if (!$service->isBimbingan($guru, $logbook)) {
            abort(403, 'Logbook bukan siswa bimbingan.');
        }

        $validated = $request->validate([
            'komentar' => 'required|string|max:1000',
        ]);

        $service->createKomentar($guru, $logbook, $validated);

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }
}
