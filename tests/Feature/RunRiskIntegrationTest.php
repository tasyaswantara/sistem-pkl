<?php

namespace Tests\Feature;

use App\Enums\AbsensiStatus;
use App\Enums\LogbookStatus;
use App\Enums\PenempatanStatus;
use App\Enums\PerizinanStatus;
use App\Http\Controllers\Admin\AdminPeringatanDiniController;
use App\Models\AbsensiPkl;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\Logbook;
use App\Models\PenempatanPKL;
use App\Models\Perizinan;
use App\Models\RiskScore;
use App\Models\Siswa;
use App\Models\User;
use App\Services\AdminPeringatanDiniService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CreatesControllerRequests;
use Tests\Support\UsesSafeTestingDatabase;
use Tests\TestCase;

class RunRiskIntegrationTest extends TestCase
{
    use CreatesControllerRequests;
    use DatabaseTransactions;
    use UsesSafeTestingDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_path_1_end_date_before_start_date_returns_error_without_creating_risk_score(): void
    {
        $this->assertUsingSafeTestingDatabase();
        $riskScoreCountBefore = RiskScore::count();

        $request = $this->requestWithInput([
            'week_start' => '2026-05-20',
            'week_end' => '2026-05-19',
        ]);

        $response = app(AdminPeringatanDiniController::class)->runRisk(
            $request,
            app(AdminPeringatanDiniService::class)
        );

        $this->assertTrue($response->isRedirect());
        $this->assertTrue(session('errors')->has('week_end'));
        $this->assertSame($riskScoreCountBefore, RiskScore::count());
    }

    public function test_path_2_end_date_in_future_returns_error_without_creating_risk_score(): void
    {
        $this->assertUsingSafeTestingDatabase();
        Carbon::setTestNow(Carbon::parse('2026-05-29 12:00:00'));
        $riskScoreCountBefore = RiskScore::count();

        $request = $this->requestWithInput([
            'week_start' => '2026-05-20',
            'week_end' => '2026-05-30',
        ]);

        $response = app(AdminPeringatanDiniController::class)->runRisk(
            $request,
            app(AdminPeringatanDiniService::class)
        );

        $this->assertTrue($response->isRedirect());
        $this->assertTrue(session('errors')->has('week_end'));
        $this->assertSame($riskScoreCountBefore, RiskScore::count());
    }
    public function test_path_3_empty_period_uses_default_dates_and_creates_risk_score(): void
    {
        $this->assertUsingSafeTestingDatabase();

        Notification::fake();

        Carbon::setTestNow(Carbon::parse('2026-05-29 12:00:00'));

        [$siswa] = $this->createRiskCalculationData();

        $request = $this->requestWithInput([]);

        $response = app(AdminPeringatanDiniController::class)->runRisk(
            $request,
            app(AdminPeringatanDiniService::class)
        );

        $this->assertTrue($response->isRedirect());
        $this->assertNotNull(session('success'));

        $this->assertDatabaseHas('risk_scores', [
            'siswa_id' => $siswa->id,
            'week_start' => '2026-05-23',
            'week_end' => '2026-05-29',
        ]);
    }
    public function test_path_4_only_start_date_uses_default_end_date_and_creates_risk_score(): void
{
    $this->assertUsingSafeTestingDatabase();

    Notification::fake();

    Carbon::setTestNow(Carbon::parse('2026-05-29 12:00:00'));

    [$siswa] = $this->createRiskCalculationData();

    $request = $this->requestWithInput([
        'week_start' => '2026-05-25',
        'tahun_ajaran' => '2025/2026',
    ]);

    $response = app(AdminPeringatanDiniController::class)->runRisk(
        $request,
        app(AdminPeringatanDiniService::class)
    );

    $this->assertTrue($response->isRedirect());

    $this->assertDatabaseHas('risk_scores', [
        'siswa_id' => $siswa->id,
        'week_start' => '2026-05-25',
        'week_end' => '2026-05-29',
    ]);
}
    public function test_path_5_only_end_date_uses_default_start_date_and_creates_risk_score(): void
{
    $this->assertUsingSafeTestingDatabase();

    Notification::fake();

    Carbon::setTestNow(Carbon::parse('2026-05-29 12:00:00'));

    [$siswa] = $this->createRiskCalculationData();

    $request = $this->requestWithInput([
        'week_end' => '2026-05-28',
        'tahun_ajaran' => '2025/2026',
    ]);

    $response = app(AdminPeringatanDiniController::class)->runRisk(
        $request,
        app(AdminPeringatanDiniService::class)
    );

    $this->assertTrue($response->isRedirect());

    $this->assertDatabaseHas('risk_scores', [
        'siswa_id' => $siswa->id,
        'week_start' => '2026-05-23',
        'week_end' => '2026-05-28',
    ]);
}
    public function test_path_6_valid_complete_period_reads_monitoring_data_and_stores_risk_score(): void
    {
        $this->assertUsingSafeTestingDatabase();
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-05-29 12:00:00'));

        [$siswa] = $this->createRiskCalculationData();
        $request = $this->requestWithInput([
            'week_start' => '2026-05-18',
            'week_end' => '2026-05-22',
            'tahun_ajaran' => '2025/2026',
        ]);

        $response = app(AdminPeringatanDiniController::class)->runRisk(
            $request,
            app(AdminPeringatanDiniService::class)
        );

        $this->assertTrue($response->isRedirect());
        $this->assertNotNull(session('success'));
        $this->assertDatabaseHas('risk_scores', [
            'siswa_id' => $siswa->id,
            'week_start' => '2026-05-18',
            'week_end' => '2026-05-22',
            'category' => 'rendah',
        ]);
        $this->assertSame(1, RiskScore::where('siswa_id', $siswa->id)->whereDate('week_start', '2026-05-18')->count());
        Notification::assertNothingSent();
    }

    private function createRiskCalculationData(): array
    {
        $jurusan = Jurusan::create(['nama' => 'RPL Risk Integrasi ' . uniqid()]);
        $siswaUser = User::factory()->create();
        $industriUser = User::factory()->create();
        $siswa = Siswa::create([
            'user_id' => $siswaUser->id,
            'nis' => 'RSK' . uniqid(),
            'jurusan_id' => $jurusan->id,
            'kelas' => 'XI RPL 1',
            'nilai_akademik' => 85,
            'perangkat' => 80,
            'status_pkl' => 'berjalan',
            'tahun_ajaran' => '2025/2026',
        ]);
        $industri = Industri::create([
            'user_id' => $industriUser->id,
            'nama_industri' => 'Industri Risk ' . uniqid(),
            'kapasitas' => 10,
            'alamat' => 'Malang',
            'latitude' => -7.9500000,
            'longitude' => 112.6100000,
            'geofence_radius_m' => 200,
            'reputasi' => 80,
            'jurusan_id' => $jurusan->id,
            'grade' => 'B',
        ]);
        PenempatanPKL::create([
            'siswa_id' => $siswa->id,
            'industri_id' => $industri->id,
            'status' => PenempatanStatus::DITERIMA_INDUSTRI->value,
            'laporan_status' => 'selesai',
        ]);

        foreach (['2026-05-18', '2026-05-19', '2026-05-20', '2026-05-21', '2026-05-22'] as $date) {
            Logbook::create([
                'siswa_id' => $siswa->id,
                'industri_id' => $industri->id,
                'tanggal' => $date,
                'aktivitas' => 'Aktivitas PKL ' . $date,
                'status_validasi' => LogbookStatus::DISETUJUI->value,
            ]);
        }

        foreach (['2026-05-18', '2026-05-19', '2026-05-20', '2026-05-21'] as $date) {
            AbsensiPkl::create([
                'siswa_id' => $siswa->id,
                'industri_id' => $industri->id,
                'tanggal' => $date,
                'check_in_at' => Carbon::parse($date . ' 08:00:00'),
                'latitude' => $industri->latitude,
                'longitude' => $industri->longitude,
                'distance_to_industri_m' => 0,
                'is_within_geofence' => true,
                'status' => AbsensiStatus::HADIR_VALID_LOKASI->value,
            ]);
        }

        Perizinan::create([
            'siswa_id' => $siswa->id,
            'industri_id' => $industri->id,
            'created_by' => $siswaUser->id,
            'jenis_izin' => 'Izin Kegiatan Sekolah',
            'tanggal_mulai' => '2026-05-22',
            'tanggal_selesai' => '2026-05-22',
            'status' => PerizinanStatus::DISETUJUI->value,
        ]);

        return [$siswa, $industri];
    }
}
