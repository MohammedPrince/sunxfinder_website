{{--
    SunXFinder pagination links (admin + company dashboards).
    Set as the default view in AppServiceProvider, so every {{ $paginator->links() }} uses it.
    Unlike Laravel's default view it is shown even when there is only one page,
    so every list page has the same footer.
--}}
<nav role="navigation" aria-label="{{ company_text('shared.pagination', 'Pagination') }}">
    <ul>
        {{-- Previous --}}
        <li>
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ company_text('shared.previous_page', 'Previous page') }}">&lsaquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ company_text('shared.previous_page', 'Previous page') }}">&lsaquo;</a>
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
                            <a href="{{ $url }}" aria-label="{{ company_text('shared.go_to_page', 'Go to page ' . $page, ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    </li>
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        <li>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ company_text('shared.next_page', 'Next page') }}">&rsaquo;</a>
            @else
                <span aria-disabled="true" aria-label="{{ company_text('shared.next_page', 'Next page') }}">&rsaquo;</span>
            @endif
        </li>
    </ul>
</nav>
