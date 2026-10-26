{{--
    English + Arabic services as one input per service (same as create-company-account).
    Shared by admin add/update company and the company profile. Script: public/main/js/services-editor.js
    Params: $services, $arServices (comma-separated strings or arrays).
--}}
@php
    $toServiceList = function ($value) {
        $value = is_array($value) ? implode(',', $value) : (string) $value;

        // English comma, Arabic comma (،), semicolons (; ؛) or new lines, like Helper::splitList.
        return array_values(array_filter(array_map('trim', preg_split('/[,،;؛\r\n]+/u', $value)), fn ($service) => $service !== '')) ?: [''];
    };

    $serviceFields = [
        'services' => [
            'label' => 'Company Services (English)',
            'placeholder' => 'e.g. Hybrid systems',
            'values' => $toServiceList(old('services', $services ?? '')),
            'add' => 'Add other service',
            'rtl' => false,
        ],
        'ar_services' => [
            'label' => 'Company Services (Arabic)',
            'placeholder' => 'مثال: الأنظمة الهجينة',
            'values' => $toServiceList(old('ar_services', $arServices ?? '')),
            'add' => 'إضافة خدمة أخرى',
            'rtl' => true,
        ],
    ];
@endphp

<div class="company-profile-grid" data-services-editor data-remove-label="Remove">
    @foreach ($serviceFields as $name => $field)
        <div class="form-group">
            <label for="{{ $name }}-0">{{ $field['label'] }} <b style="color: red">*</b></label>

            <div class="provider-service-inputs" data-service-inputs="{{ $name }}"
                data-placeholder="{{ $field['placeholder'] }}" @if ($field['rtl']) dir="rtl" @endif>
                @foreach ($field['values'] as $index => $service)
                    <div class="provider-service-row">
                        <input class="form-control" type="text" id="{{ $name }}-{{ $index }}" value="{{ $service }}"
                            maxlength="5000" placeholder="{{ $field['placeholder'] }}"
                            @if ($field['rtl']) dir="rtl" lang="ar" @endif @if ($index === 0) required @endif>
                        @if ($index > 0)
                            <button type="button" class="provider-service-remove">Remove</button>
                        @endif
                    </div>
                @endforeach
            </div>

            <input type="hidden" name="{{ $name }}" value="{{ implode(',', array_filter($field['values'])) }}">
            <button type="button" class="provider-service-add" data-add-service="{{ $name }}">{{ $field['add'] }}</button>

            @error($name)
                <small class="field-error">{{ $message }}</small>
            @enderror
        </div>
    @endforeach
</div>
