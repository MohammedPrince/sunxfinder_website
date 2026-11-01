{{-- Website home banner: slides set to show on the website in Admin → App Slider. Expects $banners (rows). --}}
@if (!empty($banners))
    @php
        $bannerText = app()->isLocale('ar') ? 'description_ar' : 'description_en';
    @endphp

    <section class="ad-banner" aria-label="{{ __('messages.Sponsored') }}">
        <div class="container">
            <div class="ad-banner-frame" data-ad-banner>
                @foreach ($banners as $banner)
                    @php
                        $hasLink = filled($banner['link_url']) && preg_match('#^https?://#i', $banner['link_url']);
                        $tag = $hasLink ? 'a' : 'div';
                    @endphp
                    <{{ $tag }} @class(['ad-banner-slide', 'is-active' => $loop->first])
                        data-interval="{{ $banner['interval_seconds'] }}"
                        @if ($hasLink) href="{{ route('ad.click', ['type' => 'slider', 'id' => $banner['id'], 'platform' => 'website']) }}" target="_blank" rel="noopener sponsored" @endif>
                        @if ($banner['is_sponsored'])
                            <span class="ad-sponsored-label">{{ __('messages.Sponsored') }}</span>
                        @endif
                        <img src="{{ asset($banner['image_path']) }}" alt="{{ $banner['name'] }}"
                            width="{{ $banner['image_width'] }}" height="{{ $banner['image_height'] }}"
                            @unless ($loop->first) loading="lazy" @endunless>
                        @if (filled($banner[$bannerText]))
                            <div class="ad-banner-caption">{{ $banner[$bannerText] }}</div>
                        @endif
                    </{{ $tag }}>
                @endforeach
            </div>
        </div>
    </section>

    @if (count($banners) > 1)
        <script>
            document.querySelectorAll('[data-ad-banner]').forEach(frame => {
                const slides = [...frame.querySelectorAll('.ad-banner-slide')];
                let current = 0;

                const next = () => setTimeout(() => {
                    slides[current].classList.remove('is-active');
                    current = (current + 1) % slides.length;
                    slides[current].classList.add('is-active');
                    next();
                }, Number(slides[current].dataset.interval || 5) * 1000);

                next();
            });
        </script>
    @endif
@endif
