<!doctype html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') · SunXFinder</title>
    <link rel="icon" type="png" href="{{ asset('admin/images/sun.png') }}">
    <link rel="stylesheet" href="{{ asset('admin/css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('main/css/quill.snow.css') }}">
    @stack('styles')
</head>

<body class="admin-body">
    <div class="admin-shell">
        @include('admin.partials.header')
        <main class="admin-main">
            <div class="page-topline">
                <div>
                    <p class="eyebrow">@yield('eyebrow', 'SUNXFINDER CONTROL CENTER')</p>
                    <h1>@yield('page-title', 'Dashboard')</h1>
                    <p class="page-subtitle">@yield('page-description', 'Manage your solar marketplace in one place.')</p>
                </div>
                <div class="page-actions">@yield('page-actions')</div>
            </div>
            @yield('content')
            @include('admin.partials.footer')
        </main>
    </div>
    <script src="{{ asset('admin/js/admin.js') }}" defer></script>
    <script src="{{ asset('main/js/quill.js') }}"></script>
    @yield('scripts')

</body>

</html>
