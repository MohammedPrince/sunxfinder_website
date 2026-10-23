@extends('visitors.layouts.app')
@section('title', 'SunXFinder - ' . $company->name)

@section('content')
    <div class="open-app-page">
        <div class="container">
            <div class="open-app-card">
                <img class="open-app-logo" src="{{ asset_v('visitors/images/logo-128.webp') }}" alt="SunXFinder"
                    width="64" height="64">

                <h1>{{ __('messages.Opening the SunXFinder app…') }}</h1>
                <p class="open-app-company">{{ $company->name }}</p>
                <p class="open-app-hint">{{ __('messages.If the app did not open, choose an option below.') }}</p>

                <div class="open-app-actions">
                    <a class="btn btn-primary" id="openAppButton"
                        href="{{ $platform === 'android' ? $intentUrl : $schemeUrl }}">
                        {{ __('messages.Open in App') }}
                    </a>

                    @if ($storeUrl)
                        <a class="btn btn-quiet" href="{{ $storeUrl }}" rel="noopener">
                            {{ __('messages.Get the app') }}
                        </a>
                    @endif

                    <a class="btn btn-quiet" href="{{ $profileUrl }}">
                        {{ __('messages.Continue on website') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .open-app-page {
            padding: 64px 0 80px;
            background: linear-gradient(180deg, #fffaf4 0%, #fff 100%);
        }

        .open-app-card {
            max-width: 440px;
            margin: 0 auto;
            padding: 32px 24px;
            border: 1px solid #e6edf5;
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 24px 60px rgba(11, 45, 92, .08);
            text-align: center;
        }

        .open-app-logo {
            width: 64px;
            height: 64px;
            margin-bottom: 14px;
        }

        .open-app-card h1 {
            margin: 0 0 6px;
            font-size: 22px;
            color: #0b2d5c;
        }

        .open-app-company {
            margin: 0 0 12px;
            font-weight: 700;
            color: #fc5202;
        }

        .open-app-hint {
            margin: 0 0 22px;
            color: #5f7189;
            font-size: 14px;
        }

        .open-app-actions {
            display: grid;
            gap: 10px;
        }
    </style>

    {{-- Try the app once on arrival. Android: the intent link falls back on its own.
         iOS: if the page is still visible after a moment, the app is not installed. --}}
    <script>
        (function() {
            var platform = @json($platform);
            var appUrl = @json($platform === 'android' ? $intentUrl : $schemeUrl);
            var fallbackUrl = @json($storeUrl ?: $profileUrl);

            if (platform === 'other') {
                return;
            }

            window.location.href = appUrl;

            if (platform === 'ios') {
                setTimeout(function() {
                    if (!document.hidden) {
                        window.location.href = fallbackUrl;
                    }
                }, 1800);
            }
        })();
    </script>
@endsection
