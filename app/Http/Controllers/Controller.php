<?php

namespace App\Http\Controllers;

use App\Models\PenempatanPKL;
use App\Models\User;
use App\Notifications\PenempatanStatusChanged;
use Illuminate\Support\Facades\Notification;

abstract class Controller
{
    protected function handlePenempatanStatusChange(PenempatanPKL $penempatan, ?string $oldStatus): void
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

        if ($newStatus === 'diterima_industri') {
            $penempatan->siswa?->update(['status_pkl' => 'berjalan']);
        }
    }
}
