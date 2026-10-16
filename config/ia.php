<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Module d'analyse IA — Réparations (membre 5)
    |--------------------------------------------------------------------------
    |
    | Le service DiagnosticReparationIA interroge un modèle de langage lorsque
    | "provider" n'est pas "local" ET qu'une clé est renseignée. Sinon il retombe
    | automatiquement sur le moteur local (AnalyseurLocal), afin que la
    | démonstration fonctionne toujours, même sans connexion ni clé API.
    |
    | L'appel utilise l'API "chat completions" compatible OpenAI. Cela couvre
    | OpenAI, Groq, Mistral, Together, Ollama, ainsi que Gemini via son point
    | d'entrée compatible OpenAI :
    |   https://generativelanguage.googleapis.com/v1beta/openai
    |
    */

    'provider' => env('IA_PROVIDER', 'local'), // local | openai

    'api_key' => env('IA_API_KEY'),

    'base_url' => env('IA_BASE_URL', 'https://api.openai.com/v1'),

    'model' => env('IA_MODEL', 'gpt-4o-mini'),

    'timeout' => (int) env('IA_TIMEOUT', 20),

    /*
    |--------------------------------------------------------------------------
    | Bundle de certificats racine (portabilité)
    |--------------------------------------------------------------------------
    |
    | Fichier PEM livré avec le projet et utilisé comme magasin d'autorités de
    | certification pour les appels HTTPS. Il évite de dépendre du réglage
    | "curl.cainfo" du php.ini local : le module IA fonctionne ainsi à
    | l'identique sur toutes les machines (intégration, autre poste…).
    |
    | Mettre IA_CACERT à vide pour se reposer sur la configuration système de PHP.
    |
    */

    'cacert' => env('IA_CACERT', base_path('certificates/cacert.pem')),

    /*
    |--------------------------------------------------------------------------
    | Cache des synthèses d'avis
    |--------------------------------------------------------------------------
    |
    | Une synthèse d'avis (appel au modèle de langage) est coûteuse : on la met
    | en cache. Par défaut (null) on utilise le magasin de cache de
    | l'application (CACHE_STORE). Laisser null pour ne rien configurer.
    |
    */

    'cache_store' => env('IA_CACHE_STORE'),

    'cache_ttl' => (int) env('IA_CACHE_TTL', 3600),

];

