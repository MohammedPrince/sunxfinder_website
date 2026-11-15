<?php

namespace App\Support;

use App\Helpers\Helper;
use App\Models\SolarCompany;

/**
 * wa.me "click to chat" links. Numbers are normalised to the international form wa.me needs
 * (digits only, country code first). Local Sudanese numbers get the 249 code.
 */
class WhatsApp
{
    public const DEFAULT_COUNTRY_CODE = '249';

    /** "+249 91 904 6917", "00249919046917", "0919046917" or "919046917" => "249919046917". Null if not a phone number. */
    public static function number(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = self::DEFAULT_COUNTRY_CODE . substr($digits, 1);
        } elseif (strlen($digits) === 9) {
            $digits = self::DEFAULT_COUNTRY_CODE . $digits;
        }

        return strlen($digits) >= 10 && strlen($digits) <= 15 ? $digits : null;
    }

    /** The company's WhatsApp: the wa.me link on its profile if it has one, otherwise its phone. */
    public static function companyNumber(?SolarCompany $company): ?string
    {
        if (!$company) {
            return null;
        }

        $link = (string) ((new Helper())->parseSocialLinks($company->social_media)['whatsapp'] ?? '');

        if ($link !== '' && preg_match('~wa\.me/\+?(\d{6,15})~i', $link, $match)) {
            return self::number($match[1]);
        }

        return self::number($company->phone);
    }

    public static function link(string $number, string $text = ''): string
    {
        return 'https://wa.me/' . $number . ($text !== '' ? '?text=' . rawurlencode($text) : '');
    }

    /** SunXFinder's own WhatsApp number in readable form, e.g. "+249 91 904 6917". */
    public static function supportNumberLabel(): ?string
    {
        $number = self::number(config('services.whatsapp.phone_number'));

        if (!$number) {
            return null;
        }

        return str_starts_with($number, self::DEFAULT_COUNTRY_CODE) && strlen($number) === 12
            ? '+249 ' . substr($number, 3, 2) . ' ' . substr($number, 5, 3) . ' ' . substr($number, 8)
            : '+' . $number;
    }
}
