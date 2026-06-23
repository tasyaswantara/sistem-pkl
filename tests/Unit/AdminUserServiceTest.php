<?php

namespace Tests\Unit;

use App\Services\AdminUserService;
use App\Services\AppNotificationService;
use PHPUnit\Framework\TestCase;

class AdminUserServiceTest extends TestCase
{
    public function test_get_prefill_role_maps_input(): void
    {
        $service = new AdminUserService($this->createMock(AppNotificationService::class));

        $this->assertSame('siswa', $service->getPrefillRole('Siswa'));
        $this->assertSame('guru pembimbing', $service->getPrefillRole('Guru Pembimbing'));
        $this->assertSame('perwakilan industri', $service->getPrefillRole('Perwakilan Industri'));
        $this->assertSame('admin', $service->getPrefillRole('Admin'));
        $this->assertNull($service->getPrefillRole('Semua Pengguna'));
    }
}
