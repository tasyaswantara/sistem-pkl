<?php

namespace App\Http\Controllers\Industri;

use App\Enums\PerizinanStatus;
use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\Perizinan;
use App\Services\AppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IndustriPerizinanController extends Controller
{
    public function index(Request $request)
    {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, __('industri_perizinan.errors.akun'));
        }

        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'status' => (string) $request->input('status', ''),
            'tanggal' => (string) $request->input('tanggal', ''),
            'jurusan_id' => (string) $request->input('jurusan_id', ''),
        ];

        $perizinanQuery = Perizinan::with(['siswa.user', 'siswa.jurusan'])
            ->where('industri_id', $industri->id)
            ->when($filters['status'] !== '', function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when($filters['tanggal'] !== '', function ($query) use ($filters) {
                $query->whereDate('tanggal_mulai', '<=', $filters['tanggal'])
                    ->whereDate('tanggal_selesai', '>=', $filters['tanggal']);
            })
            ->when($filters['jurusan_id'] !== '', function ($query) use ($filters) {
                $query->whereHas('siswa', function ($siswaQuery) use ($filters) {
                    $siswaQuery->where('jurusan_id', $filters['jurusan_id']);
                });
            })
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($nestedQuery) use ($filters) {
                    $nestedQuery->whereHas('siswa.user', function ($userQuery) use ($filters) {
                        $userQuery->where('name', 'like', '%' . $filters['q'] . '%');
                    })->orWhereHas('siswa', function ($siswaQuery) use ($filters) {
                        $siswaQuery->where('nis', 'like', '%' . $filters['q'] . '%');
                    });
                });
            });

        $perizinanList = $perizinanQuery
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $jurusanOptions = Jurusan::whereIn(
            'id',
            Perizinan::where('industri_id', $industri->id)
                ->whereHas('siswa')
                ->get()
                ->pluck('siswa.jurusan_id')
                ->filter()
                ->unique()
                ->all()
        )->orderBy('nama')->get();

        return view('industri.perizinan.industri-perizinan', [
            'perizinanList' => $perizinanList,
            'filters' => $filters,
            'jurusanOptions' => $jurusanOptions,
        ]);
    }

    public function update(Request $request, Perizinan $perizinan, AppNotificationService $notificationService)
    {
        $industri = $request->user()->industri;
        if (!$industri || $perizinan->industri_id !== $industri->id) {
            abort(403, __('industri_perizinan.errors.akses'));
        }

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(array_map(
                    static fn (PerizinanStatus $status) => $status->value,
                    PerizinanStatus::cases()
                )),
            ],
            'catatan_industri' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $perizinan->status;
        $perizinan->update([
            'status' => $validated['status'],
            'catatan_industri' => $validated['catatan_industri'],
        ]);

        if ($oldStatus !== $perizinan->status) {
            $perizinan->loadMissing(['siswa.user']);
            $notificationService->notifyStudentOfPerizinanDecision($perizinan);
        }

        return back()->with('success', __('industri_perizinan.success.ubah'));
    }
}
