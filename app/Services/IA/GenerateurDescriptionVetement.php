<?php

namespace App\Services\IA;

use App\Services\IA\Concerns\ConfigureTls;
use Illuminate\Support\Facades\Http;

/**
 * Génère une description marketing soignée pour un vêtement
 * à partir des champs saisis par l'utilisateur.
 *
 * Si aucune clé API n'est configurée, retombe sur un template local.
 */
class GenerateurDescriptionVetement
{
    use ConfigureTls;

    /**
     * @param  array{titre: string, categorie: string, marque: string, taille: string, matiere: string, couleur: string, etat: string}  $champs
     */
    public function generer(array $champs): array
    {
        if (config('ia.provider') !== 'local' && config('ia.api_key')) {
            try {
                return $this->genererViaOpenAI($champs);
            } catch (\Throwable) {
                // Fallback silencieux
            }
        }

        return $this->genererViaTemplate($champs);
    }

    private function genererViaOpenAI(array $champs): array
    {
        $contexte = collect($champs)
            ->filter(fn ($v) => $v !== '' && $v !== null)
            ->map(fn ($v, $k) => ucfirst($k) . ' : ' . $v)
            ->implode("\n");

        $response = Http::withToken(config('ia.api_key'))
            ->withOptions($this->tlsOptions())
            ->timeout((int) config('ia.timeout', 20))
            ->acceptJson()
            ->post(rtrim((string) config('ia.base_url'), '/') . '/chat/completions', [
                'model'       => config('ia.model'),
                'temperature' => 0.7,
                'messages'    => [
                    ['role' => 'system', 'content' => $this->promptSysteme()],
                    ['role' => 'user',   'content' => $contexte],
                ],
            ]);

        $response->throw();

        $texte = trim(data_get($response->json(), 'choices.0.message.content', ''));

        if ($texte === '') {
            throw new \RuntimeException('Réponse IA vide.');
        }

        return [
            'description' => $texte,
            'source'      => 'ia',
        ];
    }

    private function genererViaTemplate(array $champs): array
    {
        $parties = [];

        if (! empty($champs['titre'])) {
            $parties[] = $champs['titre'] . '.';
        }

        if (! empty($champs['marque'])) {
            $parties[] = "Marque : {$champs['marque']}.";
        }

        $details = array_filter([
            ! empty($champs['taille'])  ? "Taille {$champs['taille']}" : null,
            ! empty($champs['couleur']) ? $champs['couleur'] : null,
            ! empty($champs['matiere']) ? $champs['matiere'] : null,
        ]);

        if ($details) {
            $parties[] = implode(', ', $details) . '.';
        }

        $etats = [
            'neuf'       => 'Article neuf, jamais porté.',
            'tres_bon'   => 'En très bon état, très peu porté.',
            'bon'        => 'En bon état général, légères traces d\'usure normales.',
            'acceptable' => 'État acceptable, quelques marques d\'usure visibles.',
            'use'        => 'Article usé, défauts apparents.',
        ];

        if (! empty($champs['etat']) && isset($etats[$champs['etat']])) {
            $parties[] = $etats[$champs['etat']];
        }

        $description = implode(' ', $parties);

        return [
            'description' => $description ?: 'Article en bon état, prêt à être adopté.',
            'source'      => 'template',
        ];
    }

    private function promptSysteme(): string
    {
        return <<<'PROMPT'
        Tu es un rédacteur spécialisé en annonces de vêtements de seconde main pour la plateforme WearAgain (Tunisie).
        À partir des informations fournies, rédige une description d'annonce attrayante, naturelle et concise (3 à 5 phrases maximum).
        La description doit :
        - Mettre en valeur le vêtement sans mentir
        - Mentionner l'état avec honnêteté
        - Être rédigée en français, à la troisième personne (pas de "je")
        - Ne pas répéter le titre mot pour mot
        Réponds UNIQUEMENT avec la description, sans introduction ni guillemets.
        PROMPT;
    }
}
