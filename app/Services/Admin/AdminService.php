<?php

namespace App\Services\Admin;

use App\Models\ActivityLog;
use App\Models\ArchivedItem;
use App\Models\AppVersion;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Rating;
use App\Models\RatingQuestion;
use App\Repositories\Admin\AdminRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminService
{
    protected $adminRepository;

    public function __construct(AdminRepository $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }

    public function getDashboardData(): array
    {
        return $this->adminRepository->getDashboardData();
    }

    public function getTopbarData(): array
    {
        return $this->adminRepository->getTopbarData();
    }

    public function getReportsData(array $filters): array
    {
        return $this->adminRepository->getReportsData($filters);
    }

    public function getReportExport(string $section, array $filters): array
    {
        return $this->adminRepository->getReportExport($section, $filters);
    }

    public function adminEmailExists(string $email): bool
    {
        return $this->adminRepository->adminEmailExists($email);
    }

    public function sendPasswordResetOtp(string $email, string $locale = 'en'): array
    {
        return $this->adminRepository->sendPasswordResetOtp($email, $locale);
    }

    public function resetPasswordWithOtp(string $email, string $code, string $password): array
    {
        return $this->adminRepository->resetPasswordWithOtp($email, $code, $password);
    }

    //Compaines Managment Start

    public function getCompanies()
    {
        return $this->adminRepository->getCompanies();
    }

    public function verifiedCount(): int
    {
        return $this->adminRepository->verifiedCount();
    }

    public function pendingCount()
    {
        return $this->adminRepository->pendingCount();
    }

    public function inactiveCount(): int
    {
        return $this->adminRepository->inactiveCount();
    }

    public function addNewCompany(array $data): array
    {
        return $this->adminRepository->addNewCompany($data);
    }

    public function getCompanyById(int $id): array
    {
        return $this->adminRepository->getCompanyById($id);
    }

    public function updateCompany(int $id, array $data): array
    {
        return $this->adminRepository->updateCompany($id, $data);
    }

    public function deleteCompany($id)
    {
        return $this->adminRepository->deleteCompany($id);
    }

    //Compaines Managment END

    //Customer Managment START

    public function getCustomers(int $perPage = 10, ?string $status = null)
    {
        return $this->adminRepository->getCustomers($perPage, $status);
    }

    public function setCustomerAppStatus(int $id, string $status, ?string $messageEn, ?string $messageAr, bool $push): array
    {
        return $this->adminRepository->setCustomerAppStatus($id, $status, $messageEn, $messageAr, $push);
    }


    public function customerAccountCount(): int
    {
        return $this->adminRepository->customerAccountCount();
    }

    public function activeCustomerAccountCount(): int
    {
        return $this->adminRepository->activeCustomerAccountCount();
    }

    public function pendingCustomerAccountCount(): int
    {
        return $this->adminRepository->pendingCustomerAccountCount();
    }

    public function inactiveCustomerAccountCount(): int
    {
        return $this->adminRepository->inactiveCustomerAccountCount();
    }

    public function createCustomer(array $data): array
    {
        return $this->adminRepository->createCustomer($data);
    }

    public function getCustomerById(int $id): array
    {
        return $this->adminRepository->getCustomerById($id);
    }

    public function updateCustomer(int $id, array $data): array
    {
        return $this->adminRepository->updateCustomer($id, $data);
    }

    public function deleteCustomer(int $id): array
    {
        return $this->adminRepository->deleteCustomer($id);
    }

    public function signedInCustomerCount(): int
    {
        return $this->adminRepository->signedInCustomerCount();
    }

    public function signOutCustomer(int $id): array
    {
        return $this->adminRepository->signOutCustomer($id);
    }

    public function forceLogoutAllCustomers(): array
    {
        return $this->adminRepository->forceLogoutAllCustomers();
    }

    //Customer Managment End


    //Appliance Groups and Items Start

    public function showApplianceGroups()
    {
        return $this->adminRepository->showApplianceGroups();
    }

    public function createApplianceGroup()
    {
        return $this->adminRepository->createApplianceGroup();
    }

    public function storeApplianceGroup(array $data): array
    {
        return $this->adminRepository->storeApplianceGroup($data);
    }

    public function editApplianceGroup(int $id)
    {
        return $this->adminRepository->editApplianceGroup($id);
    }

    public function updateApplianceGroup(int $id, array $data): array
    {
        return $this->adminRepository->updateApplianceGroup($id, $data);
    }

    public function destroyApplianceGroup(int $id): array
    {
        return $this->adminRepository->destroyApplianceGroup($id);
    }

    //Appliance Groups and Items End

    //Supplier and Products Managment Start

    public function showSuppliers(): array
    {
        return $this->adminRepository->showSuppliers();
    }
    public function storeSupplier(array $data): array
    {
        return $this->adminRepository->storeSupplier($data);
    }
    public function editSupplier(int $id): array
    {
        return $this->adminRepository->editSupplier($id);
    }
    public function updateSupplier(int $id, array $data): array
    {
        return $this->adminRepository->updateSupplier($id, $data);
    }
    public function destroySupplier(int $id): array
    {
        return $this->adminRepository->destroySupplier($id);
    }

    public function showProducts(): array
    {
        return $this->adminRepository->showProducts();
    }
    public function productFormData(): array
    {
        return $this->adminRepository->productFormData();
    }
    public function storeProduct(array $data): array
    {
        return $this->adminRepository->storeProduct($data);
    }
    public function editProduct(int $id): array
    {
        return $this->adminRepository->editProduct($id);
    }
    public function updateProduct(int $id, array $data): array
    {
        return $this->adminRepository->updateProduct($id, $data);
    }
    public function destroyProduct(int $id): array
    {
        return $this->adminRepository->destroyProduct($id);
    }


    //Supplier and Products Managment End

    //Company Carousel Start

    public function getCompanyCarouselPageData(): array
    {
        return ['entries' => $this->adminRepository->getCompanyCarouselEntries()];
    }

    public function getNextCompanyCarouselSortOrder(): int
    {
        return $this->adminRepository->getNextCompanyCarouselSortOrder();
    }

    public function getCompanyCarouselFormData(?int $id = null): array
    {
        return [
            'entry' => $id ? $this->adminRepository->findCompanyCarouselEntry($id) : null,
            'companies' => $this->adminRepository->getCompaniesForCarouselSelect(),
        ];
    }

    public function createCompanyCarouselEntry(array $data): array
    {
        return $this->adminRepository->createCompanyCarouselEntry($data);
    }

    public function updateCompanyCarouselEntry(int $id, array $data): array
    {
        return $this->adminRepository->updateCompanyCarouselEntry($id, $data);
    }

    public function deleteCompanyCarouselEntry(int $id): array
    {
        return $this->adminRepository->deleteCompanyCarouselEntry($id);
    }

    //Company Carousel End

    //App Versions Start

    public function getAppVersions()
    {
        return $this->adminRepository->getAppVersions();
    }

    public function findAppVersion(int $id): ?AppVersion
    {
        return $this->adminRepository->findAppVersion($id);
    }

    public function createAppVersion(array $data): array
    {
        return $this->adminRepository->createAppVersion($data);
    }

    public function updateAppVersion(int $id, array $data): array
    {
        return $this->adminRepository->updateAppVersion($id, $data);
    }

    public function deleteAppVersion(int $id): array
    {
        return $this->adminRepository->deleteAppVersion($id);
    }

    //App Versions End

    //APK Direct Download Start

    public function storeApkChunk(string $uploadId, int $index, string $chunkPath): array
    {
        return $this->adminRepository->storeApkChunk($uploadId, $index, $chunkPath);
    }

    public function finishApkUpload(string $uploadId, int $total, ?string $version): array
    {
        return $this->adminRepository->finishApkUpload($uploadId, $total, $version);
    }

    public function setApkDownloadEnabled(bool $enabled): array
    {
        return $this->adminRepository->setApkDownloadEnabled($enabled);
    }

    public function deleteApk(): array
    {
        return $this->adminRepository->deleteApk();
    }

    public function setPlayStoreButtonEnabled(bool $enabled): array
    {
        return $this->adminRepository->setPlayStoreButtonEnabled($enabled);
    }

    //APK Direct Download End

    //Quote Requests Start
    public function getQuoteRequests(array $filters = [])
    {
        return $this->adminRepository->getQuoteRequests($filters);
    }

    public function getQuoteRequestMetrics(): array
    {
        return $this->adminRepository->getQuoteRequestMetrics();
    }

    public function getQuoteRequestById(int $id): ?Quote
    {
        return $this->adminRepository->getQuoteRequestById($id);
    }

    public function getAdminProjects(array $filters = []): LengthAwarePaginator
    {
        return $this->adminRepository->getAdminProjects($filters);
    }

    public function getAdminProjectById(int $id): ?Project
    {
        return $this->adminRepository->getAdminProjectById($id);
    }

    public function getAdminProjectMetrics(): array
    {
        return $this->adminRepository->getAdminProjectMetrics();
    }

    public function getAdminProjectCompanies()
    {
        return $this->adminRepository->getAdminProjectCompanies();
    }

    public function getAssignableCompanies()
    {
        return $this->adminRepository->getAssignableCompanies();
    }

    public function getQuoteNudgedAt(int $quoteId)
    {
        return $this->adminRepository->getQuoteNudgedAt($quoteId);
    }

    public function cancelQuoteRequest(int $id, string $reason, bool $notifyCustomer): array
    {
        return $this->adminRepository->cancelQuoteRequest($id, $reason, $notifyCustomer);
    }

    public function reassignQuoteRequest(int $id, ?int $companyId): array
    {
        return $this->adminRepository->reassignQuoteRequest($id, $companyId);
    }

    public function nudgeCompaniesForQuote(int $id): array
    {
        return $this->adminRepository->nudgeCompaniesForQuote($id);
    }

    //Quote Requests End

    //Notifications Start
    public function publishNotification(array $data): array
    {
        $notification = $this->adminRepository->publishNotification($data);

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
        return $this->adminRepository->getNotifications($filters);
    }

    public function getNotificationFormData(): array
    {
        return $this->adminRepository->getNotificationFormData();
    }

    public function getNotificationDetails(int $id): array
    {
        return $this->adminRepository->getNotificationDetails($id);
    }

    public function deleteNotification(int $id): array
    {
        return $this->adminRepository->deleteNotification($id);
    }
    //Notifications End

    //Ratings Start
    public function getRatings(array $filters = []): LengthAwarePaginator
    {
        return $this->adminRepository->getRatings($filters);
    }

    public function getRatingMetrics(): array
    {
        return $this->adminRepository->getRatingMetrics();
    }

    public function getRatingById(int $id): ?Rating
    {
        return $this->adminRepository->getRatingById($id);
    }

    public function setRatingVisibility(int $id, bool $visible): array
    {
        return $this->adminRepository->setRatingVisibility($id, $visible);
    }

    public function deleteRating(int $id): array
    {
        return $this->adminRepository->deleteRating($id);
    }

    public function getRatingCompanyOptions()
    {
        return $this->adminRepository->getRatingCompanyOptions();
    }
    //Ratings End

    //Rating Questions Start
    public function getRatingQuestions()
    {
        return $this->adminRepository->getRatingQuestions();
    }

    public function findRatingQuestion(int $id): ?RatingQuestion
    {
        return $this->adminRepository->findRatingQuestion($id);
    }

    public function createRatingQuestion(array $data): array
    {
        return $this->adminRepository->createRatingQuestion($data);
    }

    public function updateRatingQuestion(int $id, array $data): array
    {
        return $this->adminRepository->updateRatingQuestion($id, $data);
    }

    public function deleteRatingQuestion(int $id): array
    {
        return $this->adminRepository->deleteRatingQuestion($id);
    }
    //Rating Questions End

    //Rating Values Start
    public function getRatingValues()
    {
        return $this->adminRepository->getRatingValues();
    }

    public function updateRatingValues(array $values): array
    {
        return $this->adminRepository->updateRatingValues($values);
    }
    //Rating Values End

    //Settings Start
    public function getSettings(): array
    {
        return $this->adminRepository->getSettings();
    }

    public function updateSettings(array $data): array
    {
        return $this->adminRepository->updateSettings($data);
    }

    public function setCustomerQuoteLimit(int $customerId, ?int $limit): array
    {
        return $this->adminRepository->setCustomerQuoteLimit($customerId, $limit);
    }

    public function getCompanyEmailOptions()
    {
        return $this->adminRepository->getCompanyEmailOptions();
    }

    public function sendCompanyEmail(array $data): array
    {
        return $this->adminRepository->sendCompanyEmail($data);
    }

    public function createApiEndpoint(array $data): array
    {
        return $this->adminRepository->createApiEndpoint($data);
    }

    public function updateApiEndpoint(int $id, array $data): array
    {
        return $this->adminRepository->updateApiEndpoint($id, $data);
    }

    public function activateApiEndpoint(int $id): array
    {
        return $this->adminRepository->activateApiEndpoint($id);
    }

    public function deleteApiEndpoint(int $id): array
    {
        return $this->adminRepository->deleteApiEndpoint($id);
    }
    //Settings End

    //Database Backups Start
    public function getDatabaseBackups(): array
    {
        return $this->adminRepository->getDatabaseBackups();
    }

    public function createDatabaseBackup(): array
    {
        return $this->adminRepository->createDatabaseBackup();
    }

    public function getDatabaseBackupPath(string $name): ?string
    {
        return $this->adminRepository->getDatabaseBackupPath($name);
    }

    public function deleteDatabaseBackup(string $name): array
    {
        return $this->adminRepository->deleteDatabaseBackup($name);
    }
    //Database Backups End

    //Background Jobs Start
    public function getQueueOverview(): array
    {
        return $this->adminRepository->getQueueOverview();
    }

    public function getFailedJobs(array $filters = []): LengthAwarePaginator
    {
        return $this->adminRepository->getFailedJobs($filters);
    }

    public function getFailedJob(string $uuid): ?object
    {
        return $this->adminRepository->getFailedJob($uuid);
    }

    public function retryFailedJobs(array|string $uuids): array
    {
        return $this->adminRepository->retryFailedJobs($uuids);
    }

    public function deleteFailedJobs(array|string $uuids): array
    {
        return $this->adminRepository->deleteFailedJobs($uuids);
    }
    //Background Jobs End

    //Activity Logs Start
    public function getActivityLogs(array $filters = []): LengthAwarePaginator
    {
        return $this->adminRepository->getActivityLogs($filters);
    }

    public function activityLogsForExport(array $filters = [])
    {
        return $this->adminRepository->activityLogsForExport($filters);
    }

    public function getActivityLogById(int $id): ?ActivityLog
    {
        return $this->adminRepository->getActivityLogById($id);
    }

    public function getActivityLogMetrics(): array
    {
        return $this->adminRepository->getActivityLogMetrics();
    }
    //Activity Logs End

    public function getArchivedItems(array $filters = []): LengthAwarePaginator
    {
        return $this->adminRepository->getArchivedItems($filters);
    }

    public function deleteArchivedItem(ArchivedItem $item): void
    {
        $this->adminRepository->deleteArchivedItem($item);
    }




}
