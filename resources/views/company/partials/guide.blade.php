{{--
    "How it works" box for a company page. Text: resources/lang/{en,ar}/company/guides.php.
    Expects: $page (key in that file).
--}}
@php $guide = __('company/guides.' . $page); @endphp
@if (is_array($guide))
    @include('partials.page-guide', [
        'key' => 'company-' . str_replace('_', '-', $page),
        'title' => $guide['title'],
        'intro' => $guide['intro'] ?? null,
        'steps' => $guide['steps'] ?? [],
        'note' => $guide['note'] ?? null,
    ])
@endif
