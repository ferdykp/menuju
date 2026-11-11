<?php

namespace App\Notifications;

use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Workspace $workspace,
        private readonly string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/invite/'.$this->token);

        return (new MailMessage)
            ->subject('Undangan bergabung ke workspace MENUJU')
            ->greeting('Halo!')
            ->line('Kamu diundang untuk menyiapkan hari spesial bersama di workspace “'.$this->workspace->name.'”.')
            ->action('Terima undangan', $url)
            ->line('Tautan ini berlaku selama tujuh hari dan hanya dapat diterima oleh alamat email tujuan.');
    }
}
