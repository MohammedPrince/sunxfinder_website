<?php

namespace App\Repositories;

use App\Helpers\Helper;
use App\Models\PackageOffer;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

/**
 * Package offers, shared by the company panel (Offers) and the admin customer campaign,
 * so both build, price and store an offer the same way.
 */
class PackageOfferRepository
{
    /** Active products of these companies for the item pickers (company_id tells the admin page which to show). */
    public function productOptions(array $companyIds): Collection
    {
        if (!$companyIds) {
            return collect();
        }

        return Product::query()
            ->whereIn('company_id', $companyIds)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'company_id', 'name', 'model', 'type']);
    }

    /** Offers customers can still accept, for these companies. */
    public function openOffers(array $companyIds): Collection
    {
        if (!$companyIds) {
            return collect();
        }

        return PackageOffer::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', PackageOffer::STATUS_ACTIVE)
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest('id')
            ->get();
    }

    /**
     * Invoice-style lines with their totals. A line can name one of the company's products
     * (product_id); its type then comes from the product. Lines without a product keep an
     * optional type (solar_panel, inverter, battery, other) and their free-text description.
     */
    public function normalizeItems(array $items, int $companyId, string $errorKey = 'items'): array
    {
        $productIds = collect($items)
            ->pluck('product_id')
            ->filter(fn($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $products = $productIds->isEmpty()
            ? collect()
            : Product::query()
                ->where('company_id', $companyId)
                ->whereIn('id', $productIds)
                ->get(['id', 'type'])
                ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                $errorKey => __('company/messages.package_offers.product_invalid'),
            ]);
        }

        return collect($items)->map(function (array $item) use ($products) {
            $quantity = (float) $item['quantity'];
            $unitPrice = round((float) $item['unit_price'], 2);
            $product = is_numeric($item['product_id'] ?? null) ? $products->get((int) $item['product_id']) : null;
            $type = $product?->type
                ?? (array_key_exists((string) ($item['type'] ?? ''), Product::TYPES) ? $item['type'] : null);

            return array_filter([
                'type' => $type,
                'product_id' => $product?->id,
                'description' => trim($item['description']),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
            ], fn($value) => $value !== null);
        })->values()->all();
    }

    /** Stores an uploaded offer image (resized to 1200 px wide). */
    public function storeImage(UploadedFile $image): array
    {
        return Helper::storeOptimizedImage($image, PackageOffer::IMAGE_DIR, 1200, 88);
    }

    /**
     * Creates or updates an offer for $companyId from the builder fields ($data = the
     * "package" array). A sent offer is locked. $image replaces the current image;
     * $imagePath reuses one already stored (the admin campaign image).
     */
    public function save(
        int $companyId,
        array $data,
        ?PackageOffer $offer = null,
        ?UploadedFile $image = null,
        string $createdByType = 'company',
        ?int $createdById = null,
        ?array $storedImage = null,
        string $errorPrefix = 'package.'
    ): PackageOffer {
        $offer ??= new PackageOffer([
            'company_id' => $companyId,
            'created_by_type' => $createdByType,
            'created_by_id' => $createdById,
            'status' => PackageOffer::STATUS_ACTIVE,
        ]);

        if ($offer->isLocked()) {
            throw ValidationException::withMessages([
                $errorPrefix . 'title_en' => __('company/messages.package_offers.locked'),
            ]);
        }

        $items = $this->normalizeItems($data['items'], $companyId, $errorPrefix . 'items');
        $subtotal = round(array_sum(array_column($items, 'total')), 2);
        $discount = min($subtotal, round((float) ($data['discount'] ?? 0), 2));
        $price = round($subtotal - $discount, 2);

        if ($price < 1000 || $subtotal > 9999999999999) {
            throw ValidationException::withMessages([
                $errorPrefix . 'items' => __('company/messages.quote.balance_limit'),
            ]);
        }

        $offer->fill([
            'title_en' => trim($data['title_en']),
            'title_ar' => filled($data['title_ar'] ?? null) ? trim($data['title_ar']) : null,
            'description_en' => filled($data['description_en'] ?? null) ? trim($data['description_en']) : null,
            'description_ar' => filled($data['description_ar'] ?? null) ? trim($data['description_ar']) : null,
            'max_load_kw' => round((float) $data['max_load_kw'], 2),
            'currency' => $data['currency'],
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'price' => $price,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        $stored = $image ? $this->storeImage($image) : $storedImage;
        $oldImage = $offer->image_path;

        if ($stored) {
            $offer->fill([
                'image_path' => $stored['path'],
                'image_width' => $stored['width'],
                'image_height' => $stored['height'],
            ]);
        } elseif (!empty($data['remove_image'])) {
            $offer->fill(['image_path' => null, 'image_width' => null, 'image_height' => null]);
        }

        $offer->save();

        // An unsent offer's replaced image is used nowhere else.
        if ($oldImage && $oldImage !== $offer->image_path) {
            File::delete(public_path($oldImage));
        }

        return $offer;
    }
}
