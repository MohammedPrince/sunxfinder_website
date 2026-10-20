@extends('admin.layouts.app')

@php
    $isEditing = isset($group) && $group->exists;

    $pageTitle = $isEditing ? 'Update Appliance Group' : 'Add New Appliance Group';
    $pageBreadcrumb = $isEditing ? 'Calculator / Update appliance group' : 'Calculator / Add appliance group';

    $formAction = $isEditing
        ? route('admin.calculator-groups.update', $group->id)
        : route('admin.calculator-groups.store');

    $appliances = old('appliances');

    if ($appliances === null) {
        $appliances = $isEditing
            ? $group->appliances
                ->map(function ($appliance) {
                    return [
                        'name' => $appliance->name,
                        'ar_name' => $appliance->ar_name,
                        'watts' => $appliance->watts,
                        'sort_order' => $appliance->sort_order,
                        'is_active' => (int) $appliance->is_active,
                    ];
                })
                ->toArray()
            : [
                [
                    'name' => '',
                    'ar_name' => '',
                    'watts' => '',
                    'sort_order' => 0,
                    'is_active' => 1,
                ],
            ];
    }

    if (empty($appliances)) {
        $appliances = [
            [
                'name' => '',
                'ar_name' => '',
                'watts' => '',
                'sort_order' => 0,
                'is_active' => 1,
            ],
        ];
    }
@endphp

@section('title', $pageTitle)
@section('breadcrumb', $pageBreadcrumb)
@section('eyebrow', 'SOLAR CALCULATOR')
@section('page-title', $pageTitle)

@section('page-description')
    {{ $isEditing
        ? 'Update the appliance group, manage its appliances, and configure their power consumption.'
        : 'Create an appliance group and add the appliances customers can use in the solar calculator.' }}
@endsection

@section('page-actions')
    <a href="{{ route('admin.calculator-groups.index') }}" class="btn btn-primary">
        ← Back to Appliance Groups
    </a>
@endsection

@section('content')

    {{-- Success message --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Error message --}}
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following errors:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $formAction }}">
        @csrf

        @if ($isEditing)
            @method('PUT')
        @endif

        {{-- Appliance group information --}}
        <section class="panel">
            <div class="panel-heading">
                <h2>Group Information</h2>
                <p>Configure the appliance category displayed in the SunXFinder solar calculator.</p>
            </div>

            <div class="panel-body">
                <div class="form-grid">

                    <div class="form-group">
                        <label for="name">
                            Group Name (English) <b style="color: red">*</b>
                        </label>

                        <input class="form-control" type="text" id="name" name="name" maxlength="150"
                            value="{{ old('name', $group->name ?? '') }}" placeholder="e.g. Fans" required>

                        @error('name')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="ar_name">
                            Group Name (Arabic) <b style="color: red">*</b>
                        </label>

                        <input class="form-control" type="text" id="ar_name" name="ar_name" maxlength="150"
                            dir="rtl" lang="ar" value="{{ old('ar_name', $group->ar_name ?? '') }}"
                            placeholder="مثال: المراوح" required>

                        @error('ar_name')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="icon">Icon Name</label>

                        <input class="form-control" type="text" id="icon" name="icon" maxlength="100"
                            value="{{ old('icon', $group->icon ?? '') }}" placeholder="e.g. wind">

                        <small class="form-hint">
                            Optional icon identifier for the Flutter application.
                        </small>

                        @error('icon')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="sort_order">
                            Display Order <b style="color: red">*</b>
                        </label>

                        <input type="number" name="sort_order" id="sort_order" class="form-control"
                            value="{{ old('sort_order', $isEditing ? $group->sort_order : $nextSortOrder ?? 1) }}"
                            min="0" required>

                        @error('sort_order')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="is_active">
                            Group Status <b style="color: red">*</b>
                        </label>

                        @php
                            $selectedGroupActive = (string) old('is_active', (int) ($group->is_active ?? true));
                        @endphp

                        <select class="select-control" id="is_active" name="is_active" required>
                            <option value="1" @selected($selectedGroupActive === '1')>
                                Active
                            </option>
                            <option value="0" @selected($selectedGroupActive === '0')>
                                Inactive
                            </option>
                        </select>

                        @error('is_active')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                </div>
            </div>
        </section>

        {{-- Appliances --}}
        <section class="panel">
            <div class="panel-heading">
                <h2>Group Appliances</h2>
                <p>
                    Add appliances with their English and Arabic names and estimated
                    power consumption in watts.
                </p>
            </div>

            <div class="panel-body">

                <div id="appliances-container">

                    @foreach ($appliances as $index => $appliance)
                        <div class="appliance-item"
                            style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 18px;">

                            <div
                                style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 18px;">
                                <h3 class="appliance-heading" style="margin: 0;">
                                    Appliance {{ $index + 1 }}
                                </h3>

                                <button type="button" class="btn btn-light remove-appliance">
                                    Remove
                                </button>
                            </div>

                            <div class="form-grid">

                                <div class="form-group">
                                    <label>
                                        Appliance Name (English)
                                        <b style="color: red">*</b>
                                    </label>

                                    <input class="form-control" type="text" name="appliances[{{ $index }}][name]"
                                        maxlength="150" value="{{ $appliance['name'] ?? '' }}"
                                        placeholder="e.g. Ceiling Fan" required>

                                    @error("appliances.$index.name")
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>
                                        Appliance Name (Arabic)
                                        <b style="color: red">*</b>
                                    </label>

                                    <input class="form-control" type="text"
                                        name="appliances[{{ $index }}][ar_name]" maxlength="150" dir="rtl"
                                        lang="ar" value="{{ $appliance['ar_name'] ?? '' }}"
                                        placeholder="مثال: مروحة سقف" required>

                                    @error("appliances.$index.ar_name")
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>
                                        Power Consumption (Watts)
                                        <b style="color: red">*</b>
                                    </label>

                                    <input class="form-control" type="number"
                                        name="appliances[{{ $index }}][watts]" min="1" step="1"
                                        value="{{ $appliance['watts'] ?? '' }}" placeholder="e.g. 75" required>

                                    @error("appliances.$index.watts")
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Display Order</label>

                                    <input class="form-control" type="number"
                                        name="appliances[{{ $index }}][sort_order]" min="0" step="1"
                                        value="{{ $appliance['sort_order'] ?? $index }}">

                                    @error("appliances.$index.sort_order")
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>
                                        Appliance Status
                                        <b style="color: red">*</b>
                                    </label>

                                    @php
                                        $selectedApplianceActive = (string) ($appliance['is_active'] ?? 1);
                                    @endphp

                                    <select class="select-control" name="appliances[{{ $index }}][is_active]"
                                        required>
                                        <option value="1" @selected($selectedApplianceActive === '1')>
                                            Active
                                        </option>
                                        <option value="0" @selected($selectedApplianceActive === '0')>
                                            Inactive
                                        </option>
                                    </select>

                                    @error("appliances.$index.is_active")
                                        <small class="field-error">{{ $message }}</small>
                                    @enderror
                                </div>

                            </div>
                        </div>
                    @endforeach

                </div>

                <div style="margin-bottom: 24px;">
                    <button type="button" id="add-appliance" class="btn btn-light">
                        + Add Another Appliance
                    </button>
                </div>

                <div class="form-actions">
                    <a href="{{ route('admin.calculator-groups.index') }}" class="btn btn-light">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary">
                        {{ $isEditing ? 'Save Changes' : 'Create Appliance Group' }}
                    </button>
                </div>

            </div>
        </section>
    </form>

@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('appliances-container');
            const addButton = document.getElementById('add-appliance');

            function updateApplianceRows() {
                const rows = container.querySelectorAll('.appliance-item');

                rows.forEach(function(row, index) {
                    row.querySelector('.appliance-heading').textContent =
                        'Appliance ' + (index + 1);

                    row.querySelectorAll('[name]').forEach(function(field) {
                        field.name = field.name.replace(
                            /appliances\[\d+\]/,
                            'appliances[' + index + ']'
                        );
                    });

                    const removeButton = row.querySelector('.remove-appliance');
                    removeButton.disabled = rows.length === 1;
                    removeButton.style.opacity = rows.length === 1 ? '0.5' : '1';
                });
            }

            function createApplianceRow(index) {
                const row = document.createElement('div');

                row.className = 'appliance-item';
                row.style.cssText =
                    'border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 18px;';

                row.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 18px;">
                        <h3 class="appliance-heading" style="margin: 0;">
                            Appliance ${index + 1}
                        </h3>

                        <button type="button" class="btn btn-light remove-appliance">
                            Remove
                        </button>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Appliance Name (English) <b style="color: red">*</b></label>
                            <input class="form-control"
                                   type="text"
                                   name="appliances[${index}][name]"
                                   maxlength="150"
                                   placeholder="e.g. Ceiling Fan"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Appliance Name (Arabic) <b style="color: red">*</b></label>
                            <input class="form-control"
                                   type="text"
                                   name="appliances[${index}][ar_name]"
                                   maxlength="150"
                                   dir="rtl"
                                   lang="ar"
                                   placeholder="مثال: مروحة سقف"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Power Consumption (Watts) <b style="color: red">*</b></label>
                            <input class="form-control"
                                   type="number"
                                   name="appliances[${index}][watts]"
                                   min="1"
                                   step="1"
                                   placeholder="e.g. 75"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Display Order</label>
                            <input class="form-control"
                                   type="number"
                                   name="appliances[${index}][sort_order]"
                                   min="0"
                                   step="1"
                                   value="${index}">
                        </div>

                        <div class="form-group">
                            <label>Appliance Status <b style="color: red">*</b></label>
                            <select class="select-control"
                                    name="appliances[${index}][is_active]"
                                    required>
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                `;

                return row;
            }

            addButton.addEventListener('click', function() {
                const index = container.querySelectorAll('.appliance-item').length;

                container.appendChild(createApplianceRow(index));
                updateApplianceRows();
            });

            container.addEventListener('click', function(event) {
                const button = event.target.closest('.remove-appliance');

                if (!button || container.querySelectorAll('.appliance-item').length <= 1) {
                    return;
                }

                button.closest('.appliance-item').remove();
                updateApplianceRows();
            });

            updateApplianceRows();
        });
    </script>
@endsection
