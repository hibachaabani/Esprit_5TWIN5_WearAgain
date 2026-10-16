<?php

namespace App\Services\IA\Concerns;

/**
 * Fournit les options TLS à transmettre au client HTTP.
 *
 * Le bundle de certificats racine livré avec le projet (config 'ia.cacert',
 * par défaut certificates/cacert.pem) est utilisé s'il existe. Les appels
 * HTTPS ne dépendent donc pas du réglage "curl.cainfo" du php.ini local :
 * le module IA fonctionne à l'identique sur toutes les machines.
 */
trait ConfigureTls
{
    /**
     * @return array<string, mixed>
     */
    protected function tlsOptions(): array
    {
        $cacert = config('ia.cacert');

        if (is_string($cacert) && $cacert !== '' && is_file($cacert)) {
            return ['verify' => $cacert];
        }

        return [];
    }
}
