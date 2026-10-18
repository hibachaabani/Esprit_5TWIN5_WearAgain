<?php

namespace App\Services\IA\Dons;

/**
 * Moteur local, sans réseau : il reconnaît la catégorie, la famille et la saison du vêtement
 * grâce au lexique (mots-clés pondérés et âge écrit dans la description).
 */
class AnalyseurDonLocal implements AnalyseurDon
{
    public function analyser(string $description): array
    {
        $mots = LexiqueVetements::mots($description);
        $scores = [];

        foreach (LexiqueVetements::INDICES_CATEGORIE as $categorie => $indices) {
            foreach ($indices as $poids => $liste) {
                $scores[$categorie] = ($scores[$categorie] ?? 0) + $poids * count(array_intersect($liste, $mots));
            }
        }

        // « 8 ans » désigne un enfant, « 6 mois » ou « 1 an » un bébé.
        if ($age = LexiqueVetements::age($description)) {
            $bebe = $age['unite'] === 'mois' || $age['valeur'] <= 1;
            $cle = $bebe ? 'bebe' : ($age['valeur'] <= 16 ? 'enfant' : null);
            if ($cle) {
                $scores[$cle] += 3;
            }
        }

        arsort($scores);
        $retenues = array_keys(array_filter($scores));

        return [
            // L'intitulé : la première partie de la phrase (« Doudoune bleue 8 ans, portée un hiver » → « Doudoune bleue 8 ans »).
            'vetement' => trim(preg_split('/[,.;!\n]/', $description)[0]) ?: $description,
            'categorie' => $retenues[0] ?? null,
            'categories_proches' => array_slice($retenues, 1, 2),
            'mots_cles' => $mots,
            'saison' => $this->saison($mots),
            'source' => 'local',
        ];
    }

    private function saison(array $mots): string
    {
        $hiver = count(array_intersect(LexiqueVetements::SAISONS['hiver'], $mots));
        $ete = count(array_intersect(LexiqueVetements::SAISONS['ete'], $mots));

        return match (true) {
            $hiver > $ete => 'hiver',
            $ete > $hiver => 'ete',
            default => 'toutes',
        };
    }
}
