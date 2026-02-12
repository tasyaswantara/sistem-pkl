<?php

namespace App\Services;

use App\Enums\PenempatanStatus;
use App\Models\PenempatanPKL;
use App\Models\User;
use App\Notifications\PenempatanStatusChanged;
use Illuminate\Support\Facades\Notification;

class PenempatanStatusService
{
    public function handleStatusChange(PenempatanPKL $penempatan, ?string $oldStatus): void
    {
        if ($oldStatus === null) {
            return;
        }

        $newStatus = (string) $penempatan->status;
        if ($oldStatus === $newStatus) {
            return;
        }

        $admins = User::role('admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new PenempatanStatusChanged($penempatan, (string) $oldStatus, $newStatus));
        }

        if ($newStatus === PenempatanStatus::DITERIMA_INDUSTRI->value) {
            $penempatan->siswa?->update(['status_pkl' => 'berjalan']);
        }
    }
}
