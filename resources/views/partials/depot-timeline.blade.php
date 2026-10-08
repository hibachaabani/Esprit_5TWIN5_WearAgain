<ol class="timeline">
    @foreach ($depot->etapes() as $etape)
        <li @class(['faite' => $etape['faite']])>
            <strong>{{ $etape['libelle'] }}</strong>
            <span>{{ $etape['date'] ? $etape['date']->format('d/m/Y à H:i') : 'À venir' }}</span>
        </li>
    @endforeach
</ol>
