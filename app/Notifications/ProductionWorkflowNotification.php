<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductionWorkflowNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        private readonly string $event,
        private readonly string $message,
        private readonly array $details = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'message' => $this->message,
            'details' => $this->details,
        ];
    }
}
