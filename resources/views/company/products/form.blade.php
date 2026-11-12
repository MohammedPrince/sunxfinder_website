@extends('company.layouts.app')

@section('title', $product ? __('company/messages.products.form_edit') : __('company/messages.products.form_add'))
@section('breadcrumb', __('company/messages.products.title'))
@section('eyebrow', __('company/messages.products.eyebrow'))
@section('page-title', $product ? __('company/messages.products.form_edit') : __('company/messages.products.form_add'))
@section('page-description', __('company/messages.products.form_description'))

@section('page-actions')
    <a href="{{ route('company.products.index') }}" class="btn btn-secondary">
        {{ __('company/messages.products.back') }}
    </a>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>{{ __('company/messages.common.fix_errors') }}</strong>
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

    @include('company.partials.guide', ['page' => 'product_form'])

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
                            <h2>{{ __('company/messages.products.info') }}</h2>
                            <p>{{ __('company/messages.products.info_sub') }}</p>
                        </div>
                    </div>

                    <div class="product-form-grid">
                        <div class="product-field">
                            <label for="supplier_id">{{ __('company/messages.products.supplier_brand') }} <span>*</span></label>
                            <select name="supplier_id" id="supplier_id" required>
                                <option value="">{{ __('company/messages.products.select_supplier') }}</option>
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
                            <label for="type">{{ __('company/messages.products.category') }} <span>*</span></label>
                            <select name="type" id="type" required>
                                <option value="">{{ __('company/messages.products.select_category') }}</option>
                                @foreach ([
            'inverter' => __('company/messages.common.product_type_inverter'),
            'solar_panel' => __('company/messages.common.product_type_solar_panel'),
            'battery' => __('company/messages.common.product_type_battery'),
            'other' => __('company/messages.common.product_type_other'),
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
                            <label for="name">{{ __('company/messages.products.name') }} <span>*</span></label>
                            <input type="text" name="name" id="name" maxlength="150"
                                placeholder="{{ __('company/messages.products.name_placeholder') }}" value="{{ old('name', $product?->name) }}"
                                required>
                            @error('name')
                                <small class="product-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="product-field">
                            <label for="model">{{ __('company/messages.products.model_number') }}</label>
                            <input type="text" name="model" id="model" maxlength="100" placeholder="{{ __('company/messages.products.model_placeholder') }}"
                                value="{{ old('model', $product?->model) }}">
                            @error('model')
                                <small class="product-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="product-field product-field-full">
                            <label for="description">{{ __('company/messages.products.description') }}</label>
                            <textarea name="description" id="description" rows="4"
                                placeholder="{{ __('company/messages.products.description_placeholder') }}">{{ old('description', $product?->description) }}</textarea>
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
                            <h2>{{ __('company/messages.products.specs') }}</h2>
                            <p>{{ __('company/messages.products.specs_sub') }}</p>
                        </div>
                    </div>

                    <div class="product-spec-examples">
                        <strong>{{ __('company/messages.products.examples') }}</strong>
                        {{ __('company/messages.products.examples_body') }}
                    </div>

                    <div id="product-specs">
                        @foreach ($specRows as $i => $spec)
                            <div class="product-spec-row">
                                <div class="product-field">
                                    <label>{{ __('company/messages.products.spec_name') }}</label>
                                    <input type="text" name="specs[{{ $i }}][name]"
                                        placeholder="{{ __('company/messages.products.spec_name_placeholder') }}" value="{{ $spec['name'] ?? '' }}">
                                </div>

                                <div class="product-field">
                                    <label>{{ __('company/messages.products.spec_value') }}</label>
                                    <input type="text" name="specs[{{ $i }}][value]" placeholder="{{ __('company/messages.products.spec_value_placeholder') }}"
                                        value="{{ $spec['value'] ?? '' }}">
                                </div>

                                <button type="button" class="product-spec-remove" aria-label="{{ __('company/messages.products.remove_spec') }}"
                                    title="{{ __('company/messages.products.remove_spec') }}" @if (count($specRows) === 1) disabled @endif>
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-secondary product-add-spec" id="add-spec">
                        <i data-lucide="plus"></i>
                        {{ __('company/messages.products.add_spec') }}
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
                            <h2>{{ __('company/messages.products.visibility') }}</h2>
                            <p>{{ __('company/messages.products.visibility_sub') }}</p>
                        </div>
                    </div>

                    <label class="product-visibility-option" for="is_active">
                        <span class="product-visibility-text">
                            <strong>{{ __('company/messages.products.show_on_profile') }}</strong>
                            <small>{{ __('company/messages.products.show_on_profile_sub') }}</small>
                        </span>

                        {{-- An unchecked box sends nothing; the hidden 0 makes "hide" actually save. --}}
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="is_active" @checked((bool) old('is_active', $product?->is_active ?? true))>
                    </label>
                </section>

                <section class="panel product-form-panel product-help-panel">
                    <i data-lucide="lightbulb"></i>
                    <h3>{{ __('company/messages.products.tip') }}</h3>
                    <p>
                        {{ __('company/messages.products.tip_body') }}
                    </p>
                </section>
            </aside>
        </div>

        <div class="product-form-footer">
            <a href="{{ route('company.products.index') }}" class="btn btn-secondary">
                {{ __('company/messages.common.cancel') }}
            </a>

            <button type="submit" class="btn btn-primary">
                <i data-lucide="check"></i>
                {{ $product ? __('company/messages.common.save_changes') : __('company/messages.products.form_add') }}
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
                    nameLabel.textContent = @json(__('company/messages.products.spec_name'));

                    const nameInput = document.createElement('input');
                    nameInput.type = 'text';
                    nameInput.name = `specs[${nextIndex}][name]`;
                    nameInput.placeholder = @json(__('company/messages.products.spec_name_placeholder'));

                    nameField.append(nameLabel, nameInput);

                    const valueField = document.createElement('div');
                    valueField.className = 'product-field';

                    const valueLabel = document.createElement('label');
                    valueLabel.textContent = @json(__('company/messages.products.spec_value'));

                    const valueInput = document.createElement('input');
                    valueInput.type = 'text';
                    valueInput.name = `specs[${nextIndex}][value]`;
                    valueInput.placeholder = @json(__('company/messages.products.spec_value_placeholder'));

                    valueField.append(valueLabel, valueInput);

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'product-spec-remove';
                    removeButton.setAttribute('aria-label', @json(__('company/messages.products.remove_spec')));
                    removeButton.title = @json(__('company/messages.products.remove_spec'));
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
