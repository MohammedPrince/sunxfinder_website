@extends('admin.layouts.app')
@section('title', $supplier ? 'Edit Supplier' : 'Add Supplier')
@section('breadcrumb', 'Suppliers')
@section('eyebrow', 'PRODUCT CATALOG')
@section('page-title', $supplier ? 'Edit Supplier' : 'Add Supplier')
@section('page-description', 'Add a manufacturer or brand to the SunXFinder product catalog.')
@section('content')
    @if (session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    <section class="panel">
        <div class="panel-heading"><div><h2>Supplier details</h2><p>Only basic brand information is required.</p></div></div>
        <form method="POST" action="{{ $supplier ? route('admin.suppliers.update', $supplier->id) : route('admin.suppliers.store') }}" style="padding:22px;">
            @csrf @if ($supplier) @method('PUT') @endif
            <div class="form-grid">
                <div class="form-group"><label for="name">Supplier / brand name *</label><input class="form-control" id="name" name="name" required maxlength="150" value="{{ old('name', $supplier->name ?? '') }}" placeholder="e.g. Jinko Solar">@error('name')<small class="text-danger">{{ $message }}</small>@enderror</div>
                <div class="form-group"><label for="website">Website (optional)</label><input class="form-control" type="url" id="website" name="website" value="{{ old('website', $supplier->website ?? '') }}" placeholder="https://example.com">@error('website')<small class="text-danger">{{ $message }}</small>@enderror</div>
                <div class="form-group" style="grid-column:1/-1;"><label for="description">Description (optional)</label><textarea class="form-control" id="description" name="description" rows="3" maxlength="5000">{{ old('description', $supplier->description ?? '') }}</textarea>@error('description')<small class="text-danger">{{ $message }}</small>@enderror</div>
                <div class="form-group"><label for="is_active">Status *</label><select class="form-control" id="is_active" name="is_active" required><option value="1" @selected((string)old('is_active',(int)($supplier->is_active ?? true))==='1')>Active</option><option value="0" @selected((string)old('is_active',(int)($supplier->is_active ?? true))==='0')>Inactive</option></select></div>
            </div>
            <div style="display:flex;gap:10px;margin-top:24px;"><button type="submit" class="btn btn-primary">{{ $supplier ? 'Save Changes' : 'Create Supplier' }}</button><a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">Cancel</a></div>
        </form>
    </section>
@endsection
