<?php

namespace App\Services\Company;

use App\Repositories\Company\ProjectRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\OfferPriceChange;
use App\Models\Project;
use App\Models\ProjectCancellation;

class ProjectService
{
    public function __construct(
        private ProjectRepository $projectRepository
    ) {
    }

    public function getProjects(array $filters = []): LengthAwarePaginator
    {
        return $this->projectRepository->getProjects($filters);
    }

    public function getProjectMetrics(): array
    {
        return $this->projectRepository->getProjectMetrics();
    }

    public function getProjectById(int $id): ?Project
    {
        return $this->projectRepository->getProjectById($id);
    }

    public function updateProgress(int $projectId, array $data): Project
    {
        $project = $this->projectRepository->getProjectById($projectId);

        abort_if(!$project, 404, 'Project not found.');

        return $this->projectRepository->updateProgress($projectId, $data);
    }

    public function getWithdrawalStanding(): array
    {
        return $this->projectRepository->getWithdrawalStanding();
    }

    public function withdraw(int $projectId, array $data): ProjectCancellation
    {
        return $this->projectRepository->withdraw($projectId, $data);
    }

    public function cancelWithdrawRequest(int $projectId): void
    {
        $this->projectRepository->cancelWithdrawRequest($projectId);
    }

    public function proposePriceChange(int $projectId, array $data): OfferPriceChange
    {
        return $this->projectRepository->proposePriceChange($projectId, $data);
    }

    public function cancelPriceChange(int $projectId): void
    {
        $this->projectRepository->cancelPriceChange($projectId);
    }



}
