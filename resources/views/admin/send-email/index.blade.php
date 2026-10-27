@extends('admin.layouts.app')
@section('title', 'Send Email')
@section('breadcrumb', 'Send Email')
@section('eyebrow', 'SYSTEM')
@section('page-title', 'Send Email')
@section('page-description', 'Write to one or more solar companies, e.g. to invite them to join SunXFinder.')

@php
    $arabicSubject = 'انضم إلى SunXFinder | وسّع وصول شركتك إلى عملاء الطاقة الشمسية في السودان';
    $arabicMessage = implode("\n", [
        'السادة/ إدارة الشركة المحترمين',
        '',
        'تحية طيبة،',
        '',
        'يسرّنا دعوتكم للانضمام إلى SunXFinder، المنصة الرقمية التي تهدف إلى تسهيل وصول العملاء إلى شركات ومورّدي حلول الطاقة الشمسية في السودان.',
        '',
        'من خلال المنصة، يمكنكم عرض منتجاتكم وخدماتكم، وتعزيز حضور شركتكم الرقمي، والوصول إلى عملاء يبحثون عن حلول للطاقة الشمسية، واستقبال طلبات عروض الأسعار من المهتمين.',
        '',
        'نحن حاليًا في مرحلة تسجيل الشركات استعدادًا لإطلاق المنصة، ونتطلع إلى أن تكون شركتكم من أوائل الشركات المنضمة إلينا.',
        '',
        'الموقع التجريبي:',
        'https://dev.sunxfinder.com',
        '',
        'للانضمام أو معرفة المزيد، يسعدنا التواصل معكم ومساعدتكم في خطوات التسجيل.',
        '',
        'للتواصل والتسجيل عبر واتساب:',
        'https://wa.me/249919046917',
        '',
        'مع خالص التحية والتقدير،',
        'فريق SunXFinder',
    ]);
    $selectedLanguage = old('lang', 'ar');
@endphp

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
                            <option value="en" @selected(old('lang', 'ar') === 'en')>English (en-join-request)</option>
                            <option value="ar" @selected(old('lang', 'ar') === 'ar')>العربية (ar-join-request)</option>
                        </select>
                        <small class="form-hint">Write the subject and message in the same language.</small>
                    </div>

                    <div class="form-group" style="grid-column:1/-1">
                        <label for="subject">Subject <b style="color: red">*</b></label>
                        <input class="form-control" type="text" id="subject" name="subject" maxlength="150" required
                            placeholder="e.g. Join SunXFinder and receive solar quote requests"
                            value="{{ old('subject', $selectedLanguage === 'ar' ? $arabicSubject : ($prefillSubject ?? '')) }}">
                        @error('subject')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="form-group" style="grid-column:1/-1">
                        <label for="message">Message <b style="color: red">*</b></label>
                        <textarea class="form-control" id="message" name="message" rows="9" maxlength="5000" required
                            placeholder="Write your message. Line breaks are kept.">{{ old('message', $selectedLanguage === 'ar' ? $arabicMessage : '') }}</textarea>
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
            const subject = document.getElementById('subject');
            const message = document.getElementById('message');
            const arabicSubject = @json($arabicSubject);
            const arabicMessage = @json($arabicMessage);
            let autoSubject = lang.value === 'ar' && subject.value === arabicSubject ? arabicSubject : '';
            let autoMessage = lang.value === 'ar' && message.value === arabicMessage ? arabicMessage : '';
            const apply = () => {
                const isArabic = lang.value === 'ar';
                subject.dir = message.dir = isArabic ? 'rtl' : 'ltr';
                if (isArabic) {
                    if (!subject.value.trim() || subject.value === autoSubject) subject.value = autoSubject = arabicSubject;
                    if (!message.value.trim() || message.value === autoMessage) message.value = autoMessage = arabicMessage;
                } else {
                    if (subject.value === autoSubject) subject.value = '';
                    if (message.value === autoMessage) message.value = '';
                    autoSubject = autoMessage = '';
                }
            };
            lang.addEventListener('change', apply);
            apply();
        })();
    </script>
@endsection