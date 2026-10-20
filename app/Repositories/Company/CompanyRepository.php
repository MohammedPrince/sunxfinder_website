<?php

namespace App\Repositories\Company;

use App\Models\CompanyQuotation;
use App\Models\Product;
use App\Models\Quote;
use App\Models\SolarCompany;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class CompanyRepository
{
    public function __construct()
    {
        //
    }


    //Company profile START

    public function getCompany(int $id): array
    {
        try {
            $company = SolarCompany::find($id);

            if (!$company) {
                return [
                    'success' => false,
                    'message' => 'Company not found.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Company retrieved successfully.',
                'company' => $company,
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'Unable to retrieve company details.',
            ];
        }
    }


    public function updateCompanyProfile(int $id, array $data): array
    {
        try {
            $company = SolarCompany::find($id);

            if (!$company) {
                return [
                    'success' => false,
                    'message' => 'Company not found.',
                ];
            }

            $company->name = $data['name'];
            $company->city = $data['city'];
            $company->license_number = $data['license_number'];
            $company->description = $data['description'];
            $company->ar_description = $data['ar_description'] ?? null;
            $company->services = $data['services'];
            $company->ar_services = $data['ar_services'] ?? null;
            $company->phone = $data['phone'];
            $company->email = $data['email'];
            $company->social_media = $data['social_media'] ?? null;
            $company->year_of_exp = $data['year_of_exp'] ?? null;
            $company->number_of_projects = $data['number_of_projects'] ?? null;

            // Update password only when a new password is provided.
            // The password is already hashed by the controller.
            if (!empty($data['password'])) {
                $company->password = $data['password'];
            }

            // Update logo only when a new logo is provided.
            if (!empty($data['logo_path'])) {
                $company->logo_path = $data['logo_path'];
            }

            if (!$company->save()) {
                return [
                    'success' => false,
                    'message' => 'Unable to update the company profile.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Company profile updated successfully.',
                'company' => $company->fresh(),
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => 'An error occurred while updating the company profile.',
            ];
        }
    }

    public function paginateForCompany(
        int $companyId,
        array $filters = []
    ): LengthAwarePaginator {
        $query = Product::query()
            ->where('company_id', $companyId)
            ->with(['supplier', 'specs'])
            ->latest();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        return $query->paginate(15)->withQueryString();
    }

    public function findForCompany(
        int $companyId,
        int $productId
    ): Product {
        return Product::query()
            ->where('company_id', $companyId)
            ->with(['supplier', 'specs'])
            ->findOrFail($productId);
    }

    public function createForCompany(
        int $companyId,
        array $data
    ): Product {
        $product = new Product();

        // Never accept company_id from the submitted form.
        $product->company_id = $companyId;
        $product->supplier_id = $data['supplier_id'];
        $product->name = $data['name'];
        $product->model = $data['model'] ?? null;
        $product->type = $data['type'];
        $product->description = $data['description'] ?? null;
        $product->is_active = $data['is_active'] ?? true;
        $product->save();

        return $product;
    }

    public function updateForCompany(
        int $companyId,
        int $productId,
        array $data
    ): Product {
        $product = $this->findForCompany($companyId, $productId);

        $product->supplier_id = $data['supplier_id'];
        $product->name = $data['name'];
        $product->model = $data['model'] ?? null;
        $product->type = $data['type'];
        $product->description = $data['description'] ?? null;
        $product->is_active = $data['is_active'] ?? false;
        $product->save();

        return $product;
    }

    public function deleteForCompany(
        int $companyId,
        int $productId
    ): bool {
        $product = $this->findForCompany($companyId, $productId);

        return (bool) $product->delete();
    }

    //Company profile END


    //Quote Requests Start

    private function authenticatedCompanyId(): int
    {
        $company = Auth::guard('company')->user();

        abort_if(
            !$company || !$company->getAuthIdentifier(),
            403,
            'Company authentication required.'
        );

        return (int) $company->getAuthIdentifier();
    }

    public function getQuoteRequests(array $filters = []): LengthAwarePaginator
    {
        $companyId = $this->authenticatedCompanyId();

        $query = Quote::query()
            ->with([
                'customer',
                'company',
                'companyQuotations' => function ($quotationQuery) use ($companyId) {
                    // Load this company's own offers, including withdrawn offers.
                    $quotationQuery
                        ->where('company_id', $companyId)
                        ->select([
                            'id',
                            'quote_id',
                            'company_id',
                            'price',
                            'currency',
                            'status',
                            'created_at',
                            'updated_at',
                        ]);
                },
            ])
            // Requests assigned to this company, available to all, or already offered on.
            ->visibleToCompany($companyId)
            ->when(!empty($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);

                $query->where(function ($q) use ($search) {
                    if (ctype_digit($search)) {
                        $q->orWhere('id', (int) $search)
                            ->orWhere('customer_id', (int) $search);
                    }

                    $q->orWhere('service_needed', 'like', "%{$search}%")
                        ->orWhere('estimated_load', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhere('daily_load', 'like', "%{$search}%")
                        ->orWhere('peak_load', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return $query->paginate(15)->withQueryString();
    }

    public function getQuoteRequestMetrics(): array
    {
        $companyId = $this->authenticatedCompanyId();

        $query = Quote::query()->visibleToCompany($companyId);

        return [
            'total' => (clone $query)->count(),

            'pending' => (clone $query)
                ->where('status', 'pending')
                ->count(),

            'in_progress' => (clone $query)
                ->where('status', 'accepted')
                ->count(),

            'completed' => (clone $query)
                ->where('status', 'completed')
                ->count(),
        ];
    }

    public function getQuoteRequestById(int $id): ?Quote
    {
        $companyId = $this->authenticatedCompanyId();

        return Quote::query()
            ->with(['customer', 'company'])
            ->where('id', $id)
            ->visibleToCompany($companyId)
            ->first();
    }

    public function getQuoteRequestCount(): int
    {
        $companyId = $this->authenticatedCompanyId();

        return Quote::query()
            ->where('company_id', $companyId)
            ->count();
    }

    public function submitQuoteOffer(int $quoteId, array $data): CompanyQuotation
    {
        $company = Auth::guard('company')->user();

        abort_if(
            !$company || !$company->getAuthIdentifier(),
            403,
            'Company authentication required.'
        );

        $companyId = (int) $company->getAuthIdentifier();

        return DB::transaction(function () use ($quoteId, $companyId, $data) {
            // Lock the request to prevent offer submission while it is being closed.
            $quote = Quote::query()
                ->whereKey($quoteId)
                ->lockForUpdate()
                ->firstOrFail();

            // Offers can only be created or edited while the request is pending.
            if ($quote->status !== 'pending') {
                throw ValidationException::withMessages([
                    'price' => 'This request is no longer accepting or updating offers.',
                ]);
            }

            // Find this company's existing offer for the request.
            $quotation = CompanyQuotation::query()
                ->where('quote_id', $quote->id)
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->first();

            // Allow marketplace requests, requests assigned to this company,
            // or requests this company has already offered on.
            if (
                !$quotation &&
                $quote->company_id !== null &&
                (int) $quote->company_id !== $companyId
            ) {
                abort(403, 'This quote request is not available to your company.');
            }

            if ($quotation) {
                // Once selected by the customer, the offer is permanently locked.
                if ($quotation->status === 'selected') {
                    throw ValidationException::withMessages([
                        'price' => 'The customer has selected your offer. You can no longer edit it.',
                    ]);
                }

                // Only submitted offers may be edited.
                if ($quotation->status !== 'submitted') {
                    throw ValidationException::withMessages([
                        'price' => 'This offer cannot be edited because it is no longer active.',
                    ]);
                }

                // Update the existing row; do not insert another quotation.
                $quotation->customer_id = $quote->customer_id;
                $quotation->price = $data['price'];
                $quotation->currency = $data['currency'];
                $quotation->message = $data['message'] ?? null;
                $quotation->ar_message = $data['ar_message'] ?? null;
                $quotation->save();

                return $quotation->refresh();
            }

            // No existing offer: create the first offer for this company/request.
            $quotation = new CompanyQuotation();
            $quotation->quote_id = $quote->id;
            $quotation->customer_id = $quote->customer_id;
            $quotation->company_id = $companyId;
            $quotation->price = $data['price'];
            $quotation->currency = $data['currency'];
            $quotation->message = $data['message'] ?? null;
            $quotation->ar_message = $data['ar_message'] ?? null;
            $quotation->status = 'submitted';
            $quotation->save();

            return $quotation;
        });
    }

    public function getCompanyQuotationForQuote(int $quoteId): ?CompanyQuotation
    {
        $company = Auth::guard('company')->user();

        abort_if(
            !$company || !$company->getAuthIdentifier(),
            403,
            'Company authentication required.'
        );

        return CompanyQuotation::query()
            ->where('quote_id', $quoteId)
            ->where('company_id', $company->getAuthIdentifier())
            // Include withdrawn offers so the company sees its final offer status.
            ->first();
    }

    public function getCompetitorOfferPrices(int $quoteId)
    {
        $company = Auth::guard('company')->user();

        abort_if(
            !$company || !$company->getAuthIdentifier(),
            403,
            'Company authentication required.'
        );

        return CompanyQuotation::query()
            ->where('quote_id', $quoteId)
            ->where('company_id', '!=', $company->getAuthIdentifier())
            ->where('status', 'submitted')
            ->orderBy('currency')
            ->orderBy('price')
            ->get(['price', 'currency']);
    }

    //Quote Requests End


}
