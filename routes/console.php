<?php

use App\Models\Depot;
use App\Services\WorkflowDepot;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Module collecte (membre 3) : libère la place des dépôts jamais apportés.
Artisan::command('depots:expirer {--jours= : Jours de retard tolérés après la date prévue}', function (WorkflowDepot $workflow) {
    $jours = (int) ($this->option('jours') ?? config('collecte.expiration_jours'));

    $resultat = $workflow->appliquerEnMasse(
        Depot::with(['user', 'pointCollecte'])->expires($jours)->get(),
        'annule',
    );

    $this->info("{$resultat['appliques']} dépôt(s) en retard de plus de {$jours} jour(s) annulé(s) automatiquement.");
})->purpose('Annule les dépôts jamais apportés au point de collecte');

Schedule::command('depots:expirer')->dailyAt('02:00');
