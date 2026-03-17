<?php

namespace App\Services;

use App\Enums\PenempatanStatus;
use App\Enums\StatusPKL;
use App\Models\PenempatanPKL;

class PenempatanStatusService
{
    public function __construct(private AppNotificationService $notificationService)
    {
    }

    public function handleStatusChange(PenempatanPKL $penempatan, ?string $oldStatus): void
    {
        if ($oldStatus === null) {
            return;
        }

        $newStatus = (string) $penempatan->status;
        if ($oldStatus === $newStatus) {
            return;
        }

        if ($newStatus === PenempatanStatus::DITERIMA_INDUSTRI->value) {
            $penempatan->siswa?->update(['status_pkl' => StatusPKL::BERJALAN->value]);
        }

        if (in_array($newStatus, [
            PenempatanStatus::DITERIMA_INDUSTRI->value,
            PenempatanStatus::TIDAK_LOLOS_INDUSTRI->value,
        ], true)) {
            $penempatan->loadMissing(['siswa.user', 'industri']);
            $this->notificationService->notifyPenempatanDecision($penempatan);
        }
    }
}
