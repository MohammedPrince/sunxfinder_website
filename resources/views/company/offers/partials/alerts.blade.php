@if (session('success'))
    <div class="alert alert-success alert-dismissible">
        <strong>{{ __('company/messages.common.success_label') }}</strong> {{ session('success') }}
        <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="{{ __('company/messages.common.close') }}">&times;</button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible">
        <strong>{{ __('company/messages.common.error_label') }}</strong> {{ session('error') }}
        <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="{{ __('company/messages.common.close') }}">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>{{ __('company/messages.common.fix_errors') }}</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
