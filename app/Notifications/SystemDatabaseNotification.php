<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemDatabaseNotification extends Notification
{
    use Queueable;

    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        private string $title,
        private string $body,
        private ?string $url = null,
        private array $meta = []
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return array_merge($this->meta, [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ]);
    }
}
