{{--
    Password + confirmation with show/hide and a live checklist of the server rules.
    Expects: $minLength (int) — must match the "min" rule in the controller.
    Script: public/visitors/js/register.js
--}}
<div class="provider-field">
    <label for="password">{{ __('messages.Password') }} <b>*</b></label>
    <div class="provider-password">
        <input type="password" id="password" name="password" required minlength="{{ $minLength }}"
            autocomplete="new-password" aria-describedby="passwordRules" data-password-input>
        <button type="button" class="provider-password-toggle" data-password-toggle="password"
            data-show="{{ __('messages.Show') }}" data-hide="{{ __('messages.Hide') }}">{{ __('messages.Show') }}</button>
    </div>
    @error('password')
        <small class="provider-field-error">{{ $message }}</small>
    @enderror
</div>

<div class="provider-field">
    <label for="password_confirmation">{{ __('messages.Confirm password') }} <b>*</b></label>
    <div class="provider-password">
        <input type="password" id="password_confirmation" name="password_confirmation" required
            minlength="{{ $minLength }}" autocomplete="new-password" data-password-confirm
            placeholder="{{ __('messages.Re-enter your password') }}">
        <button type="button" class="provider-password-toggle" data-password-toggle="password_confirmation"
            data-show="{{ __('messages.Show') }}" data-hide="{{ __('messages.Hide') }}">{{ __('messages.Show') }}</button>
    </div>
</div>

<ul class="provider-password-rules provider-field-full" id="passwordRules" data-password-rules
    data-invalid="{{ __('messages.Your password does not meet all the rules below.') }}"
    data-mismatch="{{ __('messages.The two passwords do not match.') }}">
    <li data-rule="length" data-min="{{ $minLength }}">{{ __('messages.At least :count characters', ['count' => $minLength]) }}</li>
    <li data-rule="upper">{{ __('messages.One uppercase letter (A-Z)') }}</li>
    <li data-rule="lower">{{ __('messages.One lowercase letter (a-z)') }}</li>
    <li data-rule="number">{{ __('messages.One number (0-9)') }}</li>
    <li data-rule="symbol">{{ __('messages.One symbol, like ! @ # $') }}</li>
    <li data-rule="match">{{ __('messages.Both passwords match') }}</li>
</ul>
