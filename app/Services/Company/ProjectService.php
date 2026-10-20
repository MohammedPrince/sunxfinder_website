<?php

namespace App\Services\Company;

use App\Repositories\Company\ProjectRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Project;

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



}
