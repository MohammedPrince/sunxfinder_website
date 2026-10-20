<?php

namespace App\Helpers;

use App\Models\SolarCompany;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductSpec;



class Helper
{

    public function isCustomerAuthenticated(): ?\App\Models\Customer
    {
        $customer = auth('sanctum')->user();

        return $customer instanceof \App\Models\Customer
            ? $customer
            : null;
    }

    public function transformCompany(
        SolarCompany $company,
        bool $detailed = false
    ): array {
        $data = [
            'id' => (int) $company->id,
            'name' => $company->name,
            'slug' => $company->slug,
            'city' => $company->city,
            'logo_url' => $this->publicUrl($company->logo_path),
            'cover_image_url' => null,
            'rating' => null,
            'reviews_count' => 0,
            'services' => $this->splitList($company->services),
            'ar_services' => $this->splitList($company->ar_services),
            'is_verified' => (bool) $company->is_verified,
            'year_of_exp' => $company->year_of_exp,
            'number_of_projects' => $company->number_of_projects,
            'status' => $company->status,

        ];

        if (!$detailed) {
            return $data;
        }

        $products = Product::query()
            ->where('company_id', $company->id)
            ->with(['supplier', 'specs'])
            ->get()
            ->map(function (Product $product): array {
                return [
                    'id' => (int) $product->id,
                    'name' => $product->name,
                    'model' => $product->model,
                    'type' => $product->type,
                    'description' => $this->plainText(
                        $product->description
                    ),
                    'supplier' => $product->supplier ? [
                        'id' => (int) $product->supplier->id,
                        'name' => $product->supplier->name,
                    ] : null,
                    'specifications' => $product->specs
                        ->map(fn(ProductSpec $spec): array => [
                            'name' => $spec->spec_name,
                            'value' => $spec->spec_value,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        return $data + [
            'description' => $this->plainText($company->description),
            'ar_description' => $this->plainText($company->ar_description),
            'phone' => $company->phone,
            'email' => $company->email,
            'license_number' => $company->license_number,
            'social_links' => $this->parseSocialLinks(
                $company->social_media
            ),
            'years_experience' => null,
            'projects_completed' => null,
            'support_hours' => null,
            'products' => $products,
            'reviews' => [],
        ];
    }

    public function plainText(?string $html): string
    {
        if (!$html) {
            return '';
        }

        // Keep paragraph/line breaks as spaces so words don't run together.
        $text = preg_replace('/<(br|\/p|\/div|\/li|\/h[1-6])[^>]*>/i', ' ', $html);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    public function splitList(?string $value): array
    {
        if (!$value) {
            return [];
        }

        return collect(preg_split('/[,\r\n]+/', $value))
            ->map(fn($item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    public function publicUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }

    public function parseSocialLinks(?string $value): array
    {
        $links = [
            'website' => null,
            'facebook' => null,
            'instagram' => null,
            'whatsapp' => null,
        ];

        if (!$value) {
            return $links;
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded)) {
            foreach ($links as $key => $unused) {
                if (!empty($decoded[$key]) && is_string($decoded[$key])) {
                    $links[$key] = trim($decoded[$key]);
                }
            }

            return $links;
        }

        foreach ($this->splitList($value) as $item) {
            $url = $item;

            if (!Str::startsWith($url, ['http://', 'https://'])) {
                $url = 'https://' . ltrim($url, '/');
            }

            $host = strtolower((string) parse_url($url, PHP_URL_HOST));

            if (Str::contains($host, 'facebook')) {
                $links['facebook'] ??= $url;
            } elseif (Str::contains($host, 'instagram')) {
                $links['instagram'] ??= $url;
            } elseif (Str::contains($host, ['wa.me', 'whatsapp'])) {
                $links['whatsapp'] ??= $url;
            } else {
                $links['website'] ??= $url;
            }
        }

        return $links;
    }

    public function visibleCompanies()
    {
        return SolarCompany::query()
            ->whereIn('status', ['approved', 'pending'])
            ->where('is_active', true)
            ->where('is_verified', true);
    }
}