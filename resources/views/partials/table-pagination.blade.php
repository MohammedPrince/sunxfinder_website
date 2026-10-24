{{--
    Shared table footer: "Showing X to Y of N <label>" + page links.
    Usage: @include('partials.table-pagination', ['paginator' => $companies, 'label' => 'companies'])
--}}
@php
    $label = $label ?? 'records';
@endphp

<div class="table-bottom">
    <span class="table-info">
        Showing {{ $paginator->firstItem() ?? 0 }}
        to {{ $paginator->lastItem() ?? 0 }}
        of {{ $paginator->total() }} {{ $label }}
    </span>

    <div class="pagination-wrapper">
        {{ $paginator->onEachSide(1)->links() }}
    </div>
</div>
