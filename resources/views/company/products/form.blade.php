@extends('company.layouts.app')

@section('title', $product ? 'Edit Product' : 'Add Product')
@section('breadcrumb', 'My Products')
@section('eyebrow', 'COMPANY CATALOG')
@section('page-title', $product ? 'Edit Product' : 'Add Product')
@section('page-description', 'Manage the product information and specifications displayed on your company profile.')

@section('page-actions')
    <a href="{{ route('company.products.index') }}" class="btn btn-secondary">
        &larr; Back to Products
    </a>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following errors:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php
        $specRows = old(
            'specs',
            $product?->specs
                ->map(
                    fn($s) => [
                        'name' => $s->name,
                        'value' => $s->value,
                    ],
                )
                ->toArray() ?? [['name' => '', 'value' => '']],
        );

        if (empty($specRows)) {
            $specRows = [['name' => '', 'value' => '']];
        }
    @endphp

    <form method="POST"
        action="{{ $product ? route('company.products.update', $product->id) : route('company.products.store') }}">
        @csrf

        @if ($product)
            @method('PUT')
        @endif

        <div class="product-form-layout">
            <div class="product-form-main">
                {{-- Product information --}}
                <section class="panel product-form-panel">
                    <div class="product-section-heading">
                        <div class="product-section-icon">
                            <i data-lucide="package"></i>
                        </div>
                        <div>
                            <h2>Product Information</h2>
                            <p>Enter the basic details of your solar product.</p>
                        </div>
                    </div>

                    <div class="product-form-grid">
                        <div class="product-field">
                            <label for="supplier_id">Supplier / Brand <span>*</span></label>
                            <select name="supplier_id" id="supplier_id" required>
                                <option value="">Select supplier</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $product?->supplier_id) === (string) $supplier->id)>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('supplier_id')
                                <small class="product-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="product-field">
                            <label for="type">Product Category <span>*</span></label>
                            <select name="type" id="type" required>
                                <option value="">Select category</option>
                                @foreach ([
            'inverter' => 'Inverter',
            'solar_panel' => 'Solar Panel',
            'battery' => 'Battery',
            'other' => 'Other',
        ] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type', $product?->type) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                                <small class="product-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="product-field">
                            <label for="name">Product Name <span>*</span></label>
                            <input type="text" name="name" id="name" maxlength="150"
                                placeholder="e.g. Hybrid Solar Inverter" value="{{ old('name', $product?->name) }}"
                                required>
                            @error('name')
                                <small class="product-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="product-field">
                            <label for="model">Model Number</label>
                            <input type="text" name="model" id="model" maxlength="100" placeholder="e.g. M88PW PRO"
                                value="{{ old('model', $product?->model) }}">
                            @error('model')
                                <small class="product-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="product-field product-field-full">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" rows="4"
                                placeholder="Describe the product and its main features...">{{ old('description', $product?->description) }}</textarea>
                            @error('description')
                                <small class="product-field-error">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </section>

                {{-- Specifications --}}
                <section class="panel product-form-panel">
                    <div class="product-section-heading product-spec-heading">
                        <div class="product-section-icon">
                            <i data-lucide="list-checks"></i>
                        </div>
                        <div>
                            <h2>Product Specifications</h2>
                            <p>Add technical details relevant to this product.</p>
                        </div>
                    </div>

                    <div class="product-spec-examples">
                        <strong>Examples:</strong>
                        Inverter — Capacity, MPPT, Output Voltage.
                        Solar Panel — Wattage, Cell Type, Efficiency.
                        Battery — Capacity, Voltage, Chemistry.
                    </div>

                    <div id="product-specs">
                        @foreach ($specRows as $i => $spec)
                            <div class="product-spec-row">
                                <div class="product-field">
                                    <label>Specification Name</label>
                                    <input type="text" name="specs[{{ $i }}][name]"
                                        placeholder="e.g. Capacity" value="{{ $spec['name'] ?? '' }}">
                                </div>

                                <div class="product-field">
                                    <label>Specification Value</label>
                                    <input type="text" name="specs[{{ $i }}][value]" placeholder="e.g. 5 kW"
                                        value="{{ $spec['value'] ?? '' }}">
                                </div>

                                <button type="button" class="product-spec-remove" aria-label="Remove specification"
                                    title="Remove specification" @if (count($specRows) === 1) disabled @endif>
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-secondary product-add-spec" id="add-spec">
                        <i data-lucide="plus"></i>
                        Add Specification
                    </button>
                </section>
            </div>

            {{-- Publishing settings --}}
            <aside class="product-form-sidebar">
                <section class="panel product-form-panel product-publish-panel">
                    <div class="product-section-heading">
                        <div class="product-section-icon">
                            <i data-lucide="eye"></i>
                        </div>
                        <div>
                            <h2>Visibility</h2>
                            <p>Control whether customers can see this product.</p>
                        </div>
                    </div>

                    <label class="product-visibility-option" for="is_active">
                        <span class="product-visibility-text">
                            <strong>Show on company profile</strong>
                            <small>Allow visitors to see this product on your public profile.</small>
                        </span>

                        <input type="checkbox" name="is_active" value="1" id="is_active" @checked((bool) old('is_active', $product?->is_active ?? true))>
                    </label>
                </section>

                <section class="panel product-form-panel product-help-panel">
                    <i data-lucide="lightbulb"></i>
                    <h3>Helpful Tip</h3>
                    <p>
                        Add accurate specifications so customers can understand your product
                        and compare technical features.
                    </p>
                </section>
            </aside>
        </div>

        <div class="product-form-footer">
            <a href="{{ route('company.products.index') }}" class="btn btn-secondary">
                Cancel
            </a>

            <button type="submit" class="btn btn-primary">
                <i data-lucide="check"></i>
                {{ $product ? 'Save Changes' : 'Add Product' }}
            </button>
        </div>
    </form>

    @push('scripts')
        <script src="{{ asset('main/js/lucide.min.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const specsContainer = document.getElementById('product-specs');
                const addSpecButton = document.getElementById('add-spec');

                if (!specsContainer || !addSpecButton) {
                    return;
                }

                let nextIndex = {{ count($specRows) }};

                function updateRemoveButtons() {
                    const rows = specsContainer.querySelectorAll('.product-spec-row');

                    rows.forEach(function(row) {
                        const button = row.querySelector('.product-spec-remove');
                        if (button) {
                            button.disabled = rows.length === 1;
                        }
                    });
                }

                function addSpecRow() {
                    const row = document.createElement('div');
                    row.className = 'product-spec-row';

                    const nameField = document.createElement('div');
                    nameField.className = 'product-field';

                    const nameLabel = document.createElement('label');
                    nameLabel.textContent = 'Specification Name';

                    const nameInput = document.createElement('input');
                    nameInput.type = 'text';
                    nameInput.name = `specs[${nextIndex}][name]`;
                    nameInput.placeholder = 'e.g. Capacity';

                    nameField.append(nameLabel, nameInput);

                    const valueField = document.createElement('div');
                    valueField.className = 'product-field';

                    const valueLabel = document.createElement('label');
                    valueLabel.textContent = 'Specification Value';

                    const valueInput = document.createElement('input');
                    valueInput.type = 'text';
                    valueInput.name = `specs[${nextIndex}][value]`;
                    valueInput.placeholder = 'e.g. 5 kW';

                    valueField.append(valueLabel, valueInput);

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'product-spec-remove';
                    removeButton.setAttribute('aria-label', 'Remove specification');
                    removeButton.title = 'Remove specification';
                    removeButton.innerHTML = '<i data-lucide="trash-2"></i>';

                    row.append(nameField, valueField, removeButton);
                    specsContainer.appendChild(row);

                    nextIndex++;
                    updateRemoveButtons();

                    if (window.lucide) {
                        window.lucide.createIcons();
                    }

                    nameInput.focus();
                }

                addSpecButton.addEventListener('click', addSpecRow);

                specsContainer.addEventListener('click', function(event) {
                    const removeButton = event.target.closest('.product-spec-remove');

                    if (!removeButton || removeButton.disabled) {
                        return;
                    }

                    const row = removeButton.closest('.product-spec-row');
                    if (row) {
                        row.remove();
                    }

                    updateRemoveButtons();
                });

                updateRemoveButtons();

                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        </script>
    @endpush
@endsection
