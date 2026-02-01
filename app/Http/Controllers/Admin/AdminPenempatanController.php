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
use App\Models\UsulanIndustri;
use App\Models\User;
use App\Notifications\PenempatanLangsungAssigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

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

        $statusList = [
            'all' => 'Semua Status',
            'belum_memilih' => 'Belum Memilih',
            'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
            'ditolak_sekolah' => 'Ditolak Sekolah',
            'proses_pengajuan' => 'Proses Pengajuan',
            'pengajuan_ditolak_industri' => 'Pengajuan Ditolak Industri',
            'proses_wawancara' => 'Proses Wawancara',
            'diterima_industri' => 'Diterima Industri',
            'tidak_lolos_industri' => 'Tidak Lolos Industri',
        ];

        $statusLabels = [
            'belum_memilih' => 'Belum memilih',
            'menunggu_konfirmasi' => 'Menunggu konfirmasi',
            'ditolak_sekolah' => 'Ditolak sekolah',
            'proses_pengajuan' => 'Proses pengajuan',
            'pengajuan_ditolak_industri' => 'Pengajuan ditolak industri',
            'proses_wawancara' => 'Proses wawancara',
            'diterima_industri' => 'Diterima industri',
            'tidak_lolos_industri' => 'Tidak lolos industri',
        ];

        $pilihanLabels = [
            'rekomendasi' => 'Rekomendasi',
            'usulan_lain' => 'Usulan Lain',
            'langsung' => 'Penempatan Langsung',
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
            'usulanIndustri',
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
            'diterima_industri' => $penempatanData->where('status', 'diterima_industri')->count(),
            'proses_pengajuan' => $penempatanData->where('status', 'proses_pengajuan')->count(),
            'menunggu_konfirmasi' => $penempatanData->where('status', 'menunggu_konfirmasi')->count(),
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
            'usulanList' => $usulanList,
            'totalBobot' => $totalBobot,
            'isBobotValid' => $isBobotValid,
            'guruOptions' => $guruOptions,
            'siswaOptions' => $siswaOptions,
            'industriOptions' => $industriOptions,
        ]);
    }

    public function penempatanLangsung(Request $request)
    {
        $validated = $request->validate([
            'siswa_id' => 'required|exists:siswa,id',
            'industri_id' => 'required|exists:industri,id',
            'mode' => 'required|in:industri,sekolah',
            'alasan' => 'required|string|max:1000',
        ]);

        $siswa = Siswa::find($validated['siswa_id']);
        $industri = Industri::find($validated['industri_id']);

        if (!$siswa || !$industri) {
            return back()->withErrors(['penempatan' => 'Data siswa atau industri tidak valid.']);
        }

        $status = $validated['mode'] === 'sekolah' ? 'diterima_industri' : 'proses_pengajuan';

        $existingPenempatan = PenempatanPKL::where('siswa_id', $siswa->id)->first();
        $oldStatus = $existingPenempatan?->status;

        $penempatan = PenempatanPKL::updateOrCreate(
            ['siswa_id' => $siswa->id],
            [
                'industri_id' => $industri->id,
                'usulan_industri_id' => null,
                'pilihan_siswa' => 'langsung',
                'status' => $status,
                'jenis_penempatan' => 'langsung',
                'alasan_penempatan_langsung' => $validated['alasan'],
                'ditetapkan_oleh' => $request->user()->id,
                'ditetapkan_at' => now(),
            ]
        );

        if ($oldStatus !== null) {
            $this->handlePenempatanStatusChange($penempatan, $oldStatus);
        }

        if ($status === 'proses_pengajuan' && !$industri->status_pengajuan) {
            $industri->update([
                'status_pengajuan' => 'menunggu',
                'pengajuan_dikirim_at' => now(),
            ]);
        }

        if ($status === 'diterima_industri') {
            $siswa->update(['status_pkl' => 'berjalan']);
        }

        $siswa->user?->notify(new PenempatanLangsungAssigned($penempatan));
        $penempatan->guruPembimbing?->user?->notify(new PenempatanLangsungAssigned($penempatan));

        return back()->with('success', 'Penempatan langsung berhasil ditetapkan.');
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
                        || in_array($existingPenempatan->status, ['belum_memilih', 'ditolak_sekolah', 'pengajuan_ditolak_industri', 'tidak_lolos_industri'], true);

                    if ($shouldUpdate) {
                        $oldStatus = $existingPenempatan?->status;

                        $penempatan = PenempatanPKL::updateOrCreate(
                            ['siswa_id' => $siswa->id],
                            [
                                'industri_id' => null,
                                'usulan_industri_id' => null,
                                'pilihan_siswa' => null,
                                'status' => 'belum_memilih',
                            ]
                        );

                        if ($oldStatus !== null) {
                            $this->handlePenempatanStatusChange($penempatan, $oldStatus);
                        }
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

    public function approveUsulanIndustri(Request $request, UsulanIndustri $usulan)
    {
        if ($usulan->status !== 'menunggu') {
            return back()->withErrors(['usulan' => 'Usulan ini sudah diproses.']);
        }

        if (User::where('email', $usulan->email)->exists()) {
            return back()->withErrors(['usulan' => 'Email industri sudah digunakan.']);
        }

        if (Industri::where('nama_industri', $usulan->nama_industri)->exists()) {
            return back()->withErrors(['usulan' => 'Nama industri sudah terdaftar.']);
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
            'status_pengajuan' => 'menunggu',
            'pengajuan_dikirim_at' => now(),
        ]);

        $usulan->update([
            'status' => 'disetujui',
        ]);

        $existingPenempatan = PenempatanPKL::where('siswa_id', $usulan->siswa_id)->first();
        $oldStatus = $existingPenempatan?->status;

        $penempatan = PenempatanPKL::updateOrCreate(
            ['siswa_id' => $usulan->siswa_id],
            [
                'industri_id' => $industri->id,
                'usulan_industri_id' => $usulan->id,
                'pilihan_siswa' => 'usulan_lain',
                'status' => 'proses_pengajuan',
            ]
        );

        if ($oldStatus !== null) {
            $this->handlePenempatanStatusChange($penempatan, $oldStatus);
        }

        return back()->with('success', 'Usulan industri disetujui dan akun industri dibuat.');
    }

    public function rejectUsulanIndustri(Request $request, UsulanIndustri $usulan)
    {
        if ($usulan->status !== 'menunggu') {
            return back()->withErrors(['usulan' => 'Usulan ini sudah diproses.']);
        }

        $usulan->update([
            'status' => 'ditolak',
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
                'status' => 'ditolak_sekolah',
            ]);
            $this->handlePenempatanStatusChange($penempatan, $oldStatus);
        }

        return back()->with('success', 'Usulan industri ditolak. Siswa perlu memilih ulang.');
    }

    public function confirmPilihan(PenempatanPKL $penempatan)
    {
        if ($penempatan->status !== 'menunggu_konfirmasi') {
            return back()->withErrors(['penempatan' => 'Penempatan ini tidak dalam status menunggu konfirmasi.']);
        }

        if ($penempatan->pilihan_siswa === 'rekomendasi') {
            $industri = $penempatan->industri;
            if (!$industri) {
                return back()->withErrors(['penempatan' => 'Industri rekomendasi belum tersedia.']);
            }

            $industri->update([
                'status_pengajuan' => 'menunggu',
                'pengajuan_dikirim_at' => now(),
                'pengajuan_dijawab_at' => null,
            ]);

            $oldStatus = $penempatan->status;
            $penempatan->update([
                'status' => 'proses_pengajuan',
            ]);
            $this->handlePenempatanStatusChange($penempatan, $oldStatus);

            return back()->with('success', 'Pilihan siswa dikonfirmasi. Pengajuan ke industri dikirim.');
        }

        if ($penempatan->pilihan_siswa === 'usulan_lain') {
            $usulan = $penempatan->usulanIndustri;
            if (!$usulan) {
                return back()->withErrors(['penempatan' => 'Usulan industri tidak ditemukan.']);
            }

            if ($usulan->status !== 'menunggu') {
                return back()->withErrors(['penempatan' => 'Usulan industri sudah diproses.']);
            }

            if (User::where('email', $usulan->email)->exists()) {
                return back()->withErrors(['penempatan' => 'Email industri sudah digunakan.']);
            }

            if (Industri::where('nama_industri', $usulan->nama_industri)->exists()) {
                return back()->withErrors(['penempatan' => 'Nama industri sudah terdaftar.']);
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
                'status_pengajuan' => 'menunggu',
                'pengajuan_dikirim_at' => now(),
            ]);

            $usulan->update([
                'status' => 'disetujui',
            ]);

            $oldStatus = $penempatan->status;
            $penempatan->update([
                'industri_id' => $industri->id,
                'status' => 'proses_pengajuan',
            ]);
            $this->handlePenempatanStatusChange($penempatan, $oldStatus);

            return back()->with('success', 'Usulan siswa dikonfirmasi. Akun industri dibuat dan pengajuan dikirim.');
        }

        return back()->withErrors(['penempatan' => 'Pilihan siswa belum tersedia.']);
    }

    public function rejectPilihan(PenempatanPKL $penempatan)
    {
        if ($penempatan->status !== 'menunggu_konfirmasi') {
            return back()->withErrors(['penempatan' => 'Penempatan ini tidak dalam status menunggu konfirmasi.']);
        }

        if ($penempatan->pilihan_siswa === 'usulan_lain' && $penempatan->usulanIndustri) {
            $penempatan->usulanIndustri->update([
                'status' => 'ditolak',
            ]);
        }

        $oldStatus = $penempatan->status;
        $penempatan->update([
            'industri_id' => null,
            'usulan_industri_id' => null,
            'pilihan_siswa' => null,
            'status' => 'ditolak_sekolah',
        ]);
        $this->handlePenempatanStatusChange($penempatan, $oldStatus);

        return back()->with('success', 'Pilihan siswa ditolak. Siswa dapat memilih ulang.');
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

    public function updateLaporanStatus(Request $request, PenempatanPKL $penempatan)
    {
        $validated = $request->validate([
            'laporan_status' => 'required|in:menunggu,ditindak,selesai',
        ]);

        $penempatan->update([
            'laporan_status' => $validated['laporan_status'],
        ]);

        return back()->with('success', 'Status laporan berhasil diperbarui.');
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
