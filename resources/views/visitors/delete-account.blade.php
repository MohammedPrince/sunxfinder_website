{{-- Public "Delete your account" page (store listing link). ?lang=ar shows Arabic.
     Keep the "What happens to your data" list in line with Helper::softDeleteCustomerData()
     and CustomerRepository::deleteAccount(). --}}
@php
    $email = 'info@sunxfinder.com';
    $isAr = $lang === 'ar';

    $t = $isAr ? [
        'page_title' => 'حذف حسابك',
        'eyebrow' => 'الحساب',
        'title' => 'حذف حسابك في SunXFinder',
        'intro' => 'يمكنك حذف حسابك في SunXFinder في أي وقت.',
        'app_title' => 'الطريقة الأولى: من التطبيق',
        'app_steps' => [
            'افتح تطبيق SunXFinder وسجّل الدخول.',
            'اذهب إلى الملف الشخصي.',
            'اضغط حذف الحساب ثم أكّد.',
        ],
        'app_done' => 'يُحذف حسابك فورًا.',
        'project_note' => 'إذا كان لديك مشروع قيد التنفيذ، يمكنك حذف حسابك بعد اكتمال المشروع.',
        'email_title' => 'الطريقة الثانية: عبر البريد الإلكتروني',
        'email_body' => 'إذا لم تتمكن من استخدام التطبيق، أرسل رسالة إلى :email من البريد الإلكتروني المسجّل في حسابك. اكتب "حذف حسابي" مع اسم المستخدم ورقم الهاتف. سنحذف حسابك خلال 7 أيام ونؤكد لك ذلك بالبريد.',
        'email_subject' => 'حذف حسابي',
        'data_title' => 'ما الذي يحدث لبياناتك',
        'data_intro' => 'عند حذف حسابك:',
        'data_items' => [
            'يُغلق حسابك: اسم المستخدم، كلمة المرور، الاسم، رقم الهاتف، البريد الإلكتروني، الجنس، المدينة والعنوان. يتم تسجيل خروجك من جميع الأجهزة ولا يمكنك تسجيل الدخول مرة أخرى.',
            'تُحذف من التطبيق جميع طلبات عروض الأسعار، والعروض التي أرسلتها الشركات عليها، ومشاريعك وإشعاراتك، ولا تعود الشركات تراها.',
            'تُخفى تقييماتك ومراجعاتك ولا تُحتسب في تقييم الشركات.',
            'يتوقف إرسال الإشعارات إلى جهازك.',
        ],
        'keep_title' => 'ما الذي نحتفظ به',
        'keep_body' => 'نحتفظ بنسخة داخلية مؤرشفة من هذه البيانات (بدون كلمة المرور)، ولا تظهر للشركات أو لأي مستخدم آخر. إذا أردت حذف هذه النسخة أيضًا، راسلنا على :email وسنحذفها خلال 7 أيام.',
        'companies_note' => 'قد تحتفظ الشركات التي أرسلت إليها طلبات بالبيانات التي شاركتها معها.',
        'undo' => 'لا يمكن التراجع عن الحذف، وإذا أردت استخدام SunXFinder مرة أخرى فعليك إنشاء حساب جديد.',
        'contact_title' => 'هل تحتاج إلى مساعدة؟',
        'contact_body' => 'راسلنا على :email',
    ] : [
        'page_title' => 'Delete your account',
        'eyebrow' => 'Account',
        'title' => 'Delete your SunXFinder account',
        'intro' => 'You can delete your SunXFinder account at any time.',
        'app_title' => 'Option 1: In the app',
        'app_steps' => [
            'Open the SunXFinder app and sign in.',
            'Go to Profile.',
            'Tap Delete account and confirm.',
        ],
        'app_done' => 'Your account is deleted right away.',
        'project_note' => 'If you have a project in progress, you can delete your account once the project is completed.',
        'email_title' => 'Option 2: By email',
        'email_body' => 'If you can\'t use the app, email :email from the email address on your account. Write "Delete my account" and include your username and phone number. We will delete your account within 7 days and confirm by email.',
        'email_subject' => 'Delete my account',
        'data_title' => 'What happens to your data',
        'data_intro' => 'When your account is deleted:',
        'data_items' => [
            'Your account is closed: username, password, name, phone number, email, gender, city and address. You are signed out on every device and can no longer sign in.',
            'All your quote requests, the offers companies sent on them, your projects and your notifications are removed from the app, and companies can no longer see them.',
            'Your reviews and ratings are hidden and no longer count in company ratings.',
            'Notifications to your device stop.',
        ],
        'keep_title' => 'What we keep',
        'keep_body' => 'We keep an internal archived copy of this data (without your password). It is not visible to companies or other users. If you want this copy erased too, email :email and we will erase it within 7 days.',
        'companies_note' => 'Companies you sent a quote to may keep the details you shared with them.',
        'undo' => 'This cannot be undone. To use SunXFinder again, you will need to create a new account.',
        'contact_title' => 'Need help?',
        'contact_body' => 'Email us at :email',
    ];

    $mailLink = fn(string $subject) => '<a href="mailto:' . $email . '?subject=' . rawurlencode($subject) . '">' . $email . '</a>';
    $withEmail = fn(string $text, string $subject = '') => str_replace(':email', $mailLink($subject), e($text));
@endphp

@extends('visitors.layouts.app')
@section('title', 'SunXFinder - ' . $t['page_title'])
@section('content')
    @push('styles')
        <style>
            .legal-card ol {
                padding-inline-start: 22px;
                margin: 10px 0 12px;
            }

            .legal-card ol li::marker {
                color: #ff5100;
                font-weight: 700;
            }

            .legal-card a {
                color: #087fe0;
                font-weight: 700;
                text-decoration: none;
                overflow-wrap: anywhere;
            }

            .legal-card a:hover {
                color: #ff5100;
                text-decoration: underline;
            }

            .delete-account-note {
                padding: 12px 16px;
                border-radius: 12px;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #9a3412 !important;
            }
        </style>
    @endpush

    <main class="legal-page" lang="{{ $lang }}" @if ($isAr) dir="rtl" @endif>
        <div class="legal-container">
            <header class="legal-hero">
                <span class="legal-eyebrow">{{ $t['eyebrow'] }}</span>
                <h1>{{ $t['title'] }}</h1>
                <p>{{ $t['intro'] }}</p>
            </header>

            <section class="legal-card">
                <h2>{{ $t['app_title'] }}</h2>
                <ol>
                    @foreach ($t['app_steps'] as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ol>
                <p>{{ $t['app_done'] }}</p>
                <p class="delete-account-note">{{ $t['project_note'] }}</p>
            </section>

            <section class="legal-card">
                <h2>{{ $t['email_title'] }}</h2>
                {{-- Escaped text; only the mailto link is HTML. --}}
                <p>{!! $withEmail($t['email_body'], $t['email_subject']) !!}</p>
            </section>

            <section class="legal-card">
                <h2>{{ $t['data_title'] }}</h2>
                <p>{{ $t['data_intro'] }}</p>
                <ul>
                    @foreach ($t['data_items'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </section>

            <section class="legal-card">
                <h2>{{ $t['keep_title'] }}</h2>
                <p>{!! $withEmail($t['keep_body'], $t['email_subject']) !!}</p>
                <p>{{ $t['companies_note'] }}</p>
                <p><strong>{{ $t['undo'] }}</strong></p>
            </section>

            <section class="legal-contact">
                <h2>{{ $t['contact_title'] }}</h2>
                <p>{!! $withEmail($t['contact_body']) !!}</p>
            </section>
        </div>
    </main>
@endsection
