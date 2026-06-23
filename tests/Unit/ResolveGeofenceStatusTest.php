<?php

namespace Tests\Unit;

use App\Enums\AbsensiStatus;
use App\Models\Industri;
use App\Services\SiswaPresensiCheckInService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InvokesPrivateMethods;

class ResolveGeofenceStatusTest extends TestCase
{
    use InvokesPrivateMethods;

    public function test_path_1_inside_radius_returns_valid_location_status(): void
    {
        $industri = new Industri([
            'latitude' => 0,
            'longitude' => 0,
            'geofence_radius_m' => 200,
        ]);

        $result = $this->invokePrivate(new SiswaPresensiCheckInService(), 'resolveGeofenceStatus', [
            $industri,
            0.0,
            0.0,
        ]);

        $this->assertTrue($result['is_within_geofence']);
        $this->assertSame(AbsensiStatus::HADIR_VALID_LOKASI->value, $result['status']);
        $this->assertNull($result['approval_status']);
    }

    public function test_path_2_radius_lower_than_ten_uses_minimum_radius(): void
    {
        $industri = new Industri([
            'latitude' => 0,
            'longitude' => 0,
            'geofence_radius_m' => 1,
        ]);

        $result = $this->invokePrivate(new SiswaPresensiCheckInService(), 'resolveGeofenceStatus', [
            $industri,
            0.0,
            0.00005,
        ]);

        $this->assertTrue($result['is_within_geofence']);
        $this->assertSame(AbsensiStatus::HADIR_VALID_LOKASI->value, $result['status']);
    }

    public function test_path_3_outside_radius_returns_pending_status(): void
    {
        $industri = new Industri([
            'latitude' => 0,
            'longitude' => 0,
            'geofence_radius_m' => 10,
        ]);

        $result = $this->invokePrivate(new SiswaPresensiCheckInService(), 'resolveGeofenceStatus', [
            $industri,
            0.0,
            0.001,
        ]);

        $this->assertFalse($result['is_within_geofence']);
        $this->assertSame(AbsensiStatus::MENUNGGU_PERSETUJUAN_LUAR_LOKASI->value, $result['status']);
        $this->assertSame('menunggu', $result['approval_status']);
    }
}
