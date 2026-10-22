<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Toutes les routes des modules passent par le middleware auth : seules les pages publiques
 * du catalogue et les pages de connexion (middleware guest) restent ouvertes aux visiteurs.
 */
class RoutesProtegeesTest extends TestCase
{
    /** Pages volontairement publiques : le catalogue, que les visiteurs consultent avant de s'inscrire. */
    private const PUBLIQUES = [
        'home',
        'vetements.index', 'vetements.show',
        'associations.index', 'associations.show',
        'besoins.index', 'besoins.show', 'besoins.assistant',
        'ateliers.index', 'ateliers.show', 'avis.analyse',
        'categories.index', 'categories.show',
        'points-collecte.index', 'points-collecte.show',
        'transformations.index', 'transformations.show',
        'depots.suivi', 'campagnes.index', 'campagnes.show', 'collecte.assistant',
        'marques.index', 'marques.show',
    ];

    public function test_chaque_route_de_module_exige_d_etre_connecte(): void
    {
        $nonProtegees = collect(Route::getRoutes()->getRoutes())
            ->reject(fn ($route) => in_array($route->getName(), self::PUBLIQUES, true))
            // Routes techniques du framework (santé, fichiers, débogage).
            ->reject(fn ($route) => in_array($route->uri(), ['up', 'storage/{path}'], true) || str_starts_with($route->uri(), '_'))
            ->reject(fn ($route) => array_intersect(['auth', 'guest'], $route->gatherMiddleware()) !== [])
            ->map(fn ($route) => implode('|', $route->methods()).' '.$route->uri())
            ->values();

        $this->assertSame([], $nonProtegees->all(), "Routes sans middleware auth :\n".$nonProtegees->join("\n"));
    }

    public function test_un_visiteur_est_envoye_vers_la_connexion(): void
    {
        foreach (['espace', 'dons.index', 'dons.recus', 'besoins.mes', 'messagerie.index', 'notifications.index', 'mon-atelier.index', 'reparations.index', 'profile.edit'] as $nom) {
            $this->get(route($nom))->assertRedirect(route('login'));
        }
    }
}
