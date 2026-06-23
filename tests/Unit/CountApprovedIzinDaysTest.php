<?php

namespace Tests\Unit;

use App\Services\AdminPeringatanDiniService;
use App\Services\AppNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Tests\Support\InvokesPrivateMethods;

class CountApprovedIzinDaysTest extends TestCase
{
    use InvokesPrivateMethods;

    private function service(): AdminPeringatanDiniService
    {
        return new AdminPeringatanDiniService($this->createMock(AppNotificationService::class));
    }

    private function izinRow(?string $start, ?string $end): object
    {
        return (object) [
            'tanggal_mulai' => $start ? Carbon::parse($start) : null,
            'tanggal_selesai' => $end ? Carbon::parse($end) : null,
        ];
    }

    public function test_path_1_empty_perizinan_rows_returns_zero(): void
    {
        $result = $this->invokePrivate($this->service(), 'countApprovedIzinDays', [
            new Collection(),
            Carbon::parse('2026-05-18'),
            Carbon::parse('2026-05-22'),
        ]);

        $this->assertSame(0, $result);
    }

    public function test_path_2_missing_start_or_end_date_is_skipped(): void
    {
        $result = $this->invokePrivate($this->service(), 'countApprovedIzinDays', [
            new Collection([$this->izinRow(null, null)]),
            Carbon::parse('2026-05-18'),
            Carbon::parse('2026-05-22'),
        ]);

        $this->assertSame(0, $result);
    }

    public function test_path_3_start_date_before_week_start_is_clamped(): void
    {
        $result = $this->invokePrivate($this->service(), 'countApprovedIzinDays', [
            new Collection([$this->izinRow('2026-05-16', '2026-05-19')]),
            Carbon::parse('2026-05-18'),
            Carbon::parse('2026-05-22'),
        ]);

        $this->assertSame(2, $result);
    }

    public function test_path_4_end_date_after_week_end_is_clamped(): void
    {
        $result = $this->invokePrivate($this->service(), 'countApprovedIzinDays', [
            new Collection([$this->izinRow('2026-05-21', '2026-05-25')]),
            Carbon::parse('2026-05-18'),
            Carbon::parse('2026-05-22'),
        ]);

        $this->assertSame(2, $result);
    }

    public function test_path_5_weekend_dates_are_not_counted(): void
    {
        $result = $this->invokePrivate($this->service(), 'countApprovedIzinDays', [
            new Collection([$this->izinRow('2026-05-23', '2026-05-24')]),
            Carbon::parse('2026-05-18'),
            Carbon::parse('2026-05-24'),
        ]);

        $this->assertSame(0, $result);
    }

    public function test_path_6_weekday_dates_are_counted(): void
    {
        $result = $this->invokePrivate($this->service(), 'countApprovedIzinDays', [
            new Collection([$this->izinRow('2026-05-18', '2026-05-20')]),
            Carbon::parse('2026-05-18'),
            Carbon::parse('2026-05-22'),
        ]);

        $this->assertSame(3, $result);
    }

    public function test_path_7_only_weekdays_inside_week_range_are_counted(): void
    {
        $result = $this->invokePrivate($this->service(), 'countApprovedIzinDays', [
            new Collection([$this->izinRow('2026-05-16', '2026-05-24')]),
            Carbon::parse('2026-05-18'),
            Carbon::parse('2026-05-22'),
        ]);

        $this->assertSame(5, $result);
    }
}
