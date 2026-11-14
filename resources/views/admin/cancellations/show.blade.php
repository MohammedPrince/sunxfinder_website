@extends('admin.layouts.app')

@php
    $PC = \App\Models\ProjectCancellation::class;
    $reference = \App\Support\OfferWithdrawal::reference((int) $cancellation->quote_id);
@endphp

@section('title', 'Withdrawal ' . $reference)
@section('breadcrumb', 'Withdrawals / ' . $reference)
@section('eyebrow', 'PROJECTS')
@section('page-title', 'Withdrawal on ' . $reference)
@section('page-description', 'What the company asked, where the job stood, and what was decided.')

@section('page-actions')
    <a class="btn btn-light" href="{{ route('admin.cancellations.index') }}">← All withdrawals</a>
@endsection

@section('content')
    @php
        $company = $cancellation->company;
        $customer = $cancellation->customer;
        $offer = $cancellation->offer;
        $project = $cancellation->project;
        $statuses = [
            $PC::REQUESTED => 'Waiting for decision',
            $PC::WITHDRAWN => 'Withdrawn by the company',
            $PC::APPROVED => 'Approved',
            $PC::REJECTED => 'Rejected',
            $PC::CANCELLED => 'Taken back by the company',
        ];
        $statusClasses = [
            $PC::REQUESTED => 'pill-orange', $PC::WITHDRAWN => 'pill-red', $PC::APPROVED => 'pill-red',
            $PC::REJECTED => 'pill-blue', $PC::CANCELLED => 'pill-purple',
        ];
        $stages = ['accepted' => 'Not started', 'in_progress' => 'Installation in progress', 'handover_pending' => 'Waiting for the customer to confirm the handover'];
        $customerName = $customer?->full_name ?: ($customer?->username ?: 'Customer removed');
        $isRequested = $cancellation->status === $PC::REQUESTED;
        $isPriceDecline = $cancellation->reason === $PC::REASON_PRICE_DECLINED;
    @endphp

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="panel admin-project-hero">
        <div class="admin-project-hero-top">
            <div class="admin-project-hero-title">
                <span class="pill {{ $statusClasses[$cancellation->status] ?? 'pill-blue' }}">{{ $statuses[$cancellation->status] ?? $cancellation->status }}</span>
                <h2>{{ $cancellation->reasonText('en') }}</h2>
                <p>{{ $company?->name ?? 'Company removed' }} · customer {{ $customerName }}</p>
            </div>
        </div>

        <div class="admin-project-stats">
            <div>
                <small>Asked</small>
                <strong>{{ $cancellation->created_at?->format('M j, Y') }}</strong>
                <span>{{ $cancellation->created_at?->diffForHumans() }}</span>
            </div>
            <div>
                <small>Job stage then</small>
                <strong>{{ $cancellation->progress_percent }}%</strong>
                <span>{{ $stages[$cancellation->stage] ?? $cancellation->stage }}</span>
            </div>
            <div>
                <small>Offer price</small>
                <strong>{{ $offer ? number_format((float) $offer->price) . ' ' . $offer->currency : '—' }}</strong>
                <span>{{ $cancellation->quote?->quote_name }}</span>
            </div>
            <div>
                <small>Company standing</small>
                <strong>{{ $standing['recent'] }} / {{ $standing['threshold'] }}</strong>
                <span>counted in {{ $standing['window_days'] }} days{{ $standing['flagged'] ? ' · flagged' : '' }}</span>
            </div>
        </div>
    </section>

    <div class="admin-project-layout">
        <div class="admin-project-main">
            <section class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>What the company wrote</h2>
                        <p>Shown to admins only. The customer sees the reason, not this note.</p>
                    </div>
                </div>
                <div class="complaint-description">{{ $cancellation->note ?: 'No note.' }}</div>

                @if ($cancellation->decided_at && !in_array($cancellation->status, [$PC::WITHDRAWN, $PC::REQUESTED], true))
                    <div class="complaint-last-reply">
                        <small>Decision · {{ $cancellation->decided_at->format('M j, Y H:i') }}{{ $cancellation->admin ? ' by ' . $cancellation->admin->username : '' }}</small>
                        <p>{{ $cancellation->admin_note ?: 'No note.' }}</p>
                    </div>
                @endif
            </section>

            @if ($isRequested)
                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Decide</h2>
                            <p>Talk to the company and the customer first. Approving ends the job and reopens the request; rejecting keeps the job going.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.cancellations.approve', $cancellation->id) }}" class="form-grid"
                        onsubmit="return confirm('Approve? The project is cancelled, the customer is told, and the request opens again for other companies.');">
                        @csrf
                        <div class="form-group full-width">
                            <label for="approve_note">Note to the company (optional)</label>
                            <textarea id="approve_note" name="admin_note" rows="3" maxlength="2000"></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label>
                                <input type="hidden" name="counts_against_company" value="0">
                                <input type="checkbox" name="counts_against_company" value="1" checked>
                                Count this withdrawal against the company
                            </label>
                            <span class="form-hint">Untick when the company was not at fault.</span>
                        </div>
                        <div class="form-actions full-width">
                            <button class="btn btn-primary" type="submit">Approve cancellation</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('admin.cancellations.reject', $cancellation->id) }}" class="form-grid" style="margin-top:20px">
                        @csrf
                        <div class="form-group full-width">
                            <label for="reject_note">Why not (sent to the company)</label>
                            <textarea id="reject_note" name="admin_note" rows="3" maxlength="2000" required minlength="5"></textarea>
                        </div>
                        <div class="form-actions full-width">
                            <button class="btn btn-light" type="submit">Reject request</button>
                        </div>
                    </form>
                </section>
            @elseif ($cancellation->isDone() && !$isPriceDecline)
                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>{{ $cancellation->counts_against_company ? 'Counted against the company' : 'Not counted' }}</h2>
                            <p>{{ $cancellation->reopened_offers }} other {{ \Illuminate\Support\Str::plural('offer', $cancellation->reopened_offers) }} reopened for the customer.</p>
                        </div>
                        <form method="POST" action="{{ route('admin.cancellations.count', $cancellation->id) }}">
                            @csrf
                            <input type="hidden" name="counts_against_company" value="{{ $cancellation->counts_against_company ? 0 : 1 }}">
                            <button class="btn btn-light" type="submit">{{ $cancellation->counts_against_company ? 'Waive it' : 'Count it' }}</button>
                        </form>
                    </div>
                </section>
            @endif

            @if ($project && $project->updates->isNotEmpty())
                <section class="panel">
                    <div class="panel-heading"><div><h2>Project timeline</h2><p>Newest first, including internal entries.</p></div></div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <tbody>
                                @foreach ($project->updates as $update)
                                    <tr>
                                        <td style="white-space:nowrap">{{ $update->created_at?->format('M j, Y H:i') }}</td>
                                        <td>{{ ucfirst((string) $update->updated_by_type) }}</td>
                                        <td>{{ str_replace('_', ' ', (string) $update->new_status) }}</td>
                                        <td class="cell-wrap">{{ $update->message }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>

        <aside class="admin-project-side">
            <section class="panel">
                <div class="panel-heading"><div><h2>People involved</h2><p>Call both before deciding.</p></div></div>
                <dl class="admin-project-dates">
                    <div>
                        <dt>Company</dt>
                        <dd>
                            @if ($company)
                                <a href="{{ route('admin.companies.details', $company->id) }}">{{ $company->name }}</a>
                                <small>{{ $company->phone }} {{ $company->email }}</small>
                            @else
                                Company removed
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Customer</dt>
                        <dd>
                            {{ $customerName }}
                            <small>{{ $customer?->phone }} {{ $customer?->email }}</small>
                        </dd>
                    </div>
                    <div>
                        <dt>Request</dt>
                        <dd>
                            <a href="{{ route('admin.quote-requests.show', $cancellation->quote_id) }}">{{ $reference }}</a>
                            <small>Now: {{ $cancellation->quote?->status ?? '—' }}</small>
                        </dd>
                    </div>
                    @if ($project && !$project->trashed())
                        <div>
                            <dt>Project</dt>
                            <dd><a href="{{ route('admin.projects.show', $project->id) }}">#{{ $project->id }}</a> <small>{{ str_replace('_', ' ', $project->status) }}</small></dd>
                        </div>
                    @endif
                </dl>
            </section>
        </aside>
    </div>
@endsection
