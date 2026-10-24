@extends('admin.layouts.app')
@section('title', 'Products')
@section('breadcrumb', 'Products')
@section('eyebrow', 'PRODUCT CATALOG')
@section('page-title', 'Products')
@section('page-description', 'Manage solar products that companies can display on their SunXFinder profiles.')
@section('page-actions')
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">+ Add Product</a>
@endsection
@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <section class="panel table-panel">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Product catalog</h2>
                <p>{{ $products->total() }} products</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Model</th>
                        <th>Supplier</th>
                        <th>Type</th>
                        <th>Specifications</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td><strong>{{ $product->name }}</strong></td>
                            <td>{{ $product->model ?: '—' }}</td>
                            <td>{{ $product->supplier?->name ?? '—' }}</td>
                            <td>{{ $product->typeLabel() }}</td>
                            <td>
                                @forelse ($product->specs as $spec)
                                    <span>{{ $spec->spec_name }}: {{ $spec->spec_value }}</span>
                                    @if (!$loop->last)
                                        ,
                                    @endif @empty —
                                @endforelse
                            </td>
                            <td><span
                                    class="pill {{ $product->is_active ? 'pill-green' : 'pill-red' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td>
                                <div class="company-actions"><a href="{{ route('admin.products.edit', $product->id) }}"
                                        class="row-action row-action-icon"> <svg viewBox="0 0 24 24" width="18"
                                            height="18" fill="none" stroke="currentColor" stroke-width="1.8"
                                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                        </svg></a>

                                    <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST"
                                        class="company-delete-form" onsubmit="return confirm('Delete this product?');">@csrf
                                        @method('DELETE')<button type="submit"
                                            class="row-action row-action-icon row-action-delete"> <svg viewBox="0 0 24 24"
                                                width="18" height="18" fill="none" stroke="currentColor"
                                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                                aria-hidden="true">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4h8v2" />
                                                <path d="m19 6-1 14H6L5 6" />
                                                <path d="M10 11v5M14 11v5" />
                                            </svg>
                                        </button></form>
                                </div>
                            </td>
                        </tr>
                    @empty <tr>
                            <td colspan="7" class="text-center">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.table-pagination', ['paginator' => $products, 'label' => 'products'])
    </section>
@endsection
