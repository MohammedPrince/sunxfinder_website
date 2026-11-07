<?php

namespace App\Support;

use App\Repositories\MainRepository;
use Throwable;

/**
 * The current Terms & Conditions version: the "Last updated" date (Y-m-d) of the Terms
 * page header, set in Admin → System → Privacy & Terms. The website page, GET /terms and
 * the acceptance records all use this one value. The app sends it back as terms_version.
 */
class TermsVersion
{
    public static function current(): ?string
    {
        try {
            // Same choice as the website: the admin's page, else the built-in text.
            $legal = app(MainRepository::class)->getLegalPage('terms') ?? LegalDefaults::page('terms');

            return $legal['header']?->last_updated?->toDateString();
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
