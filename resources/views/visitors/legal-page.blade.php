@extends('visitors.layouts.app')
@section('title', 'SunXFinder - ' . $header->text('title', $lang))
@section('content')
    @push('styles')
        <style>
            .legal-card a {
                color: #087fe0;
                font-weight: 700;
                text-decoration: none;
            }

            .legal-card a:hover {
                color: #ff5100;
                text-decoration: underline;
            }

            .legal-card ol {
                padding-inline-start: 22px;
                margin: 10px 0 0;
            }
        </style>
    @endpush

    <main class="legal-page" lang="{{ $lang }}" @if ($lang === 'ar') dir="rtl" @endif>
        <div class="legal-container">
            <header class="legal-hero">
                @if ($header->text('eyebrow', $lang))
                    <span class="legal-eyebrow">{{ $header->text('eyebrow', $lang) }}</span>
                @endif
                <h1>{{ $header->text('title', $lang) }}</h1>
                <p>{{ $header->text('body', $lang) }}</p>
                @if ($header->last_updated)
                    <span class="legal-updated">
                        {{ $lang === 'ar'
                            ? 'آخر تحديث: ' . $header->last_updated->locale('ar')->translatedFormat('j F Y')
                            : 'Last updated: ' . $header->last_updated->locale('en')->translatedFormat('F j, Y') }}
                    </span>
                @endif
            </header>

            {{-- Bodies are sanitized with Helper::sanitizeRichText when they are saved. --}}
            @foreach ($sections as $section)
                <section class="legal-card">
                    <h2>{{ $loop->iteration }}. {{ $section->text('title', $lang) }}</h2>
                    {!! $section->text('body', $lang) !!}
                </section>
            @endforeach

            @foreach ($contacts as $contact)
                <section class="legal-contact">
                    <h2>{{ $contact->text('title', $lang) }}</h2>
                    {!! $contact->text('body', $lang) !!}
                </section>
            @endforeach
        </div>
    </main>
@endsection
