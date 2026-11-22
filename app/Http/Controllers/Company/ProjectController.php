<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\OfferPriceChange;
use App\Models\ProjectCancellation;
use App\Services\Company\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
                Rule::in(['accepted', 'in_progress', 'handover_pending', 'completed']),
            ],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $projects = $this->projectService->getProjects($filters);
        $metrics = $this->projectService->getProjectMetrics();
        $withdrawals = $this->projectService->getWithdrawalStanding();

        return view('company.projects.index', compact('projects', 'metrics', 'withdrawals'));
    }

    public function show(int $id)
    {
        $project = $this->projectService->getProjectById($id);

        abort_if(!$project, 404);

        $withdrawals = $this->projectService->getWithdrawalStanding();

        return view('company.projects.show', compact('project', 'withdrawals'));
    }

    /** Back out of the job: done right away before work starts, otherwise a request for SunXFinder. */
    public function withdraw(Request $request, int $id)
    {
        $validated = $request->validate([
            'reason' => ['required', Rule::in(array_keys(ProjectCancellation::REASONS))],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:reason,other'],
        ]);

        try {
            $cancellation = $this->projectService->withdraw($id, $validated);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        if ($cancellation->status === ProjectCancellation::WITHDRAWN) {
            return redirect()
                ->route('company.projects.index')
                ->with('success', __('company/messages.deal.withdrawn_done'));
        }

        return redirect()
            ->route('company.projects.show', $id)
            ->with('success', __('company/messages.deal.request_sent'));
    }

    public function cancelWithdrawRequest(int $id)
    {
        try {
            $this->projectService->cancelWithdrawRequest($id);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('company.projects.show', $id)
            ->with('success', __('company/messages.deal.request_cancelled'));
    }

    /** Ask the customer to accept a new price before installation starts. */
    public function proposePriceChange(Request $request, int $id)
    {
        $validated = $request->validate([
            'new_price' => ['required', 'numeric', 'min:1000', 'max:9999999999999'],
            'reason' => ['required', Rule::in(array_keys(OfferPriceChange::REASONS))],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:reason,other'],
        ]);

        try {
            $this->projectService->proposePriceChange($id, $validated);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('company.projects.show', $id)
            ->with('success', __('company/messages.deal.price_sent'));
    }

    public function cancelPriceChange(int $id)
    {
        try {
            $this->projectService->cancelPriceChange($id);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('company.projects.show', $id)
            ->with('success', __('company/messages.deal.price_cancelled'));
    }


    public function updateProgress(Request $request, int $id)
    {
        $validated = $request->validate([
            // "completed" here means "the company finished": the project becomes
            // handover_pending and only the customer's confirmation completes it.
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
            ->with('success', __('company/messages.projects.progress_updated'));
    }



}
