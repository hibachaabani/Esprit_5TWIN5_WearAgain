@if ($paginator->hasPages())
    <nav aria-label="Pagination">
        <ul class="pagination">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="disabled" aria-hidden="true"><span class="page-link">{{ $element }}</span></li>
                @elseif (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </ul>
    </nav>
@endif
