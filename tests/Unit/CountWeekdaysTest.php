<?php

namespace Tests\Unit;

use App\Services\AdminPeringatanDiniService;
use App\Services\AppNotificationService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Support\InvokesPrivateMethods;

class CountWeekdaysTest extends TestCase
{
    use InvokesPrivateMethods;

    private function service(): AdminPeringatanDiniService
    {
        return new AdminPeringatanDiniService($this->createMock(AppNotificationService::class));
    }

    public function test_path_1_start_date_greater_than_end_date_returns_zero(): void
    {
        $result = $this->invokePrivate($this->service(), 'countWeekdays', [
            Carbon::parse('2026-05-22'),
            Carbon::parse('2026-05-18'),
        ]);

        $this->assertSame(0, $result);
    }

    public function test_path_2_only_weekend_days_are_not_counted(): void
    {
        $result = $this->invokePrivate($this->service(), 'countWeekdays', [
            Carbon::parse('2026-05-23'),
            Carbon::parse('2026-05-24'),
        ]);

        $this->assertSame(0, $result);
    }

    public function test_path_3_weekday_range_is_counted(): void
    {
        $result = $this->invokePrivate($this->service(), 'countWeekdays', [
            Carbon::parse('2026-05-18'),
            Carbon::parse('2026-05-22'),
        ]);

        $this->assertSame(5, $result);
    }
}
