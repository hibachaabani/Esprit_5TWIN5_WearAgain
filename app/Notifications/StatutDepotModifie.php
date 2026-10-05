<?php

namespace App\Notifications;

use App\Models\Depot;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Prévient le particulier à chaque étape du parcours de son dépôt.
 * En local, MAIL_MAILER=log écrit l'email dans storage/logs/laravel.log.
 */
class StatutDepotModifie extends Notification
{
    use Queueable;

    public function __construct(public Depot $depot)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $depot = $this->depot;
        $point = $depot->pointCollecte;

        $message = match ($depot->statut) {
            'recu' => "L'association a bien reçu votre vêtement « {$depot->vetement_titre} » au point {$point->nom}.",
            'trie' => "Votre vêtement « {$depot->vetement_titre} » a été trié : il commence sa seconde vie. Merci !",
            'annule' => "Votre dépôt « {$depot->vetement_titre} » prévu à {$point->nom} a été annulé.",
            default => "Le statut de votre dépôt « {$depot->vetement_titre} » est maintenant : {$depot->libelleStatut()}.",
        };

        return (new MailMessage)
            ->subject("Dépôt {$depot->code} : {$depot->libelleStatut()}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line($message)
            ->action('Suivre mon dépôt', route('depots.suivi', ['code' => $depot->code]))
            ->line('Merci de donner une seconde vie à vos vêtements avec WearAgain.');
    }
}
