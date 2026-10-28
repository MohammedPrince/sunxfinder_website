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
                                <option value="{{ $company->email }}" data-phone="{{ $company->phone }}">{{ $company->name }}</option>
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
                        <label for="whatsappPhone">WhatsApp number</label>
                        <input class="form-control" type="tel" id="whatsappPhone" name="whatsapp_phone"
                            autocomplete="tel" dir="ltr" placeholder="+249 9XX XXX XXX"
                            value="{{ old('whatsapp_phone') }}">
                        <small class="form-hint">Include the country code. You can also choose a registered company above.</small>
                        <small class="field-error" id="whatsappError" role="alert" hidden></small>
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
                    <button type="button" class="btn btn-green-light" id="openWhatsApp">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
                            <path d="M12.04 2a9.86 9.86 0 0 0-8.48 14.9L2 22l5.28-1.38A9.95 9.95 0 1 0 12.04 2Zm0 18.1a8.1 8.1 0 0 1-4.13-1.13l-.3-.18-3.13.82.84-3.05-.2-.31a8.15 8.15 0 1 1 6.92 3.85Zm4.47-6.1c-.24-.12-1.43-.7-1.65-.78-.22-.08-.38-.12-.54.12-.16.24-.62.78-.76.94-.14.16-.28.18-.52.06-.24-.12-1.02-.38-1.94-1.2-.72-.64-1.2-1.43-1.34-1.67-.14-.24-.02-.37.1-.49.1-.1.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.2-.46-.4-.4-.54-.4h-.46c-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2s.86 2.32.98 2.48c.12.16 1.7 2.6 4.12 3.64.58.25 1.03.4 1.38.5.58.18 1.1.15 1.51.09.46-.07 1.43-.58 1.63-1.14.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28Z" />
                        </svg>
                        Open WhatsApp
                    </button>
                </div>
            </div>
        </section>
    </form>

    <section class="panel table-panel" style="margin-top:24px;">
        <div class="panel-heading table-toolbar">
            <div>
                <h2>Sent email history</h2>
                <p>Successfully sent emails are saved here. Resending creates a new history entry.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Sent</th>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Language</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sentEmails as $sentEmail)
                        <tr>
                            <td>{{ $sentEmail->sent_at?->format('M d, Y H:i') }}</td>
                            <td dir="ltr">{{ $sentEmail->recipient_email }}</td>
                            <td dir="auto" title="{{ $sentEmail->subject }}">
                                {{ \Illuminate\Support\Str::limit($sentEmail->subject, 70) }}
                            </td>
                            <td>{{ strtoupper($sentEmail->lang) }}</td>
                            <td>
                                <div class="company-actions">
                                    <form action="{{ route('admin.send-email.resend', $sentEmail) }}" method="POST"
                                        onsubmit="return confirm('Resend this email to {{ $sentEmail->recipient_email }}?');">
                                        @csrf
                                        <button type="submit" class="row-action row-action-icon" title="Resend email"
                                            aria-label="Resend email to {{ $sentEmail->recipient_email }}">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                                stroke-linejoin="round" aria-hidden="true">
                                                <path d="M20 7v5h-5" />
                                                <path d="M4 17v-5h5" />
                                                <path d="M5.6 9a7 7 0 0 1 11.55-2.6L20 12" />
                                                <path d="M4 12l2.85 5.6A7 7 0 0 0 18.4 15" />
                                            </svg>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.send-email.delete', $sentEmail) }}" method="POST"
                                        onsubmit="return confirm('Remove this email from history?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="row-action row-action-icon row-action-delete"
                                            title="Delete from history" aria-label="Delete email to {{ $sentEmail->recipient_email }}">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                                stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4h8v2" />
                                                <path d="m19 6-1 14H6L5 6" />
                                                <path d="M10 11v5M14 11v5" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="dashboard-empty">
                                <strong>No sent emails yet.</strong>
                                <span>Emails will appear here after they are sent successfully.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sentEmails->hasPages())
            <div class="panel-body">{{ $sentEmails->links() }}</div>
        @endif
    </section>
@endsection

@section('scripts')
    <script>
        // Arabic is written right to left: flip the subject and message boxes with the language.
        (() => {
            const lang = document.getElementById('lang');
            const subject = document.getElementById('subject');
            const message = document.getElementById('message');
            const email = document.getElementById('email');
            const phone = document.getElementById('whatsappPhone');
            const whatsappError = document.getElementById('whatsappError');
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

            email.addEventListener('change', () => {
                const selectedCompany = Array.from(document.querySelectorAll('#companyEmails option'))
                    .find(option => option.value.toLowerCase() === email.value.trim().toLowerCase());
                if (selectedCompany?.dataset.phone && !phone.value.trim()) {
                    phone.value = selectedCompany.dataset.phone;
                }
            });

            document.getElementById('openWhatsApp').addEventListener('click', () => {
                whatsappError.hidden = true;
                whatsappError.textContent = '';

                const digits = phone.value.replace(/\D/g, '');
                if (digits.length < 8 || digits.length > 15) {
                    whatsappError.textContent = 'Enter a valid WhatsApp number with its country code.';
                    whatsappError.hidden = false;
                    phone.focus();
                    return;
                }

                if (!message.value.trim()) {
                    whatsappError.textContent = 'Enter a message before opening WhatsApp.';
                    whatsappError.hidden = false;
                    message.focus();
                    return;
                }

                const whatsappUrl = `https://wa.me/${digits}?text=${encodeURIComponent(message.value)}`;
                window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
            });
        })();
    </script>
@endsection
