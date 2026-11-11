@php
    // $terms: saved terms of this offer, or the company's last offer for a new one. $prefilled: the latter.
    $terms = $terms ?? [];
    $prefilled = $prefilled ?? false;
    $t = 'company/messages.offer_terms.';

    // After a validation error the form shows exactly what was posted, including unticked boxes.
    $hasOld = session()->hasOldInput();
    $included = $hasOld ? (array) old('terms.included', []) : ($terms['included'] ?? []);
    $extras = array_values($hasOld ? (array) old('terms.extras', []) : ($terms['extras'] ?? []));

    $validity = old('terms.validity_days', $terms['validity_days'] ?? '');
    $validityOptions = \App\Support\OfferTerms::VALIDITY_OPTIONS;
    if ($validity !== '' && $validity !== null && !in_array((int) $validity, $validityOptions, true)) {
        $validityOptions[] = (int) $validity;
        sort($validityOptions);
    }
@endphp

<section class="offer-terms-card" aria-labelledby="offerTermsTitle">
    <div class="offer-invoice-heading">
        <div>
            <h3 id="offerTermsTitle">{{ __($t . 'title') }}</h3>
            <p>{{ __($t . 'subtitle') }}</p>
        </div>
    </div>

    <div class="offer-terms-body">
        @if ($prefilled)
            <small class="offer-terms-prefilled">{{ __($t . 'prefilled_note') }}</small>
        @endif

        <div class="offer-terms-group">
            <h4>{{ __($t . 'warranty_heading') }}</h4>
            <div class="offer-terms-grid">
                @foreach (\App\Support\OfferTerms::WARRANTY_PARTS as $part)
                    @php
                        $value = old("terms.warranties.$part.value", $terms['warranties'][$part]['value'] ?? '');
                        $unit = old("terms.warranties.$part.unit", $terms['warranties'][$part]['unit'] ?? 'years');
                    @endphp
                    <div class="offer-form-group">
                        <label for="terms_warranty_{{ $part }}">{{ __($t . 'warranty_' . $part) }}</label>
                        <div class="offer-terms-duration">
                            <input class="form-control" type="number" id="terms_warranty_{{ $part }}" min="1" max="360" step="1"
                                name="terms[warranties][{{ $part }}][value]" value="{{ $value }}"
                                placeholder="{{ __($t . 'warranty_placeholder') }}">
                            <select class="form-control" name="terms[warranties][{{ $part }}][unit]"
                                aria-label="{{ __($t . 'warranty_' . $part) }}">
                                @foreach (\App\Support\OfferTerms::UNITS as $option)
                                    <option value="{{ $option }}" @selected($unit === $option)>{{ __($t . $option) }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($part === 'installation')
                            <small>{{ __($t . 'warranty_installation_hint') }}</small>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="offer-terms-group">
            <h4>{{ __($t . 'service_heading') }}</h4>
            <div class="offer-terms-grid">
                <div class="offer-form-group">
                    <label for="terms_installation_days">{{ __($t . 'installation_time') }}</label>
                    <div class="offer-terms-suffix">
                        <input class="form-control" type="number" id="terms_installation_days" min="1" max="365" step="1"
                            placeholder="{{ __($t . 'installation_time_placeholder') }}"
                            name="terms[installation_days]" value="{{ old('terms.installation_days', $terms['installation_days'] ?? '') }}">
                        <span>{{ __($t . 'installation_time_suffix') }}</span>
                    </div>
                </div>

                <div class="offer-form-group">
                    <label for="terms_free_maintenance">{{ __($t . 'free_maintenance') }}</label>
                    <div class="offer-terms-suffix">
                        <input class="form-control" type="number" id="terms_free_maintenance" min="1" max="120" step="1"
                            placeholder="{{ __($t . 'free_maintenance_placeholder') }}"
                            name="terms[free_maintenance_months]"
                            value="{{ old('terms.free_maintenance_months', $terms['free_maintenance_months'] ?? '') }}">
                        <span>{{ __($t . 'free_maintenance_suffix') }}</span>
                    </div>
                </div>

                <div class="offer-form-group">
                    <label for="terms_payment">{{ __($t . 'payment') }}</label>
                    @php $payment = old('terms.payment_terms', $terms['payment_terms'] ?? ''); @endphp
                    <select class="form-control" id="terms_payment" name="terms[payment_terms]">
                        <option value="">{{ __($t . 'payment_none') }}</option>
                        @foreach (\App\Support\OfferTerms::PAYMENT_TERMS as $option)
                            <option value="{{ $option }}" @selected($payment === $option)>{{ __($t . 'payment_' . $option) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="offer-form-group">
                    <label for="terms_validity">{{ __($t . 'validity') }}</label>
                    <select class="form-control" id="terms_validity" name="terms[validity_days]">
                        <option value="">{{ __($t . 'validity_none') }}</option>
                        @foreach ($validityOptions as $days)
                            <option value="{{ $days }}" @selected((string) $validity === (string) $days)>
                                {{ \App\Support\OfferTerms::duration($days, 'days', app()->getLocale()) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="offer-terms-group">
            <h4>{{ __($t . 'included') }}</h4>
            <div class="offer-terms-chips">
                @foreach (\App\Support\OfferTerms::INCLUDED as $option)
                    <label class="offer-terms-chip">
                        <input type="checkbox" name="terms[included][]" value="{{ $option }}" @checked(in_array($option, $included, true))>
                        <span>{{ __($t . 'included_' . $option) }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="offer-terms-group">
            <div class="offer-terms-extras-head">
                <div>
                    <h4>{{ __($t . 'extras') }}</h4>
                    <small>{{ __($t . 'extras_hint') }}</small>
                </div>
                <button type="button" class="btn btn-light" id="addOfferExtra"
                    data-max="{{ \App\Support\OfferTerms::MAX_EXTRAS }}">{{ __($t . 'add_extra') }}</button>
            </div>

            <div class="offer-terms-extras" id="offerExtras">
                @foreach ($extras as $index => $extra)
                    @include('company.quote-requests.partials.offer-terms-extra', ['index' => $index, 'extra' => $extra])
                @endforeach
            </div>

            <template id="offerExtraTemplate">
                @include('company.quote-requests.partials.offer-terms-extra', ['index' => '__EXTRA__', 'extra' => []])
            </template>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const list = document.getElementById('offerExtras');
        const template = document.getElementById('offerExtraTemplate');
        const add = document.getElementById('addOfferExtra');
        if (!list || !template || !add) return;

        // Indexes only grow, so a removed row never shares its name with a new one.
        let next = list.querySelectorAll('[data-offer-extra]').length;
        const max = Number(add.dataset.max || 10);
        const refresh = () => { add.disabled = list.querySelectorAll('[data-offer-extra]').length >= max; };

        add.addEventListener('click', function () {
            const holder = document.createElement('template');
            holder.innerHTML = template.innerHTML.replaceAll('__EXTRA__', next++);
            const row = holder.content.firstElementChild;
            list.appendChild(row);
            row.querySelector('input')?.focus();
            refresh();
        });

        list.addEventListener('click', function (event) {
            const remove = event.target.closest('[data-remove-offer-extra]');
            if (!remove) return;
            remove.closest('[data-offer-extra]').remove();
            refresh();
        });

        refresh();
    });
</script>
