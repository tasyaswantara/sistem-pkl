<?php

namespace App\Notifications;

use App\Models\PenempatanPKL;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PenempatanLangsungAssigned extends Notification
{
    use Queueable;

    public function __construct(private PenempatanPKL $penempatan)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Penempatan langsung ditetapkan',
            'message' => sprintf(
                'Siswa %s ditempatkan langsung ke %s.',
                $this->penempatan->siswa?->user?->name ?? '-',
                $this->penempatan->industri?->nama_industri ?? '-'
            ),
            'penempatan_id' => $this->penempatan->id,
            'status' => $this->penempatan->status,
            'jenis' => $this->penempatan->jenis_penempatan,
        ];
    }
}
