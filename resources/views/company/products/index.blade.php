@extends('company.layouts.app')

@section('title', __('company/messages.products.title'))
@section('breadcrumb', __('company/messages.products.title'))
@section('eyebrow', __('company/messages.products.eyebrow'))
@section('page-title', __('company/messages.products.title'))
@section('page-description', __('company/messages.products.page_description'))

@section('page-actions')
    <a href="{{ route('company.products.create') }}" class="btn btn-primary">
        {{ __('company/messages.products.add_button') }}
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @include('company.partials.guide', ['page' => 'products'])

    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>{{ __('company/messages.products.title') }}</h2>
                <p>{{ __('company/messages.products.count', ['count' => $products->total()]) }}</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('company/messages.products.product') }}</th>
                        <th>{{ __('company/messages.products.model') }}</th>
                        <th>{{ __('company/messages.products.supplier') }}</th>
                        <th>{{ __('company/messages.products.type') }}</th>
                        <th>{{ __('company/messages.products.specifications') }}</th>
                        <th>{{ __('company/messages.common.status') }}</th>
                        <th>{{ __('company/messages.common.actions') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>
                                <strong>{{ $product->name }}</strong>
                            </td>

                            <td>{{ $product->model ?: '—' }}</td>

                            <td>{{ $product->supplier?->name ?? '—' }}</td>

                            <td>{{ __('company/messages.common.product_type_' . $product->type) }}</td>

                            <td>
                                @forelse ($product->specs as $spec)
                                    <span>
                                        {{ $spec->spec_name }}: {{ $spec->spec_value }}
                                    </span>
                                    @if (!$loop->last)
                                        ,
                                    @endif
                                @empty
                                    —
                                @endforelse
                            </td>

                            <td>
                                <span class="pill {{ $product->is_active ? 'pill-green' : 'pill-red' }}">
                                    {{ $product->is_active ? __('company/messages.common.active') : __('company/messages.common.inactive') }}
                                </span>
                            </td>

                            <td>
                                <div class="company-actions">
                                    <a
                                        href="{{ route('company.products.edit', $product->id) }}"
                                        class="row-action row-action-icon"
                                        aria-label="{{ __('company/messages.products.edit') }}"
                                        title="{{ __('company/messages.products.edit') }}"
                                    >
                                        <svg viewBox="0 0 24 24" width="18" height="18"
                                            fill="none" stroke="currentColor" stroke-width="1.8"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            aria-hidden="true">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                        </svg>
                                    </a>

                                    <form
                                        action="{{ route('company.products.destroy', $product->id) }}"
                                        method="POST"
                                        class="company-delete-form"
                                        onsubmit="return confirm(@js(__('company/messages.products.delete_confirm')));"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="row-action row-action-icon row-action-delete"
                                            aria-label="{{ __('company/messages.products.delete') }}"
                                            title="{{ __('company/messages.products.delete') }}"
                                        >
                                            <svg viewBox="0 0 24 24" width="18" height="18"
                                                fill="none" stroke="currentColor" stroke-width="1.8"
                                                stroke-linecap="round" stroke-linejoin="round"
                                                aria-hidden="true">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4h8v2" />
                                                <path d="m19 6-1 14H6L5 6" />
                                                <path d="M10 11v5M14 11v5" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                {{ __('company/messages.products.empty') }}
                                <a href="{{ route('company.products.create') }}">{{ __('company/messages.products.add_first') }}</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.table-pagination', ['paginator' => $products, 'label' => 'products'])
    </section>
@endsection