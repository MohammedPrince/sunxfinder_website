<?php

namespace App\Services\API;

use App\Models\CustomerDeviceToken;
use App\Repositories\API\CustomerRepository;

class CustomerService
{
    protected $customerRepository;

    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    public function login($data)
    {
        return $this->customerRepository->login($data);
    }

    public function mainData()
    {
        return $this->customerRepository->mainData();
    }


    public function createAccount(array $data): array
    {
        return $this->customerRepository->createAccount($data);
    }

    public function getProfile()
    {
        return $this->customerRepository->getProfile();
    }

    public function updateProfile(array $data): array
    {
        return $this->customerRepository->updateProfile($data);
    }

    public function getCities()
    {
        return $this->customerRepository->getCities();
    }

    public function getCompanies($filters)
    {
        return $this->customerRepository->getCompanies($filters);
    }

    public function getCompany($id)
    {
        return $this->customerRepository->getCompany($id);
    }

    public function getServices()
    {
        return $this->customerRepository->getServices();
    }

    public function appliancesCalculator(): array
    {
        return $this->customerRepository->appliancesCalculator();
    }

    public function getCarousels(): array
    {
        return $this->customerRepository->getCarousels();
    }


    public function storeQuote(array $data): array
    {
        return $this->customerRepository->storeQuote($data);
    }

    public function getMyQuotes($filters): array
    {
        return $this->customerRepository->getMyQuotes($filters);
    }

    public function acceptOffer(array $data)
    {
        return $this->customerRepository->acceptOffer($data);
    }

    public function rejectOffer(array $data)
    {
        return $this->customerRepository->rejectOffer($data);
    }

    public function getProjectProgress(int $projectId)
    {
        return $this->customerRepository->getProjectProgress($projectId);
    }

    public function saveCustomerDeviceToken(
        int $customerId,
        string $token,
        string $platform,
        string $lang = 'en',
        ?string $deviceName = null,
        ?bool $isActive = null
    ): CustomerDeviceToken {
        return $this->customerRepository->saveCustomerDeviceToken(
            $customerId,
            $token,
            $platform,
            $lang,
            $deviceName,
            $isActive
        );
    }

    public function getCustomerNotifications(array $filters = []): array
    {
        return $this->customerRepository->getCustomerNotifications($filters);
    }

    public function markNotificationAsRead($notificationId): array
    {
        return $this->customerRepository->markNotificationAsRead($notificationId);
    }

    public function markAllNotificationsAsRead(): array
    {
        return $this->customerRepository->markAllNotificationsAsRead();
    }

    public function deleteAccount(): array
    {
        return $this->customerRepository->deleteAccount();
    }


    public function logout()
    {
        return $this->customerRepository->logout();
    }

}
