@extends('admin.layouts.app')
@section('title', 'Suppliers')
@section('breadcrumb', 'Suppliers')
@section('eyebrow', 'PRODUCT CATALOG')
@section('page-title', 'Suppliers')
@section('page-description', 'Manage the brands and manufacturers available in the SunXFinder catalog.')
@section('page-actions')
    <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary">
        + Add Supplier</a>
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
                <h2>Supplier directory</h2>
                <p>{{ $suppliers->total() }} suppliers</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Website</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td><strong>{{ $supplier->name }}</strong></td>
                            <td>
                                @if ($supplier->website)
                                    <a href="{{ $supplier->website }}" target="_blank"
                                        rel="noopener noreferrer">{{ $supplier->website }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $supplier->products_count }}</td>
                            <td><span
                                    class="pill {{ $supplier->is_active ? 'pill-green' : 'pill-red' }}">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td>
                                <div class="company-actions">
                                    <a href="{{ route('admin.suppliers.edit', $supplier->id) }}"
                                        class="row-action row-action-icon"> <svg viewBox="0 0 24 24" width="18"
                                            height="18" fill="none" stroke="currentColor" stroke-width="1.8"
                                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" />
                                        </svg></a>
                                    <form action="{{ route('admin.suppliers.destroy', $supplier->id) }}" method="POST"
                                        onsubmit="return confirm('Delete this supplier?');" class="company-delete-form">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="row-action row-action-icon row-action-delete"><svg
                                                viewBox="0 0 24 24" width="18" height="18" fill="none"
                                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                                stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4h8v2" />
                                                <path d="m19 6-1 14H6L5 6" />
                                                <path d="M10 11v5M14 11v5" />
                                            </svg></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty <tr>
                            <td colspan="5" class="text-center">No suppliers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-bottom"><span class="table-info">Showing
                {{ $suppliers->firstItem() ?? 0 }}–{{ $suppliers->lastItem() ?? 0 }} of {{ $suppliers->total() }}</span>
            <div class="pagination-wrapper">{{ $suppliers->links() }}</div>
        </div>
    </section>
@endsection
