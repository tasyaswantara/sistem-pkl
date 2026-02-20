<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JenisPenempatan;
use App\Enums\LaporanStatus;
use App\Enums\PenempatanStatus;
use App\Enums\PengajuanStatus;
use App\Enums\PilihanSiswa;
use App\Enums\UsulanStatus;
use App\Http\Controllers\Controller;
use App\Models\BobotKriteria;
use App\Models\HasilRekomendasi;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\Kriteria;
use App\Models\PenempatanPKL;
use App\Models\SawRun;
use App\Models\Siswa;
use App\Models\GuruPembimbing;
use App\Models\UsulanIndustri;
use App\Models\User;
use App\Services\PenempatanLangsungService;
use App\Services\SawRunService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\Rule;

class AdminPenempatanController extends Controller
{
    public function index(Request $request)
    {
        $jurusanOptions = Jurusan::orderBy('nama')->get();
        $selectedJurusan = $request->input('jurusan_id');

        if (!$selectedJurusan && $jurusanOptions->isNotEmpty()) {
            $selectedJurusan = $jurusanOptions->first()->id;
        }

        $tahunAjaranList = Siswa::query()
            ->when($selectedJurusan, function ($query) use ($selectedJurusan) {
                $query->where('jurusan_id', $selectedJurusan);
            })
            ->select('tahun_ajaran')
            ->distinct()
            ->orderBy('tahun_ajaran', 'desc')
            ->pluck('tahun_ajaran');

        $selectedTahun = $request->input('tahun_ajaran');
        if (!$selectedTahun && $tahunAjaranList->isNotEmpty()) {
            $selectedTahun = $tahunAjaranList->first();
        }

        $statusList = Lang::get('penempatan.list');
        $statusLabels = Lang::get('penempatan.label');
        $pilihanLabels = Lang::get('penempatan.pilih');

        $selectedStatus = $request->input('status', 'all');
        $search = $request->input('q', '');

        $kriteriaList = Kriteria::orderBy('nama_kriteria')->get();
        $bobotByKriteria = BobotKriteria::query()
            ->when($selectedJurusan, function ($query) use ($selectedJurusan) {
                $query->where('jurusan_id', $selectedJurusan);
            })
            ->get()
            ->keyBy('kriteria_id');

        $bobotKriteria = $kriteriaList->map(function ($kriteria) use ($bobotByKriteria) {
            $bobot = $bobotByKriteria->get($kriteria->id)?->bobot ?? 0;

            return [
                'id' => $kriteria->id,
                'kriteria' => $kriteria->nama_kriteria,
                'tipe' => $kriteria->tipe,
                'bobot' => $bobot,
            ];
        });

        $totalBobot = $bobotKriteria->sum('bobot');
        $isBobotValid = abs($totalBobot - 1) <= 0.02;

        $penempatanBaseQuery = PenempatanPKL::with([
            'siswa.user',
            'siswa.jurusan',
            'industri',
            'usulanIndustri',
            'guruPembimbing.user',
        ]);

        if ($selectedJurusan) {
            $penempatanBaseQuery->whereHas('siswa', function ($query) use ($selectedJurusan) {
                $query->where('jurusan_id', $selectedJurusan);
            });
        }

        if ($selectedTahun) {
            $penempatanBaseQuery->whereHas('siswa', function ($query) use ($selectedTahun) {
                $query->where('tahun_ajaran', $selectedTahun);
            });
        }

        if ($selectedStatus && $selectedStatus !== 'all') {
            $penempatanBaseQuery->where('status', $selectedStatus);
        }

        if ($search) {
            $penempatanBaseQuery->whereHas('siswa.user', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        $penempatanData = (clone $penempatanBaseQuery)
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $penempatanLangsung = (clone $penempatanBaseQuery)
            ->where('jenis_penempatan', JenisPenempatan::LANGSUNG->value)
            ->orderByDesc('id')
            ->get();

        $siswaOptions = Siswa::with('user', 'jurusan')
            ->orderBy('id')
            ->get();

        $industriOptions = Industri::orderBy('nama_industri')->get();

        $usulanList = UsulanIndustri::with(['siswa.user', 'jurusan'])
            ->orderByDesc('id')
            ->get();

        $guruOptions = GuruPembimbing::with('user', 'jurusan')
            ->orderBy('id')
            ->get()
            ->groupBy('jurusan_id');

        $statusCounts = [
            PenempatanStatus::DITERIMA_INDUSTRI->value => (clone $penempatanBaseQuery)
                ->where('status', PenempatanStatus::DITERIMA_INDUSTRI->value)
                ->count(),
            PenempatanStatus::PROSES_PENGAJUAN->value => (clone $penempatanBaseQuery)
                ->where('status', PenempatanStatus::PROSES_PENGAJUAN->value)
                ->count(),
            PenempatanStatus::MENUNGGU_KONFIRMASI->value => (clone $penempatanBaseQuery)
                ->where('status', PenempatanStatus::MENUNGGU_KONFIRMASI->value)
                ->count(),
        ];

        $latestSawRun = null;
        $rekomendasiBySiswa = collect();

        if ($selectedJurusan && $selectedTahun) {
            $latestSawRun = SawRun::query()
                ->where('jurusan_id', $selectedJurusan)
                ->where('tahun_ajaran', $selectedTahun)
                ->latest('run_at')
                ->first();

            if ($latestSawRun) {
                $rekomendasiBySiswa = HasilRekomendasi::with('industri')
                    ->where('saw_run_id', $latestSawRun->id)
                    ->orderBy('peringkat')
                    ->get()
                    ->groupBy('siswa_id');
            }
        }

        return view('admin.penempatan.admin-penempatan', [
            'tahunAjaranList' => $tahunAjaranList,
            'statusList' => $statusList,
            'statusLabels' => $statusLabels,
            'pilihanLabels' => $pilihanLabels,
            'jurusanOptions' => $jurusanOptions,
            'bobotKriteria' => $bobotKriteria,
            'selectedJurusan' => $selectedJurusan,
            'selectedTahun' => $selectedTahun,
            'selectedStatus' => $selectedStatus,
            'search' => $search,
            'penempatanData' => $penempatanData,
            'statusCounts' => $statusCounts,
            'latestSawRun' => $latestSawRun,
            'rekomendasiBySiswa' => $rekomendasiBySiswa,
            'usulanList' => $usulanList,
            'totalBobot' => $totalBobot,
            'isBobotValid' => $isBobotValid,
            'guruOptions' => $guruOptions,
            'siswaOptions' => $siswaOptions,
            'industriOptions' => $industriOptions,
            'penempatanLangsung' => $penempatanLangsung,
        ]);
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

    public function storeBobot(Request $request)
    {
        $validated = $request->validate([
            'jurusan_id' => 'required|exists:jurusan,id',
            'bobot' => 'required|array',
            'bobot.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $totalBobot = 0;
        foreach ($validated['bobot'] as $value) {
            $totalBobot += (float) ($value ?? 0);
        }

        if (abs($totalBobot - 100) > 0.01) {
            return back()->withErrors(['bobot' => __('penempatan.errors.bobot')])->withInput();
        }

        $jurusanId = $validated['jurusan_id'];
        $kriteriaIds = Kriteria::whereIn('id', array_keys($validated['bobot']))->pluck('id');

        DB::transaction(function () use ($validated, $jurusanId, $kriteriaIds) {
            foreach ($kriteriaIds as $kriteriaId) {
                $persen = (float) ($validated['bobot'][$kriteriaId] ?? 0);
                $bobot = round($persen / 100, 2);

                BobotKriteria::updateOrCreate(
                    [
                        'jurusan_id' => $jurusanId,
                        'kriteria_id' => $kriteriaId,
                    ],
                    [
                        'bobot' => $bobot,
                    ]
                );
            }
        });

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

    public function approveUsulanIndustri(Request $request, UsulanIndustri $usulan)
    {
        if ($usulan->status !== UsulanStatus::MENUNGGU->value) {
            return back()->withErrors(['usulan' => __('penempatan.errors.usulan_proses')]);
        }

        if (User::where('email', $usulan->email)->exists()) {
            return back()->withErrors(['usulan' => __('penempatan.errors.email')]);
        }

        if (Industri::where('nama_industri', $usulan->nama_industri)->exists()) {
            return back()->withErrors(['usulan' => __('penempatan.errors.nama')]);
        }

        $user = User::create([
            'name' => $usulan->nama_industri,
            'email' => $usulan->email,
            'password' => Hash::make($usulan->email),
        ]);

        $user->assignRole('perwakilan industri');

        $industri = $user->industri()->create([
            'nama_industri' => $usulan->nama_industri,
            'kapasitas' => $usulan->kapasitas,
            'alamat' => $usulan->alamat,
            'grade' => 'C',
            'jurusan_id' => $usulan->jurusan_id,
            'status_pengajuan' => PengajuanStatus::MENUNGGU->value,
            'pengajuan_dikirim_at' => now(),
        ]);

        $usulan->update([
            'status' => UsulanStatus::DISETUJUI->value,
        ]);

        $existingPenempatan = PenempatanPKL::where('siswa_id', $usulan->siswa_id)->first();
        $oldStatus = $existingPenempatan?->status;

        $penempatan = PenempatanPKL::updateOrCreate(
            ['siswa_id' => $usulan->siswa_id],
            [
                'industri_id' => $industri->id,
                'usulan_industri_id' => $usulan->id,
                'pilihan_siswa' => PilihanSiswa::USULAN_LAIN->value,
                'status' => PenempatanStatus::PROSES_PENGAJUAN->value,
            ]
        );

        if ($oldStatus !== null) {
            $this->handlePenempatanStatusChange($penempatan, $oldStatus);
        }

        return back()->with('success', __('penempatan.success.usul_ok'));
    }

    public function rejectUsulanIndustri(Request $request, UsulanIndustri $usulan)
    {
        if ($usulan->status !== UsulanStatus::MENUNGGU->value) {
            return back()->withErrors(['usulan' => __('penempatan.errors.usulan_proses')]);
        }

        $usulan->update([
            'status' => UsulanStatus::DITOLAK->value,
        ]);

        $penempatan = PenempatanPKL::where('siswa_id', $usulan->siswa_id)
            ->where('usulan_industri_id', $usulan->id)
            ->first();

        if ($penempatan) {
            $oldStatus = $penempatan->status;
            $penempatan->update([
                'industri_id' => null,
                'usulan_industri_id' => null,
                'pilihan_siswa' => null,
                'status' => PenempatanStatus::DITOLAK_SEKOLAH->value,
            ]);
            $this->handlePenempatanStatusChange($penempatan, $oldStatus);
        }

        return back()->with('success', __('penempatan.success.usul_tolak'));
    }

    public function confirmPilihan(PenempatanPKL $penempatan)
    {
        if ($penempatan->status !== PenempatanStatus::MENUNGGU_KONFIRMASI->value) {
            return back()->withErrors(['penempatan' => __('penempatan.errors.tunggu')]);
        }

        if ($penempatan->pilihan_siswa === PilihanSiswa::REKOMENDASI->value) {
            $industri = $penempatan->industri;
            if (!$industri) {
                return back()->withErrors(['penempatan' => __('penempatan.errors.rekom')]);
            }

            $industri->update([
                'status_pengajuan' => PengajuanStatus::MENUNGGU->value,
                'pengajuan_dikirim_at' => now(),
                'pengajuan_dijawab_at' => null,
            ]);

            $oldStatus = $penempatan->status;
            $penempatan->update([
                'status' => PenempatanStatus::PROSES_PENGAJUAN->value,
            ]);
            $this->handlePenempatanStatusChange($penempatan, $oldStatus);

            return back()->with('success', __('penempatan.success.pilih_ok'));
        }

        if ($penempatan->pilihan_siswa === PilihanSiswa::USULAN_LAIN->value) {
            $usulan = $penempatan->usulanIndustri;
            if (!$usulan) {
                return back()->withErrors(['penempatan' => __('penempatan.errors.usul_tidak')]);
            }

            if ($usulan->status !== UsulanStatus::MENUNGGU->value) {
                return back()->withErrors(['penempatan' => __('penempatan.errors.usulan_proses')]);
            }

            if (User::where('email', $usulan->email)->exists()) {
                return back()->withErrors(['penempatan' => __('penempatan.errors.email')]);
            }

            if (Industri::where('nama_industri', $usulan->nama_industri)->exists()) {
                return back()->withErrors(['penempatan' => __('penempatan.errors.nama')]);
            }

            $user = User::create([
                'name' => $usulan->nama_industri,
                'email' => $usulan->email,
                'password' => Hash::make($usulan->email),
            ]);

            $user->assignRole('perwakilan industri');

            $industri = $user->industri()->create([
                'nama_industri' => $usulan->nama_industri,
                'kapasitas' => $usulan->kapasitas,
                'alamat' => $usulan->alamat,
                'grade' => 'C',
                'jurusan_id' => $usulan->jurusan_id,
                'status_pengajuan' => PengajuanStatus::MENUNGGU->value,
                'pengajuan_dikirim_at' => now(),
            ]);

            $usulan->update([
                'status' => UsulanStatus::DISETUJUI->value,
            ]);

            $oldStatus = $penempatan->status;
            $penempatan->update([
                'industri_id' => $industri->id,
                'status' => PenempatanStatus::PROSES_PENGAJUAN->value,
            ]);
            $this->handlePenempatanStatusChange($penempatan, $oldStatus);

            return back()->with('success', __('penempatan.success.usul_siswa'));
        }

        return back()->withErrors(['penempatan' => __('penempatan.errors.pilihan')]);
    }

    public function rejectPilihan(PenempatanPKL $penempatan)
    {
        if ($penempatan->status !== PenempatanStatus::MENUNGGU_KONFIRMASI->value) {
            return back()->withErrors(['penempatan' => __('penempatan.errors.tunggu')]);
        }

        if ($penempatan->pilihan_siswa === PilihanSiswa::USULAN_LAIN->value && $penempatan->usulanIndustri) {
            $penempatan->usulanIndustri->update([
                'status' => UsulanStatus::DITOLAK->value,
            ]);
        }

        $oldStatus = $penempatan->status;
        $penempatan->update([
            'industri_id' => null,
            'usulan_industri_id' => null,
            'pilihan_siswa' => null,
            'status' => PenempatanStatus::DITOLAK_SEKOLAH->value,
        ]);
        $this->handlePenempatanStatusChange($penempatan, $oldStatus);

        return back()->with('success', __('penempatan.success.pilih_tolak'));
    }

    public function setGuruPembimbing(Request $request, PenempatanPKL $penempatan)
    {
        $validated = $request->validate([
            'guru_pembimbing_id' => 'required|exists:guru_pembimbing,id',
        ]);

        if ($penempatan->status !== PenempatanStatus::DITERIMA_INDUSTRI->value) {
            return back()->withErrors(['guru_pembimbing_id' => __('penempatan.errors.guru')]);
        }

        $penempatan->update([
            'guru_pembimbing_id' => $validated['guru_pembimbing_id'],
        ]);

        return back()->with('success', __('penempatan.success.guru'));
    }

    public function updateLaporanStatus(Request $request, PenempatanPKL $penempatan)
    {
        $validated = $request->validate([
            'laporan_status' => [
                'required',
                Rule::in(array_map(static fn (LaporanStatus $status) => $status->value, LaporanStatus::cases())),
            ],
        ]);

        $penempatan->update([
            'laporan_status' => $validated['laporan_status'],
        ]);

        return back()->with('success', __('penempatan.success.laporan'));
    }

}
