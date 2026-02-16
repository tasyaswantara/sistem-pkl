<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LogbookStatus;
use App\Http\Controllers\Controller;
use App\Services\AdminLogbookService;
use Illuminate\Http\Request;

class AdminLogbookController extends Controller
{
    public function index(Request $request, AdminLogbookService $service)
    {
        $filters = [
            'tahun_ajaran' => $request->input('tahun_ajaran'),
            'jurusan_id' => $request->input('jurusan_id'),
            'industri_id' => $request->input('industri_id'),
            'status' => $request->input('status', 'all'),
            'q' => $request->input('q', ''),
        ];

        $options = $service->getOptions();
        $data = $service->getLogbookData($filters);

        $statusLabels = [
            'all' => 'Semua Status',
            LogbookStatus::PENDING->value => 'Pending',
            LogbookStatus::DISETUJUI->value => 'Disetujui',
            LogbookStatus::DITOLAK->value => 'Ditolak',
        ];

        return view('admin.elogbook.admin-elogbook', [
            'jurusanOptions' => $options['jurusanOptions'],
            'industriOptions' => $options['industriOptions'],
            'tahunAjaranList' => $options['tahunAjaranList'],
            'filters' => $filters,
            'statusCounts' => $data['statusCounts'],
            'statusLabels' => $statusLabels,
            'logbooks' => $data['logbooks'],
        ]);
    }
}
