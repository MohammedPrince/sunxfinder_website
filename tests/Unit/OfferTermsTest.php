<?php

namespace Tests\Unit;

use App\Support\OfferTerms;
use Tests\TestCase;

/** Needs the app only for the translator; no database. */
class OfferTermsTest extends TestCase
{
    public function test_empty_input_is_stored_as_null(): void
    {
        $this->assertNull(OfferTerms::normalize(null));
        $this->assertNull(OfferTerms::normalize([]));
        $this->assertNull(OfferTerms::normalize([
            'warranties' => ['panels' => ['value' => '', 'unit' => 'years']],
            'installation_days' => '',
            'payment_terms' => '',
            'extras' => [['en' => ' ', 'ar' => '']],
        ]));
        $this->assertSame([], OfferTerms::lines(null));
    }

    public function test_normalize_keeps_known_filled_fields_only(): void
    {
        $terms = OfferTerms::normalize([
            'warranties' => [
                'panels' => ['value' => '25', 'unit' => 'years'],
                'inverter' => ['value' => '', 'unit' => 'years'],
                'installation' => ['value' => '6', 'unit' => 'months'],
                'roof' => ['value' => '3', 'unit' => 'years'],
            ],
            'installation_days' => '3',
            'payment_terms' => 'deposit_50',
            'validity_days' => '14',
            'included' => ['testing', 'delivery', 'not-a-service'],
            'extras' => [['en' => 'Free support', 'ar' => ''], ['en' => '', 'ar' => '']],
            'unknown' => 'x',
        ]);

        $this->assertSame([
            'warranties' => [
                'panels' => ['value' => 25, 'unit' => 'years'],
                'installation' => ['value' => 6, 'unit' => 'months'],
            ],
            'installation_days' => 3,
            'validity_days' => 14,
            'payment_terms' => 'deposit_50',
            // Fixed order, whatever order they were ticked in.
            'included' => ['delivery', 'testing'],
            'extras' => [['en' => 'Free support', 'ar' => null]],
        ], $terms);
    }

    public function test_lines_are_given_in_english_and_arabic(): void
    {
        $lines = collect(OfferTerms::lines([
            'warranties' => ['panels' => ['value' => 10, 'unit' => 'years'], 'inverter' => ['value' => 2, 'unit' => 'years']],
            'installation_days' => 1,
            'payment_terms' => 'on_completion',
            'included' => ['delivery', 'installation'],
            'extras' => [['en' => 'Free support', 'ar' => null]],
        ]))->keyBy('key');

        $this->assertSame([
            'key' => 'warranty_panels',
            'label' => 'Panels warranty',
            'ar_label' => 'ضمان الألواح',
            'value' => '10 years',
            'ar_value' => '10 سنوات',
        ], $lines['warranty_panels']);
        $this->assertSame('سنتان', $lines['warranty_inverter']['ar_value']);
        $this->assertSame('Within 1 day', $lines['installation_time']['value']);
        $this->assertSame('خلال يوم واحد', $lines['installation_time']['ar_value']);
        $this->assertSame('Full payment after installation', $lines['payment_terms']['value']);
        $this->assertSame('Delivery to site, Installation', $lines['included']['value']);
        $this->assertSame('التوصيل إلى الموقع، التركيب', $lines['included']['ar_value']);
        // An extra written in one language shows in both.
        $this->assertSame('Free support', $lines['extra']['ar_value']);
    }

    public function test_arabic_durations_agree_with_the_number(): void
    {
        $this->assertSame('سنة واحدة', OfferTerms::duration(1, 'years', 'ar'));
        $this->assertSame('5 سنوات', OfferTerms::duration(5, 'years', 'ar'));
        $this->assertSame('25 سنة', OfferTerms::duration(25, 'years', 'ar'));
        $this->assertSame('شهران', OfferTerms::duration(2, 'months', 'ar'));
        $this->assertSame('12 شهرًا', OfferTerms::duration(12, 'months', 'ar'));
        $this->assertSame('1 month', OfferTerms::duration(1, 'months', 'en'));
    }
}
