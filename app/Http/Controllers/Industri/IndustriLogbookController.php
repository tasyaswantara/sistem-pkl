<?php

namespace App\Http\Controllers\Industri;

use App\Enums\LogbookStatus;
use App\Http\Controllers\Controller;
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

        $logbooks = Logbook::with(['siswa.user', 'siswa.jurusan'])
            ->where('industri_id', $industri->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('industri.elogbook.industri-elogbook', [
            'logbooks' => $logbooks,
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
