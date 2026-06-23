<?php

namespace Tests\Feature;

use App\Enums\AbsensiStatus;
use App\Enums\PenempatanStatus;
use App\Http\Controllers\Siswa\SiswaPresensiController;
use App\Models\AbsensiPkl;
use App\Models\Industri;
use App\Models\Jurusan;
use App\Models\PenempatanPKL;
use App\Models\Siswa;
use App\Models\User;
use App\Notifications\SystemDatabaseNotification;
use App\Services\AppNotificationService;
use App\Services\SiswaPresensiCheckInService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\CreatesControllerRequests;
use Tests\Support\UsesSafeTestingDatabase;
use Tests\TestCase;

class StorePresensiIntegrationTest extends TestCase
{
    use CreatesControllerRequests;
    use DatabaseTransactions;
    use UsesSafeTestingDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_path_1_user_without_siswa_data_gets_access_error(): void
    {
        $this->assertUsingSafeTestingDatabase();

        $request = $this->requestWithValidatedData([], $this->fakeUser(null));

        $this->expectException(HttpException::class);
        app(SiswaPresensiController::class)->store(
            $request,
            app(SiswaPresensiCheckInService::class),
            app(AppNotificationService::class)
        );
    }

    public function test_path_2_existing_daily_absensi_returns_error_and_does_not_create_duplicate(): void
    {
        $this->assertUsingSafeTestingDatabase();
        Carbon::setTestNow(Carbon::parse('2026-05-18 08:00:00'));

        [$siswa, $industri] = $this->createAcceptedSiswaPlacement();
        AbsensiPkl::create([
            'siswa_id' => $siswa->id,
            'industri_id' => $industri->id,
            'tanggal' => '2026-05-18',
            'check_in_at' => Carbon::parse('2026-05-18 07:30:00'),
            'latitude' => $industri->latitude,
            'longitude' => $industri->longitude,
            'distance_to_industri_m' => 0,
            'is_within_geofence' => true,
            'status' => AbsensiStatus::HADIR_VALID_LOKASI->value,
        ]);
        $request = $this->requestWithValidatedData([
            'latitude' => $industri->latitude,
            'longitude' => $industri->longitude,
            'accuracy_m' => null,
            'catatan' => null,
        ], $this->fakeUser($siswa));

        $response = app(SiswaPresensiController::class)->store(
            $request,
            app(SiswaPresensiCheckInService::class),
            app(AppNotificationService::class)
        );

        $this->assertTrue($response->isRedirect());
        $this->assertTrue(session('errors')->has('presensi'));
        $this->assertSame(1, AbsensiPkl::where('siswa_id', $siswa->id)->whereDate('tanggal', '2026-05-18')->count());
    }

    public function test_path_3_valid_location_creates_absensi_without_industry_notification(): void
    {
        $this->assertUsingSafeTestingDatabase();
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-05-18 08:00:00'));

        [$siswa, $industri] = $this->createAcceptedSiswaPlacement();
        $request = $this->requestWithValidatedData([
            'latitude' => $industri->latitude,
            'longitude' => $industri->longitude,
            'accuracy_m' => 12,
            'catatan' => null,
        ], $this->fakeUser($siswa));

        $response = app(SiswaPresensiController::class)->store(
            $request,
            app(SiswaPresensiCheckInService::class),
            app(AppNotificationService::class)
        );

        $this->assertTrue($response->isRedirect());
        $this->assertNotNull(session('success'));
        $this->assertDatabaseHas('absensi_pkl', [
            'siswa_id' => $siswa->id,
            'industri_id' => $industri->id,
            'tanggal' => '2026-05-18',
            'status' => AbsensiStatus::HADIR_VALID_LOKASI->value,
            'is_within_geofence' => true,
        ]);
        Notification::assertNothingSent();
    }

    public function test_path_4_outside_location_creates_pending_absensi_and_sends_industry_notification(): void
    {
        $this->assertUsingSafeTestingDatabase();
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-05-18 08:00:00'));

        [$siswa, $industri, $industriUser] = $this->createAcceptedSiswaPlacement();
        $request = $this->requestWithValidatedData([
            'latitude' => -7.9900000,
            'longitude' => 112.6500000,
            'accuracy_m' => 10,
            'catatan' => 'Kegiatan di luar lokasi industri',
        ], $this->fakeUser($siswa));

        $response = app(SiswaPresensiController::class)->store(
            $request,
            app(SiswaPresensiCheckInService::class),
            app(AppNotificationService::class)
        );

        $this->assertTrue($response->isRedirect());
        $this->assertNotNull(session('success'));
        $this->assertDatabaseHas('absensi_pkl', [
            'siswa_id' => $siswa->id,
            'industri_id' => $industri->id,
            'tanggal' => '2026-05-18',
            'status' => AbsensiStatus::MENUNGGU_PERSETUJUAN_LUAR_LOKASI->value,
            'approval_status' => 'menunggu',
            'is_within_geofence' => false,
        ]);
        Notification::assertSentTo($industriUser, SystemDatabaseNotification::class);
    }

    private function createAcceptedSiswaPlacement(): array
    {
        $jurusan = Jurusan::create(['nama' => 'RPL Presensi Integrasi ' . uniqid()]);
        $siswaUser = User::factory()->create();
        $industriUser = User::factory()->create();
        $siswa = Siswa::create([
            'user_id' => $siswaUser->id,
            'nis' => 'PRS' . uniqid(),
            'jurusan_id' => $jurusan->id,
            'kelas' => 'XI RPL 1',
            'nilai_akademik' => 85,
            'perangkat' => 80,
            'status_pkl' => 'berjalan',
            'tahun_ajaran' => '2025/2026',
        ]);
        $industri = Industri::create([
            'user_id' => $industriUser->id,
            'nama_industri' => 'Industri Presensi ' . uniqid(),
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

        return [$siswa, $industri, $industriUser];
    }
}
