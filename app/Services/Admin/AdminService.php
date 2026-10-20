<?php

namespace App\Services\Admin;

use App\Models\Quote;
use App\Repositories\Admin\AdminRepository;

class AdminService
{
    protected $adminRepository;

    public function __construct(AdminRepository $adminRepository)
    {
        $this->adminRepository = $adminRepository;
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

    public function getCustomers(int $perPage = 15)
    {
        return $this->adminRepository->getCustomers($perPage);
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

    //Quote Requests End




}
