{{--
    SunXFinder pagination links (admin + company dashboards).
    Set as the default view in AppServiceProvider, so every {{ $paginator->links() }} uses it.
    Unlike Laravel's default view it is shown even when there is only one page,
    so every list page has the same footer.
--}}
<nav role="navigation" aria-label="Pagination">
    <ul>
        {{-- Previous --}}
        <li>
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Previous page">&lsaquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">&lsaquo;</a>
            @endif
        </li>

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li><span aria-disabled="true">{{ $element }}</span></li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    <li>
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endif
                    </li>
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        <li>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">&rsaquo;</a>
            @else
                <span aria-disabled="true" aria-label="Next page">&rsaquo;</span>
            @endif
        </li>
    </ul>
</nav>
