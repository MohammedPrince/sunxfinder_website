@extends('admin.layouts.app')
@section('title', $product ? 'Edit Product' : 'Add Product')
@section('breadcrumb', 'Products')
@section('eyebrow', 'PRODUCT CATALOG')
@section('page-title', $product ? 'Edit Product' : 'Add Product')
@section('page-description', 'Add basic product details and optional technical specifications.')
@section('content')
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}</div>
    @endif
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Product details</h2>
                <p>No images, selling prices, or stock fields are included.</p>
            </div>
        </div>
        <form method="POST"
            action="{{ $product ? route('admin.products.update', $product->id) : route('admin.products.store') }}"
            style="padding:22px;" id="productForm">
            @csrf @if ($product)
                @method('PUT')
            @endif
            <div class="form-grid">
                <div class="form-group"><label for="supplier_id">Supplier / brand *</label><select class="form-control"
                        id="supplier_id" name="supplier_id" required>
                        <option value="">Select supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $product->supplier_id ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror @if ($suppliers->isEmpty())
                            <small class="text-danger">Create an active supplier first.</small>
                        @endif
                    </div>
                    <div class="form-group"><label for="type">Product type *</label><select class="form-control"
                            id="type" name="type" required>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $product->type ?? 'inverter') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label for="name">Product name *</label><input class="form-control"
                            id="name" name="name" required maxlength="180"
                            value="{{ old('name', $product->name ?? '') }}" placeholder="e.g. Hybrid Solar Inverter">
                        @error('name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group"><label for="model">Model (optional)</label><input class="form-control"
                            id="model" name="model" maxlength="150" value="{{ old('model', $product->model ?? '') }}"
                            placeholder="e.g. M88PW PRO">
                        @error('model')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group" style="grid-column:1/-1;"><label for="description">Description (optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3" maxlength="5000">{{ old('description', $product->description ?? '') }}</textarea>
                        @error('description')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group"><label for="is_active">Status *</label><select class="form-control" id="is_active"
                            name="is_active" required>
                            <option value="1" @selected((string) old('is_active', (int) ($product->is_active ?? true)) === '1')>Active</option>
                            <option value="0" @selected((string) old('is_active', (int) ($product->is_active ?? true)) === '0')>Inactive</option>
                        </select></div>
                </div>
                <hr style="margin:26px 0;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
                    <div>
                        <h3 style="margin:0;">Specifications</h3>
                        <p style="margin:5px 0 0;color:#8491a5;">Examples: capacity, watts, MPPTs, voltage, energy.</p>
                    </div><button type="button" class="btn btn-secondary" id="addSpec">+ Add specification</button>
                </div>
                <div id="specRows">
                    @php $currentSpecs = old('specs', $product?->specs?->map(fn($spec) => ['spec_name'=>$spec->spec_name,'spec_value'=>$spec->spec_value])->all() ?? []); @endphp
                    @foreach ($currentSpecs as $index => $spec)
                        <div class="form-grid spec-row" style="align-items:end;margin-bottom:12px;">
                            <div class="form-group"><label>Specification name</label><input class="form-control"
                                    name="specs[{{ $index }}][spec_name]" maxlength="100"
                                    value="{{ $spec['spec_name'] ?? '' }}" placeholder="e.g. Capacity"></div>
                            <div class="form-group"><label>Value</label><input class="form-control"
                                    name="specs[{{ $index }}][spec_value]" maxlength="150"
                                    value="{{ $spec['spec_value'] ?? '' }}" placeholder="e.g. 5 kW"></div>
                            <div class="form-group"><button type="button" class="btn btn-secondary remove-spec">Remove</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div style="display:flex;gap:10px;margin-top:24px;"><button type="submit" class="btn btn-primary"
                        @disabled($suppliers->isEmpty())>{{ $product ? 'Save Changes' : 'Create Product' }}</button><a
                        href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancel</a></div>
            </form>
        </section>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const rows = document.getElementById('specRows');
                document.getElementById('addSpec').addEventListener('click', function() {
                    if (rows.querySelectorAll('.spec-row').length >= 30) {
                        alert('Maximum 30 specifications.');
                        return;
                    }
                    const row = document.createElement('div');
                    row.className = 'form-grid spec-row';
                    row.style.cssText = 'align-items:end;margin-bottom:12px;';
                    row.innerHTML =
                        '<div class="form-group"><label>Specification name</label><input class="form-control" maxlength="100" placeholder="e.g. Capacity"></div><div class="form-group"><label>Value</label><input class="form-control" maxlength="150" placeholder="e.g. 5 kW"></div><div class="form-group"><button type="button" class="btn btn-secondary remove-spec">Remove</button></div>';
                    rows.appendChild(row);
                    reindex();
                });
                rows.addEventListener('click', function(event) {
                    const button = event.target.closest('.remove-spec');
                    if (button) {
                        button.closest('.spec-row').remove();
                        reindex();
                    }
                });

                function reindex() {
                    rows.querySelectorAll('.spec-row').forEach((row, index) => {
                        const inputs = row.querySelectorAll('input');
                        inputs[0].name = `specs[${index}][spec_name]`;
                        inputs[1].name = `specs[${index}][spec_value]`;
                    });
                }
                reindex();
            });
        </script>
    @endsection
