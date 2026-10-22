<?php

namespace App\Notifications;

use App\Models\Signalement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Prévient le particulier que l'association a traité son signalement, avec sa réponse.
 * En local, MAIL_MAILER=log écrit l'email dans storage/logs/laravel.log.
 */
class SignalementTraite extends Notification
{
    use Queueable;

    public function __construct(public Signalement $signalement)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $point = $this->signalement->pointCollecte;

        return (new MailMessage)
            ->subject("Votre signalement sur {$point->nom} a été traité")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("L'association a traité votre signalement « {$this->signalement->libelleType()} » au point {$point->nom}.")
            ->line('Sa réponse : '.$this->signalement->reponse)
            ->action('Voir mes signalements', route('signalements.index'))
            ->line('Merci d\'aider à garder les points de collecte utilisables.');
    }
}
