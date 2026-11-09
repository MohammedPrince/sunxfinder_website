{{--
    Shared body of the Admin and Company manuals.
    $t     fn(string $key): string|array, reads the manual's language file (admin/manual or company/manual)
    $pages [section key => route name|null], in display order; sections not listed are not shown
    $dir   optional "rtl"/"ltr" for the manual block (the admin panel is English, its manual can be Arabic)
    Steps and tips come from our own language files and may contain <strong>/<code>, so they are printed unescaped.
--}}
@php
    $sections = collect($t('sections'))->only(array_keys($pages))->sortBy(fn($s, $key) => array_search($key, array_keys($pages), true));
    $langAttr = ($dir ?? null) === 'rtl' ? 'ar' : null;
@endphp

<div class="manual" id="manual-top" @if (!empty($dir)) dir="{{ $dir }}" @endif @if ($langAttr) lang="{{ $langAttr }}" @endif>
    <section class="panel manual-intro">
        <div class="panel-body">
            <h2>{{ $t('intro_title') }}</h2>
            <p>{{ $t('intro_body') }}</p>

            <h3>{{ $t('flow_title') }}</h3>
            <ol class="manual-flow">
                @foreach ($t('flow') as [$label, $text])
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
        <nav class="manual-toc panel no-print" aria-label="{{ $t('contents') }}">
            <strong>{{ $t('contents') }}</strong>
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
                            <a href="{{ route($pages[$key]) }}" class="btn btn-light no-print">{{ $t('open_page') }}</a>
                        @endif
                    </div>

                    <div class="panel-body">
                        <h3 class="manual-label">{{ $t('steps') }}</h3>
                        <ol class="manual-steps">
                            @foreach ($section['steps'] as $step)
                                <li>{!! $step !!}</li>
                            @endforeach
                        </ol>

                        @if (!empty($section['tips']))
                            <div class="manual-tips">
                                <h3 class="manual-label">💡 {{ $t('tips') }}</h3>
                                <ul>
                                    @foreach ($section['tips'] as $tip)
                                        <li>{!! $tip !!}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <a href="#manual-top" class="manual-top-link no-print">↑ {{ $t('back_to_top') }}</a>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</div>
