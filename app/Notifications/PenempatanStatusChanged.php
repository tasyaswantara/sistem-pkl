<?php

namespace App\Notifications;

use App\Models\PenempatanPKL;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PenempatanStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        private PenempatanPKL $penempatan,
        private string $oldStatus,
        private string $newStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $siswaNama = $this->penempatan->siswa?->user?->name ?? 'Siswa';
        $industriNama = $this->penempatan->industri?->nama_industri ?? 'Industri';

        return [
            'title' => 'Status penempatan berubah',
            'body' => "{$siswaNama} → {$industriNama}: {$this->oldStatus} → {$this->newStatus}",
            'penempatan_id' => $this->penempatan->id,
        ];
    }
}
