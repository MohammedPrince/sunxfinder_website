@extends('admin.layouts.app')
@section('title', 'Send Email')
@section('breadcrumb', 'Send Email')
@section('eyebrow', 'SYSTEM')
@section('page-title', 'Send Email')
@section('page-description', 'Write to one or more solar companies, e.g. to invite them to join SunXFinder.')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

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

    <form method="POST" action="{{ route('admin.send-email.send') }}"
        onsubmit="this.querySelector('button[type=submit]').disabled = true;">
        @csrf

        <section class="panel">
            <div class="panel-heading">
                <div>
                    <h2>New email</h2>
                    <p>
                        Sent from <strong>{{ \App\Mail\CompanyJoinRequestMail::FROM_ADDRESS }}</strong>; replies come back
                        to that inbox. A silent copy (BCC) goes to {{ \App\Mail\CompanyJoinRequestMail::BCC_ADDRESS }}.
                    </p>
                </div>
            </div>

            <div class="panel-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="email">Email recipients <b style="color: red">*</b></label>
                        <input class="form-control" type="text" id="email" name="email" list="companyEmails"
                            maxlength="5000" required dir="ltr" placeholder="company@example.com, another@example.com"
                            value="{{ old('email', $prefillEmail ?? '') }}">
                        <datalist id="companyEmails">
                            @foreach ($companies as $company)
                                <option value="{{ $company->email }}">{{ $company->name }}</option>
                            @endforeach
                        </datalist>
                        <small class="form-hint">
                            Enter multiple addresses separated by commas (spaces also work), or pick a registered company.
                            Each recipient gets a separate email, and registered companies are greeted by name.
                        </small>
                        @error('email')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="lang">Language <b style="color: red">*</b></label>
                        <select class="form-control" id="lang" name="lang" required>
                            <option value="en" @selected(old('lang', 'en') === 'en')>English (en-join-request)</option>
                            <option value="ar" @selected(old('lang') === 'ar')>العربية (ar-join-request)</option>
                        </select>
                        <small class="form-hint">Write the subject and message in the same language.</small>
                    </div>

                    <div class="form-group" style="grid-column:1/-1">
                        <label for="subject">Subject <b style="color: red">*</b></label>
                        <input class="form-control" type="text" id="subject" name="subject" maxlength="150" required
                            placeholder="e.g. Join SunXFinder and receive solar quote requests"
                            value="{{ old('subject', $prefillSubject ?? '') }}">
                        @error('subject')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group" style="grid-column:1/-1">
                        <label for="message">Message <b style="color: red">*</b></label>
                        <textarea class="form-control" id="message" name="message" rows="9" maxlength="5000" required
                            placeholder="Write your message. Line breaks are kept.">{{ old('message') }}</textarea>
                        <small class="form-hint">
                            The email adds the SunXFinder logo, a greeting and a "Join SunXFinder" button for you.
                        </small>
                        @error('message')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Send email</button>
                </div>
            </div>
        </section>
    </form>
@endsection

@section('scripts')
    <script>
        // Arabic is written right to left: flip the subject and message boxes with the language.
        (() => {
            const lang = document.getElementById('lang');
            const fields = [document.getElementById('subject'), document.getElementById('message')];
            const apply = () => fields.forEach(field => field.dir = lang.value === 'ar' ? 'rtl' : 'ltr');
            lang.addEventListener('change', apply);
            apply();
        })();
    </script>
@endsection
