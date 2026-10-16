<?php

namespace App\Services\IA;

/**
 * Génère un brouillon de réponse de l'atelier à partir de gabarits français,
 * sans appel réseau. Le ton s'adapte à la note :
 *  - avis positif  : remerciement chaleureux ;
 *  - avis mitigé    : remerciement + demande de précisions ;
 *  - avis négatif   : excuses, prise en compte, proposition de reprise.
 *
 * Utilisé par défaut et comme repli si le modèle de langage échoue.
 */
class AnalyseurReponseLocal implements AnalyseurReponseIA
{
    public function rediger(array $contexte): string
    {
        $note = (int) ($contexte['note'] ?? 0);
        $client = $this->prenom($contexte['client'] ?? null);
        $atelier = trim((string) ($contexte['atelier'] ?? '')) ?: "l'atelier";

        $adresse = $client !== '' ? "Bonjour {$client}," : 'Bonjour,';

        return $adresse."\n\n".$this->corps($note, $atelier)."\n\n".$this->signature($atelier);
    }

    private function corps(int $note, string $atelier): string
    {
        if ($note >= 4) {
            return "Merci beaucoup pour votre retour et pour votre confiance. Nous sommes ravis que votre expérience au sein de {$atelier} vous ait donné satisfaction, et nous serions heureux de vous accueillir de nouveau pour vos prochaines retouches.";
        }

        if ($note === 3) {
            return "Merci d'avoir pris le temps de partager votre expérience avec {$atelier}. Nous notons votre retour et souhaitons faire encore mieux : n'hésitez pas à nous préciser ce qui aurait pu être amélioré, nous en tiendrons compte.";
        }

        return "Nous vous remercions pour votre retour et sommes sincèrement désolés que votre expérience au sein de {$atelier} n'ait pas été à la hauteur de vos attentes. Votre satisfaction est essentielle : nous vous invitons à nous contacter directement afin de convenir ensemble d'une reprise ou d'une solution adaptée.";
    }

    private function signature(string $atelier): string
    {
        return "Cordialement,\nL'équipe {$atelier}";
    }

    private function prenom(?string $nom): string
    {
        $nom = trim((string) $nom);

        if ($nom === '') {
            return '';
        }

        return explode(' ', $nom)[0];
    }
}
