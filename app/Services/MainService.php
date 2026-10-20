<?php

namespace App\Services;

use App\Repositories\MainRepository;
use Illuminate\Database\Eloquent\Collection;

class MainService
{
    protected MainRepository $mainRepository;

    public function __construct(MainRepository $mainRepository)
    {
        $this->mainRepository = $mainRepository;
    }

    public function getCompanies()
    {
        return $this->mainRepository->getCompanies();
    }

    public function verifiedCount(): int
    {
        return $this->mainRepository->verifiedCount();
    }

    public function pendingCount(): int
    {
        return $this->mainRepository->pendingCount();
    }

    public function inactiveCount(): int
    {
        return $this->mainRepository->inactiveCount();
    }

    public function getCompanyBySlug(string $slug)
    {
        return $this->mainRepository->getCompanyBySlug($slug);
    }

    public function registerCompany(array $data): array
    {
        return $this->mainRepository->registerCompany($data);
    }

    public function storeCustomer(array $data): array
    {
        return $this->mainRepository->storeCustomer($data);
    }

    public function getWebsiteCompanyCarousel(): Collection
    {
        return $this->mainRepository->getWebsiteCompanyCarousel();
    }

}