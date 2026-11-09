@extends('company.layouts.app')

@section('title', __('company/manual.title'))
@section('breadcrumb', __('company/manual.title'))
@section('eyebrow', __('company/manual.eyebrow'))
@section('page-title', __('company/manual.title'))
@section('page-description', __('company/manual.page_description'))

@section('page-actions')
    <button type="button" class="btn btn-light no-print" onclick="window.print()">{{ __('company/manual.print') }}</button>
@endsection

@section('content')
    @php
        // Section key => the page it explains. Advertising shows only when it is on for this company.
        $pages = [
            'start' => 'company.dashboard',
            'profile' => 'company.profile',
            'products' => 'company.products.index',
            'quotes' => 'company.quote-requests.index',
            'package_offers' => 'company.offers.index',
            'projects' => 'company.projects.index',
            'invoices' => 'company.invoices.index',
            'customers' => 'company.customers.index',
            'notifications' => 'company.notifications.index',
            'advertising' => 'company.ads.index',
            'ratings' => 'company.ratings.index',
            'reports' => 'company.reports.index',
            'help' => null,
        ];

        $sections = collect(__('company/manual.sections'))
            ->only(array_keys($pages))
            ->when(!$hasAdvertising, fn($all) => $all->except('advertising'));
    @endphp

    <div class="manual" id="manual-top">
        <section class="panel manual-intro">
            <div class="panel-body">
                <h2>{{ __('company/manual.intro_title') }}</h2>
                <p>{{ __('company/manual.intro_body') }}</p>

                <h3>{{ __('company/manual.flow_title') }}</h3>
                <ol class="manual-flow">
                    @foreach (__('company/manual.flow') as [$label, $text])
                        <li>
                            <span class="manual-flow-number">{{ $loop->iteration }}</span>
                            <strong>{{ $label }}</strong>
                            <small>{{ $text }}</small>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <div class="manual-layout">
            <nav class="manual-toc panel no-print" aria-label="{{ __('company/manual.contents') }}">
                <strong>{{ __('company/manual.contents') }}</strong>
                <ol>
                    @foreach ($sections as $key => $section)
                        <li><a href="#manual-{{ $key }}">{{ $section['title'] }}</a></li>
                    @endforeach
                </ol>
            </nav>

            <div class="manual-sections">
                @foreach ($sections as $key => $section)
                    <section class="panel manual-section" id="manual-{{ $key }}">
                        <div class="panel-heading manual-section-heading">
                            <div>
                                <h2><span class="manual-section-number">{{ $loop->iteration }}</span>{{ $section['title'] }}</h2>
                                <p>{{ $section['intro'] }}</p>
                            </div>
                            @if ($pages[$key])
                                <a href="{{ route($pages[$key]) }}" class="btn btn-light no-print">{{ __('company/manual.open_page') }}</a>
                            @endif
                        </div>

                        <div class="panel-body">
                            <h3 class="manual-label">{{ __('company/manual.steps') }}</h3>
                            {{-- Trusted text from resources/lang/{en,ar}/company/manual.php (may contain <strong>). --}}
                            <ol class="manual-steps">
                                @foreach ($section['steps'] as $step)
                                    <li>{!! $step !!}</li>
                                @endforeach
                            </ol>

                            @if (!empty($section['tips']))
                                <div class="manual-tips">
                                    <h3 class="manual-label">💡 {{ __('company/manual.tips') }}</h3>
                                    <ul>
                                        @foreach ($section['tips'] as $tip)
                                            <li>{!! $tip !!}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <a href="#manual-top" class="manual-top-link no-print">↑ {{ __('company/manual.back_to_top') }}</a>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
@endsection
