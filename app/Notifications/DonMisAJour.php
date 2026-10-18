<?php

namespace App\Notifications;

use App\Models\Don;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Prévient à chaque étape d'un don : à l'association quand un don lui est proposé
 * ou annulé, au donateur quand son don est accepté, refusé ou remis.
 * Par e-mail, et dans la cloche du site (canal « database », lu par js/notifications.js).
 */
class DonMisAJour extends Notification
{
    use Queueable;

    public function __construct(public Don $don)
    {
    }

    public function via(object $notifiable): array
    {
        // La cloche d'abord : elle reste enregistrée même si l'envoi de l'e-mail échoue.
        return ['database', 'mail'];
    }

    /** Contenu de la notification affichée dans la cloche. */
    public function toArray(object $notifiable): array
    {
        $don = $this->don;
        $titre = "« {$don->vetement_titre} »";
        $association = $don->association->nom;
        $donateur = $don->donateur->name;

        [$icone, $intitule, $texte] = match ($don->statut) {
            'propose' => ['🎁', 'Nouveau don proposé', "{$donateur} propose {$titre}".($don->besoin ? " pour « {$don->besoin->titre} »" : '').'. À vous de répondre.'],
            'accepte' => ['✅', 'Don accepté', "{$association} accepte {$titre}".($don->date_remise ? ' · remise le '.$don->date_remise->format('d/m à H\\hi') : '').'.'],
            'refuse' => ['❌', 'Don refusé', "{$association} ne peut pas accepter {$titre}."],
            'remis' => ['🎉', 'Don bien reçu', "{$association} a reçu {$titre}. Merci !"],
            'annule' => ['↩️', 'Don annulé', "{$donateur} a annulé {$titre}."],
        };

        return [
            'don_id' => $don->id,
            'statut' => $don->statut,
            'icone' => $icone,
            'titre' => $intitule,
            'texte' => $texte,
            'url' => route('dons.show', $don, false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $don = $this->don;
        $association = $don->association->nom;
        $mail = (new MailMessage)->greeting("Bonjour {$notifiable->name},");

        return match ($don->statut) {
            'propose' => $mail
                ->subject("Nouveau don proposé : {$don->vetement_titre}")
                ->line("{$don->donateur->name} propose de donner « {$don->vetement_titre} » à {$association}"
                    .($don->besoin ? " pour votre besoin « {$don->besoin->titre} »." : '.'))
                ->line('Mode de remise : '.Don::LIBELLES_MODE[$don->mode].'.')
                ->action('Traiter le don', route('dons.show', $don)),

            'accepte' => $mail
                ->subject("Votre don « {$don->vetement_titre} » est accepté")
                ->line("{$association} accepte votre don. Merci !")
                ->line('Remise prévue le '.$don->date_remise->format('d/m/Y à H:i')." : {$don->lieu_remise}.")
                ->when($don->consigne, fn ($m) => $m->line("Consigne : {$don->consigne}"))
                ->action('Voir mon don', route('dons.show', $don))
                ->line('Vous pouvez écrire à l\'association depuis la page du don.'),

            'refuse' => $mail
                ->subject("Votre don « {$don->vetement_titre} » n'a pas été retenu")
                ->line("{$association} ne peut pas accepter votre don.")
                ->line("Motif : {$don->motif_refus}")
                ->action('Voir les besoins des associations', route('besoins.index'))
                ->line('D\'autres associations recherchent peut-être ce vêtement.'),

            'remis' => $mail
                ->subject("Merci pour votre don « {$don->vetement_titre} »")
                ->line("{$association} confirme avoir reçu votre vêtement. Il commence sa seconde vie !")
                ->action('Voir mes dons', route('dons.index', ['onglet' => 'termines'])),

            'annule' => $mail
                ->subject("Don annulé : {$don->vetement_titre}")
                ->line("{$don->donateur->name} a annulé son don « {$don->vetement_titre} ».")
                ->action('Voir les dons reçus', route('dons.recus')),
        };
    }
}
