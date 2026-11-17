<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * An operational alert or job notice for staff, supervisors and head office:
 * shown in the app's notification list and, for alerts, emailed.
 */
class OperationsAlert extends Notification
{
    use Queueable;

    /**
     * @param  string  $kind  assigned | escalated | missed_shift | info
     */
    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public string $kind = 'info',
        public bool $email = true,
    ) {
    }

    public function via(object $notifiable): array
    {
        return $this->email && !empty($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello ' . strtok((string) $notifiable->name, ' ') . ',')
            ->line($this->body);

        return $this->url ? $mail->action('Open in BookTala', $this->url) : $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'kind' => $this->kind,
        ];
    }
}
