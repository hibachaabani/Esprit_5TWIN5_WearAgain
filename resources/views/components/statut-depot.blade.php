@props(['statut'])

<span {{ $attributes->class(['statut', "statut-{$statut}"]) }}>{{ \App\Models\Depot::STATUTS[$statut] ?? $statut }}</span>
