<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminPenempatanController;
use App\Models\BobotKriteria;
use App\Models\HasilRekomendasi;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\Kriteria;
use App\Models\PenempatanPKL;
use App\Models\SawRun;
use App\Models\Siswa;
use App\Models\User;
use App\Services\SawRunService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\CreatesControllerRequests;
use Tests\Support\UsesSafeTestingDatabase;
use Tests\TestCase;

class RunSawIntegrationTest extends TestCase
{
    use CreatesControllerRequests;
    use DatabaseTransactions;
    use UsesSafeTestingDatabase;

    public function test_path_1_missing_bobot_kriteria_returns_error_without_creating_saw_run(): void
    {
        $this->assertUsingSafeTestingDatabase();

        $admin = User::factory()->create();
        $jurusan = Jurusan::create(['nama' => 'RPL Integrasi SAW Gagal ' . uniqid()]);
        $request = $this->requestWithValidatedData([
            'jurusan_id' => $jurusan->id,
            'tahun_ajaran' => '2025/2026',
        ], $this->fakeUser(null, $admin->id));

        $response = app(AdminPenempatanController::class)->runSaw($request, app(SawRunService::class));

        $this->assertTrue($response->isRedirect());
        $this->assertTrue(session('errors')->has('bobot'));
        $this->assertDatabaseMissing('saw_runs', [
            'jurusan_id' => $jurusan->id,
            'tahun_ajaran' => '2025/2026',
        ]);
    }

    public function test_path_2_valid_data_creates_saw_run_recommendations_and_initial_penempatan(): void
    {
        $this->assertUsingSafeTestingDatabase();

        $admin = User::factory()->create();
        $jurusan = Jurusan::create(['nama' => 'RPL Integrasi SAW Berhasil ' . uniqid()]);
        $siswaUser = User::factory()->create();
        $industriUserA = User::factory()->create();
        $industriUserB = User::factory()->create();

        $siswa = Siswa::create([
            'user_id' => $siswaUser->id,
            'nis' => 'SAW' . uniqid(),
            'jurusan_id' => $jurusan->id,
            'kelas' => 'XI RPL 1',
            'nilai_akademik' => 85,
            'perangkat' => 80,
            'status_pkl' => 'belum',
            'tahun_ajaran' => '2025/2026',
        ]);

        $industriA = Industri::create([
            'user_id' => $industriUserA->id,
            'nama_industri' => 'Industri Integrasi A ' . uniqid(),
            'kapasitas' => 8,
            'alamat' => 'Malang',
            'reputasi' => 80,
            'jurusan_id' => $jurusan->id,
            'grade' => 'B',
        ]);
        $industriB = Industri::create([
            'user_id' => $industriUserB->id,
            'nama_industri' => 'Industri Integrasi B ' . uniqid(),
            'kapasitas' => 4,
            'alamat' => 'Malang',
            'reputasi' => 80,
            'jurusan_id' => $jurusan->id,
            'grade' => 'B',
        ]);

        $nilaiAkademik = Kriteria::create(['nama_kriteria' => 'Nilai Akademik', 'tipe' => 'benefit']);
        $kapasitas = Kriteria::create(['nama_kriteria' => 'Kapasitas Industri', 'tipe' => 'benefit']);
        BobotKriteria::create(['jurusan_id' => $jurusan->id, 'kriteria_id' => $nilaiAkademik->id, 'bobot' => 0.50]);
        BobotKriteria::create(['jurusan_id' => $jurusan->id, 'kriteria_id' => $kapasitas->id, 'bobot' => 0.50]);

        $request = $this->requestWithValidatedData([
            'jurusan_id' => $jurusan->id,
            'tahun_ajaran' => '2025/2026',
        ], $this->fakeUser(null, $admin->id));

        $response = app(AdminPenempatanController::class)->runSaw($request, app(SawRunService::class));

        $this->assertTrue($response->isRedirect());
        $this->assertNotNull(session('success'));
        $this->assertSame(1, SawRun::where('jurusan_id', $jurusan->id)->where('tahun_ajaran', '2025/2026')->count());
        $this->assertSame(2, HasilRekomendasi::where('siswa_id', $siswa->id)->count());
        $this->assertDatabaseHas('hasil_rekomendasi', [
            'siswa_id' => $siswa->id,
            'industri_id' => $industriA->id,
        ]);
        $this->assertDatabaseHas('hasil_rekomendasi', [
            'siswa_id' => $siswa->id,
            'industri_id' => $industriB->id,
        ]);
        $this->assertSame(
            [1, 2],
            HasilRekomendasi::where('siswa_id', $siswa->id)->orderBy('peringkat')->pluck('peringkat')->all()
        );
        $this->assertDatabaseHas('penempatan_pkl', [
            'siswa_id' => $siswa->id,
            'status' => 'belum_memilih',
        ]);
        $this->assertSame(1, PenempatanPKL::where('siswa_id', $siswa->id)->count());
    }
}
