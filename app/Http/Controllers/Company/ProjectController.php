<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\Company\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => [
                'nullable',
                Rule::in(['accepted', 'in_progress', 'completed']),
            ],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $projects = $this->projectService->getProjects($filters);
        $metrics = $this->projectService->getProjectMetrics();

        return view('company.projects.index', compact('projects', 'metrics'));
    }

    public function show(int $id)
    {
        $project = $this->projectService->getProjectById($id);

        abort_if(!$project, 404);

        return view('company.projects.show', compact('project'));
    }


    public function updateProgress(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['accepted', 'in_progress', 'completed']),
            ],
            'progress_percent' => [
                'nullable',
                'integer',
                'between:0,100',
            ],
            'message' => ['nullable', 'string', 'max:500'],
            'visible_to_customer' => ['nullable', 'boolean'],
            'milestones' => ['nullable', 'array'],
            'milestones.*.status' => [
                'required',
                Rule::in(['pending', 'in_progress', 'completed']),
            ],
        ]);

        $validated['visible_to_customer'] =
            $request->boolean('visible_to_customer', true);

        try {
            $this->projectService->updateProgress($id, $validated);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()
            ->route('company.projects.show', $id)
            ->with('success', 'Project progress updated successfully.');
    }



}
