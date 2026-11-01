<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds the Arabic validation wording the company portal needs on top of ar/validation.php.
 * Lines are added for this request only, so public-site forms keep their own wording.
 */
class CompanyValidationLines
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isLocale('ar')) {
            // Load the file first: addLines() alone would stop it from loading.
            trans('validation.required');

            $lines = [];

            foreach (Arr::dot(__('company/messages.validation')) as $key => $line) {
                $lines['validation.' . $key] = $line;
            }

            app('translator')->addLines($lines, 'ar');
        }

        return $next($request);
    }
}
