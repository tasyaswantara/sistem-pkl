<?php

namespace App\Http\Controllers\Industri;

use App\Enums\LogbookStatus;
use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\Logbook;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IndustriLogbookController extends Controller
{
    public function index(Request $request)
    {
        $industri = $request->user()->industri;
        if (!$industri) {
            abort(403, __('industri_logbook.errors.akun'));
        }

        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'status' => (string) $request->input('status', ''),
            'tanggal' => (string) $request->input('tanggal', ''),
            'jurusan_id' => (string) $request->input('jurusan_id', ''),
        ];

        $logbookQuery = Logbook::with(['siswa.user', 'siswa.jurusan'])
            ->where('industri_id', $industri->id)
            ->when($filters['status'] !== '', function ($query) use ($filters) {
                $query->where('status_validasi', $filters['status']);
            })
            ->when($filters['tanggal'] !== '', function ($query) use ($filters) {
                $query->whereDate('tanggal', $filters['tanggal']);
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

        $logbooks = $logbookQuery
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $jurusanOptions = Jurusan::whereIn(
            'id',
            Logbook::where('industri_id', $industri->id)
                ->whereHas('siswa')
                ->get()
                ->pluck('siswa.jurusan_id')
                ->filter()
                ->unique()
                ->all()
        )->orderBy('nama')->get();

        return view('industri.elogbook.industri-elogbook', [
            'logbooks' => $logbooks,
            'filters' => $filters,
            'jurusanOptions' => $jurusanOptions,
        ]);
    }

    public function update(Request $request, Logbook $logbook)
    {
        $industri = $request->user()->industri;
        if (!$industri || $logbook->industri_id !== $industri->id) {
            abort(403, __('industri_logbook.errors.akses'));
        }

        $validated = $request->validate([
            'status_validasi' => [
                'required',
                Rule::in(array_map(
                    static fn (LogbookStatus $status) => $status->value,
                    LogbookStatus::cases()
                )),
            ],
            'catatan_industri' => 'nullable|string|max:1000',
        ]);

        $logbook->update([
            'status_validasi' => $validated['status_validasi'],
            'catatan_industri' => $validated['catatan_industri'],
            'validated_at' => $validated['status_validasi'] === LogbookStatus::PENDING->value ? null : now(),
        ]);

        return back()->with('success', __('industri_logbook.success.valid'));
    }
}
