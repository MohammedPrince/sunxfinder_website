{{--
    Shared table footer: "Showing X to Y of N <label>" + page links.
    Usage: @include('partials.table-pagination', ['paginator' => $companies, 'label' => 'companies'])
--}}
@php
    $label = $label ?? company_text('shared.labels.records', 'records');
@endphp

<div class="table-bottom">
    <span class="table-info">
        @if (company_portal())
            {{ __('company/messages.shared.showing', ['first' => $paginator->firstItem() ?? 0, 'last' => $paginator->lastItem() ?? 0, 'total' => $paginator->total(), 'label' => __('company/messages.shared.labels.' . $label)]) }}
        @else
            Showing {{ $paginator->firstItem() ?? 0 }}
            to {{ $paginator->lastItem() ?? 0 }}
            of {{ $paginator->total() }} {{ $label }}
        @endif
    </span>

    <div class="pagination-wrapper">
        {{ $paginator->onEachSide(1)->links() }}
    </div>
</div>
