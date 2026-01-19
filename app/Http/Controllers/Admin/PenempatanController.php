<?php

namespace App\Http\Controllers\Admin;

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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PenempatanController extends Controller
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

        $statusList = [
            'all' => 'Semua Status',
            'belum_diproses' => 'Belum Diproses',
            'proses_pengajuan' => 'Proses Pengajuan',
            'diterima_industri' => 'Diterima Industri',
            'ditolak_industri' => 'Ditolak Industri',
        ];

        $statusLabels = [
            'belum_diproses' => 'Belum Diproses',
            'proses_pengajuan' => 'Proses Pengajuan',
            'diterima_industri' => 'Diterima Industri',
            'ditolak_industri' => 'Ditolak Industri',
        ];

        $pilihanLabels = [
            'rekomendasi' => 'Rekomendasi',
            'usulan_lain' => 'Usulan Lain',
        ];

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

        $penempatanQuery = PenempatanPKL::with([
            'siswa.user',
            'siswa.jurusan',
            'industri',
            'guruPembimbing.user',
        ]);

        if ($selectedJurusan) {
            $penempatanQuery->whereHas('siswa', function ($query) use ($selectedJurusan) {
                $query->where('jurusan_id', $selectedJurusan);
            });
        }

        if ($selectedTahun) {
            $penempatanQuery->whereHas('siswa', function ($query) use ($selectedTahun) {
                $query->where('tahun_ajaran', $selectedTahun);
            });
        }

        if ($selectedStatus && $selectedStatus !== 'all') {
            $penempatanQuery->where('status', $selectedStatus);
        }

        if ($search) {
            $penempatanQuery->whereHas('siswa.user', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        $penempatanData = $penempatanQuery->orderByDesc('id')->get();

        $guruOptions = GuruPembimbing::with('user', 'jurusan')
            ->orderBy('id')
            ->get()
            ->groupBy('jurusan_id');

        $statusCounts = [
            'diterima_industri' => $penempatanData->where('status', 'diterima_industri')->count(),
            'proses_pengajuan' => $penempatanData->where('status', 'proses_pengajuan')->count(),
            'ditolak_industri' => $penempatanData->where('status', 'ditolak_industri')->count(),
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

        return view('admin.penempatan.index', [
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
            'totalBobot' => $totalBobot,
            'isBobotValid' => $isBobotValid,
            'guruOptions' => $guruOptions,
        ]);
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
            return back()->withErrors(['bobot' => 'Total bobot harus 100%.'])->withInput();
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

        return back()->with('success', 'Bobot SAW berhasil disimpan.');
    }

    public function runSaw(Request $request)
    {
        $validated = $request->validate([
            'jurusan_id' => 'required|exists:jurusan,id',
            'tahun_ajaran' => 'required|string',
        ]);

        $jurusanId = $validated['jurusan_id'];
        $tahunAjaran = $validated['tahun_ajaran'];

        $bobotKriteria = BobotKriteria::with('kriteria')
            ->where('jurusan_id', $jurusanId)
            ->get();

        if ($bobotKriteria->isEmpty()) {
            return back()->withErrors(['bobot' => 'Bobot kriteria belum diatur untuk jurusan ini.']);
        }

        $totalBobot = $bobotKriteria->sum('bobot');
        if (abs($totalBobot - 1) > 0.02) {
            return back()->withErrors(['bobot' => 'Total bobot harus 100% sebelum menjalankan SAW.']);
        }

        $siswaList = Siswa::where('jurusan_id', $jurusanId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->get();

        $industriList = Industri::where('jurusan_id', $jurusanId)->get();

        if ($siswaList->isEmpty() || $industriList->isEmpty()) {
            return back()->withErrors(['saw' => 'Data siswa atau industri belum lengkap untuk jurusan dan tahun ajaran ini.']);
        }

        $kriteriaEntries = $bobotKriteria->map(function ($item) {
            $map = $this->resolveKriteriaMap($item->kriteria->nama_kriteria);

            return [
                'bobot' => $item->bobot,
                'tipe' => $item->kriteria->tipe,
                'source' => $map['source'] ?? null,
                'field' => $map['field'] ?? null,
            ];
        })->filter(function ($entry) {
            return $entry['source'] !== null;
        })->values();

        if ($kriteriaEntries->isEmpty()) {
            return back()->withErrors(['saw' => 'Kriteria belum terhubung dengan data siswa/industri.']);
        }

        $normalisasiSiswa = [];
        foreach ($kriteriaEntries as $entry) {
            if ($entry['source'] !== 'siswa') {
                continue;
            }

            $values = $siswaList->pluck($entry['field'])->filter(function ($value) {
                return $value !== null;
            });

            $normalisasiSiswa[$entry['field']] = [
                'max' => $values->max() ?: 0,
                'min' => $values->min() ?: 0,
            ];
        }

        $industriesByGrade = $industriList->groupBy('grade');
        $normalisasiIndustriByGrade = [];
        foreach ($industriesByGrade as $grade => $list) {
            foreach ($kriteriaEntries as $entry) {
                if ($entry['source'] !== 'industri') {
                    continue;
                }

                $values = $list->pluck($entry['field'])->filter(function ($value) {
                    return $value !== null;
                });

                $normalisasiIndustriByGrade[$grade][$entry['field']] = [
                    'max' => $values->max() ?: 0,
                    'min' => $values->min() ?: 0,
                ];
            }
        }

        $now = now();
        $rowsCount = 0;

        DB::transaction(function () use (
            $jurusanId,
            $tahunAjaran,
            $kriteriaEntries,
            $normalisasiSiswa,
            $normalisasiIndustriByGrade,
            $industriesByGrade,
            $siswaList,
            $industriList,
            $now,
            &$rowsCount
        ) {
            $run = SawRun::create([
                'jurusan_id' => $jurusanId,
                'tahun_ajaran' => $tahunAjaran,
                'run_at' => $now,
                'created_by' => auth()->id(),
            ]);

            $rows = [];
            foreach ($siswaList as $siswa) {
                $grade = $this->resolveSiswaGrade((float) $siswa->nilai_akademik);
                $poolIndustri = $industriesByGrade->get($grade, collect());
                if ($poolIndustri->isEmpty()) {
                    $poolIndustri = $industriList;
                }

                $normalisasiIndustri = $normalisasiIndustriByGrade[$grade] ?? [];
                $scores = [];
                foreach ($poolIndustri as $industri) {
                    $score = 0;
                    foreach ($kriteriaEntries as $entry) {
                        $value = $entry['source'] === 'siswa'
                            ? (float) ($siswa->{$entry['field']} ?? 0)
                            : (float) ($industri->{$entry['field']} ?? 0);

                        if ($entry['source'] === 'siswa') {
                            $max = $normalisasiSiswa[$entry['field']]['max'] ?? 0;
                            $min = $normalisasiSiswa[$entry['field']]['min'] ?? 0;
                        } else {
                            $max = $normalisasiIndustri[$entry['field']]['max'] ?? 0;
                            $min = $normalisasiIndustri[$entry['field']]['min'] ?? 0;
                        }

                        if ($max <= 0) {
                            $normalized = 0;
                        } elseif ($entry['tipe'] === 'cost') {
                            $normalized = $value > 0 ? ($min / $value) : 0;
                        } else {
                            $normalized = $value / $max;
                        }

                        $score += $normalized * $entry['bobot'];
                    }

                    $scores[] = [
                        'industri_id' => $industri->id,
                        'nilai_preferensi' => $score,
                    ];
                }

                usort($scores, function ($a, $b) {
                    return $b['nilai_preferensi'] <=> $a['nilai_preferensi'];
                });

                $topChoice = $scores[0] ?? null;
                if ($topChoice) {
                    $existingPenempatan = PenempatanPKL::where('siswa_id', $siswa->id)->first();
                    $shouldUpdate = !$existingPenempatan
                        || in_array($existingPenempatan->status, ['belum_diproses', 'ditolak_industri'], true);

                    if ($shouldUpdate) {
                        PenempatanPKL::updateOrCreate(
                            ['siswa_id' => $siswa->id],
                            [
                                'industri_id' => $topChoice['industri_id'],
                                'pilihan_siswa' => 'rekomendasi',
                                'status' => 'belum_diproses',
                            ]
                        );
                    }
                }

                $rank = 1;
                foreach ($scores as $score) {
                    $rows[] = [
                        'saw_run_id' => $run->id,
                        'siswa_id' => $siswa->id,
                        'industri_id' => $score['industri_id'],
                        'nilai_preferensi' => $score['nilai_preferensi'],
                        'peringkat' => $rank,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $rank++;
                }
            }

            if ($rows) {
                HasilRekomendasi::insert($rows);
                $rowsCount = count($rows);
            }
        });

        Log::info('SAW run completed', [
            'jurusan_id' => $jurusanId,
            'tahun_ajaran' => $tahunAjaran,
            'siswa' => $siswaList->count(),
            'industri' => $industriList->count(),
            'rows_inserted' => $rowsCount,
        ]);

        return back()->with('success', 'Perhitungan SAW berhasil dijalankan. Hasil: ' . $rowsCount . ' rekomendasi disimpan.');
    }

    public function setGuruPembimbing(Request $request, PenempatanPKL $penempatan)
    {
        $validated = $request->validate([
            'guru_pembimbing_id' => 'required|exists:guru_pembimbing,id',
        ]);

        if ($penempatan->status !== 'diterima_industri') {
            return back()->withErrors(['guru_pembimbing_id' => 'Guru pembimbing hanya bisa ditentukan setelah industri menerima.']);
        }

        $penempatan->update([
            'guru_pembimbing_id' => $validated['guru_pembimbing_id'],
        ]);

        return back()->with('success', 'Guru pembimbing berhasil ditetapkan.');
    }

    private function resolveKriteriaMap(string $nama): ?array
    {
        $nama = strtolower($nama);

        if (str_contains($nama, 'nilai') && str_contains($nama, 'akademik')) {
            return ['source' => 'siswa', 'field' => 'nilai_akademik'];
        }

        if (str_contains($nama, 'perangkat')) {
            return ['source' => 'siswa', 'field' => 'perangkat'];
        }

        if (str_contains($nama, 'kapasitas')) {
            return ['source' => 'industri', 'field' => 'kapasitas'];
        }

        return null;
    }

    private function resolveSiswaGrade(float $nilai): string
    {
        if ($nilai >= 90) {
            return 'A';
        }

        if ($nilai >= 80) {
            return 'B';
        }

        return 'C';
    }
}
