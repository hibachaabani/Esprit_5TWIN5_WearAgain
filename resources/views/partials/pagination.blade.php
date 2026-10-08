@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="page disabled">‹ Précédent</span>
        @else
            <a class="page" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Précédent</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="page disabled">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="page current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="page" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="page" href="{{ $paginator->nextPageUrl() }}" rel="next">Suivant ›</a>
        @else
            <span class="page disabled">Suivant ›</span>
        @endif
    </nav>
@endif
