<?php

namespace App\Http\Controllers\Admin;
// validasi input/request lalu memanggil sawrunservice
use App\Enums\LaporanStatus;
use App\Http\Controllers\Controller;
use App\Services\AdminPenempatanService;
use App\Services\AdminPenempatanWorkflowService;
use App\Models\PenempatanPKL;
use App\Models\UsulanIndustri;
use App\Services\PenempatanLangsungService;
use App\Services\SawRunService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPenempatanController extends Controller
{
    public function index(Request $request, AdminPenempatanService $service)
    {
        return view('admin.penempatan.admin-penempatan', $service->getIndexData(
            array_merge(
                $request->only(['tab', 'jurusan_id', 'tahun_ajaran', 'status', 'q']),
                [
                    'has_jurusan_filter' => $request->has('jurusan_id'),
                    'has_tahun_ajaran_filter' => $request->has('tahun_ajaran'),
                ]
            )
        ));
    }

    public function penempatanLangsung(Request $request, PenempatanLangsungService $service)
    {
        $validated = $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'industri_id' => 'required|exists:industri,id',
            'mode' => 'required|in:industri,sekolah',
            'alasan' => 'required|string|max:1000',
        ]);

        $result = $service->assign($validated, (int) $request->user()->id);

        if (!$result['ok']) {
            return back()->withErrors(['penempatan' => __($result['error_key'] ?? 'penempatan.errors.data')]);
        }

        return back()->with('success', __('penempatan.success.langsung'));
    }

    public function storeBobot(Request $request, AdminPenempatanWorkflowService $workflowService)
    {
        $validated = $request->validate([
            'jurusan_id' => 'required|exists:jurusan,id',
            'bobot' => 'required|array',
            'bobot.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $result = $workflowService->storeBobot($validated);
        if (!$result['ok']) {
            return back()->withErrors([$result['error_field'] => __($result['error_key'])])->withInput();
        }

        return back()->with('success', __('penempatan.success.bobot'));
    }

    public function runSaw(Request $request, SawRunService $service)
    {
        $validated = $request->validate([
            'jurusan_id' => 'required|exists:jurusan,id',
            'tahun_ajaran' => 'required|string',
        ]);

        $result = $service->run(
            (int) $validated['jurusan_id'],
            (string) $validated['tahun_ajaran'],
            $request->user()?->id
        );

        if (!$result['ok']) {
            return back()->withErrors([
                $result['error_field'] ?? 'saw' => __($result['error_key'] ?? 'penempatan.errors.data_kurang'),
            ]);
        }

        return back()->with('success', __('penempatan.success.saw', ['count' => $result['rows_count'] ?? 0]));
    }

    public function approveUsulanIndustri(UsulanIndustri $usulan, AdminPenempatanWorkflowService $workflowService)
    {
        $result = $workflowService->approveUsulanIndustri($usulan);
        if (!$result['ok']) {
            return back()->withErrors([$result['error_field'] => __($result['error_key'])]);
        }

        return back()->with('success', __($result['success_key']));
    }

    public function rejectUsulanIndustri(UsulanIndustri $usulan, AdminPenempatanWorkflowService $workflowService)
    {
        $result = $workflowService->rejectUsulanIndustri($usulan);
        if (!$result['ok']) {
            return back()->withErrors([$result['error_field'] => __($result['error_key'])]);
        }

        return back()->with('success', __($result['success_key']));
    }

    public function confirmPilihan(PenempatanPKL $penempatan, AdminPenempatanWorkflowService $workflowService)
    {
        $result = $workflowService->confirmPilihan($penempatan);
        if (!$result['ok']) {
            return back()->withErrors([$result['error_field'] => __($result['error_key'])]);
        }

        return back()->with('success', __($result['success_key']));
    }

    public function rejectPilihan(PenempatanPKL $penempatan, AdminPenempatanWorkflowService $workflowService)
    {
        $result = $workflowService->rejectPilihan($penempatan);
        if (!$result['ok']) {
            return back()->withErrors([$result['error_field'] => __($result['error_key'])]);
        }

        return back()->with('success', __($result['success_key']));
    }

    public function setGuruPembimbing(
        Request $request,
        PenempatanPKL $penempatan,
        AdminPenempatanWorkflowService $workflowService
    )
    {
        $validated = $request->validate([
            'guru_pembimbing_id' => 'required|exists:guru_pembimbing,id',
        ]);

        $result = $workflowService->assignGuruPembimbing($penempatan, (int) $validated['guru_pembimbing_id']);
        if (!$result['ok']) {
            return back()->withErrors([$result['error_field'] => __($result['error_key'])]);
        }

        return back()->with('success', __($result['success_key']));
    }

    public function updateLaporanStatus(
        Request $request,
        PenempatanPKL $penempatan,
        AdminPenempatanWorkflowService $workflowService
    )
    {
        $validated = $request->validate([
            'laporan_status' => [
                'required',
                Rule::in(array_map(static fn (LaporanStatus $status) => $status->value, LaporanStatus::cases())),
            ],
        ]);

        $workflowService->updateLaporanStatus($penempatan, $validated['laporan_status']);

        return back()->with('success', __('penempatan.success.laporan'));
    }

}
