{{--
| Avatar d'un participant : sa photo de profil, sinon ses initiales.
| « Les Mains Solidaires » → « MS » ; un nom fait seulement d'articles (« LA ») garde ses propres lettres.
| Attend $compte (le compte, ou null) et $nom (le nom affiché).
--}}
@php
    $mots = collect(preg_split('/\s+/', trim($nom)))->filter();
    $utiles = $mots->reject(fn ($mot) => in_array(mb_strtolower($mot), ['les', 'la', 'le', 'de', 'des', 'du', 'et']));
    $lettres = ($utiles->isEmpty() ? $mots : $utiles)->take(2)->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))->join('');
@endphp
<span class="avatar" aria-hidden="true">
    @if ($compte?->photoUrl())
        <img src="{{ $compte->photoUrl() }}" alt="">
    @else
        {{ $lettres ?: '?' }}
    @endif
</span>
