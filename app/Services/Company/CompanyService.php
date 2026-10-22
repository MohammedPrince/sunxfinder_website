<?php

namespace App\Services\Company;

use App\Models\CompanyQuotation;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Supplier;
use App\Repositories\Company\CompanyRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyService
{
    protected $companyRepository;

    public function __construct(CompanyRepository $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }

    public function getDashboardData(): array
    {
        return $this->companyRepository->getDashboardData();
    }

    public function getTopbarData(): array
    {
        return $this->companyRepository->getTopbarData();
    }

    public function getCompanyReportsData(array $filters): array
    {
        return $this->companyRepository->getCompanyReportsData($filters);
    }

    public function getCompanyReportExport(string $section, array $filters): array
    {
        return $this->companyRepository->getCompanyReportExport($section, $filters);
    }

    public function companyEmailExists(string $email): bool
    {
        return $this->companyRepository->companyEmailExists($email);
    }

    public function sendPasswordResetOtp(string $email, string $locale = 'en'): array
    {
        return $this->companyRepository->sendPasswordResetOtp($email, $locale);
    }

    public function resetPasswordWithOtp(string $email, string $code, string $password): array
    {
        return $this->companyRepository->resetPasswordWithOtp($email, $code, $password);
    }

    //Company profile managment START
    public function getCompany(int $id): array
    {
        return $this->companyRepository->getCompany($id);
    }

    public function updateCompanyProfile(int $id, array $data): array
    {
        return $this->companyRepository->updateCompanyProfile($id, $data);
    }

    public function list(int $companyId, array $filters = []): LengthAwarePaginator
    {
        return $this->companyRepository->paginateForCompany($companyId, $filters);
    }

    public function find(int $companyId, int $productId): Product
    {
        return $this->companyRepository->findForCompany($companyId, $productId);
    }

    public function suppliers()
    {
        return Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function save(
        int $companyId,
        array $data,
        ?int $productId = null
    ): Product {
        // Only select an active supplier from the admin-managed catalogue.
        $supplierExists = Supplier::query()
            ->whereKey($data['supplier_id'])
            ->where('is_active', true)
            ->exists();

        if (!$supplierExists) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Please select a valid supplier.',
            ]);
        }

        return DB::transaction(function () use ($companyId, $data, $productId) {
            $specs = $data['specs'] ?? [];
            unset($data['specs']);

            $product = $productId
                ? $this->companyRepository->updateForCompany(
                    $companyId,
                    $productId,
                    $data
                )
                : $this->companyRepository->createForCompany(
                    $companyId,
                    $data
                );

            // Reconcile the product's name/value specifications.
            $product->specs()->delete();

            foreach ($specs as $spec) {
                if (trim($spec['name'] ?? '') === '') {
                    continue;
                }

                // Manual assignment avoids depending on Model::create().
                $item = new \App\Models\ProductSpec();
                $item->product_id = $product->id;
                $item->spec_name = trim($spec['name']);
                $item->spec_value = trim($spec['value'] ?? '');
                $item->save();
            }

            return $product->load(['supplier', 'specs']);
        });
    }

    public function delete(int $companyId, int $productId): bool
    {
        return $this->companyRepository->deleteForCompany(
            $companyId,
            $productId
        );
    }

    //Company profile END


    //Quote Requests Start
    public function getQuoteRequests(array $filters = [])
    {
        return $this->companyRepository->getQuoteRequests($filters);
    }

    public function getQuoteRequestMetrics(): array
    {
        return $this->companyRepository->getQuoteRequestMetrics();
    }

    public function getQuoteRequestById(int $id): ?Quote
    {
        return $this->companyRepository->getQuoteRequestById($id);
    }

    public function getQuoteRequestCount(): int
    {
        return $this->companyRepository->getQuoteRequestCount();
    }

    public function submitQuoteOffer(int $quoteId, array $data): CompanyQuotation
    {
        return $this->companyRepository->submitQuoteOffer($quoteId, $data);
    }

    public function getCompanyQuotationForQuote(int $quoteId): ?CompanyQuotation
    {
        return $this->companyRepository->getCompanyQuotationForQuote($quoteId);
    }

    public function getCompetitorOfferPrices(int $quoteId)
    {
        return $this->companyRepository->getCompetitorOfferPrices($quoteId);
    }

    //Quote Requests End

    //Notifications Start
    public function publishNotification(array $data): array
    {
        $notification = $this->companyRepository->publishNotification($data);

        return [
            'success' => true,
            'message' => app()->getLocale() === 'ar'
                ? 'تم إرسال الإشعار بنجاح.'
                : 'Notification sent successfully.',
            'notification_id' => $notification->id,
        ];
    }

    public function getNotifications(array $filters = []): array
    {
        return $this->companyRepository->getNotifications($filters);
    }

    public function getNotificationFormData(): array
    {
        return $this->companyRepository->getNotificationFormData();
    }

    public function getNotificationDetails(int $id): array
    {
        return $this->companyRepository->getNotificationDetails($id);
    }

    public function deleteNotification(int $id): array
    {
        return $this->companyRepository->deleteNotification($id);
    }
    //Notifications End
}
