@extends('visitors.layouts.app')
@section('title', app()->getLocale() === 'ar' ? 'SunXFinder - سياسة الخصوصية' : 'SunXFinder - Privacy Policy ')
@section('content')

    <main class="legal-page" lang="en">
        <div class="legal-container">

            <header class="legal-hero">
                <span class="legal-eyebrow">Your Privacy Matters</span>
                <h1>Privacy Policy</h1>
                <p>
                    At SunXFinder, we respect your privacy and are committed to
                    protecting your personal information when you explore solar
                    solutions, create an account, or connect with solar providers.
                </p>
                <span class="legal-updated">Last updated: October 31, 2026</span>
            </header>

            <section class="legal-card">
                <h2>1. Introduction</h2>
                <p>
                    This Privacy Policy explains how SunXFinder collects, uses,
                    stores, and protects information when you visit our website,
                    use our mobile app, or use our services.
                </p>
                <p>
                    By using SunXFinder, you acknowledge that your information
                    may be handled as described in this policy. If you do not
                    agree with this policy, please discontinue use of the
                    services that require the relevant information.
                </p>
            </section>

            <section class="legal-card">
                <h2>2. Information We Collect</h2>

                <h3>Personal information</h3>
                <p>Depending on how you use the platform, we may collect:</p>
                <ul>
                    <li>Your name and username.</li>
                    <li>Email address and telephone number.</li>
                    <li>Gender, if you choose to provide it.</li>
                    <li>City, address, or other location details you provide.</li>
                    <li>Account credentials, stored in protected form.</li>
                    <li>Information you submit through forms or customer support.</li>
                </ul>

                <h3>Quote requests and projects</h3>
                <p>
                    When you use the platform we store your quote requests, including
                    the appliances and estimated energy load you enter and any message
                    you add, along with the offers and invoices you receive, your
                    project progress, and the ratings, comments, and complaints you
                    submit.
                </p>

                <h3>Mobile app and device information</h3>
                <p>
                    If you use the mobile app, we store a device token used to send
                    push notifications, along with your device type, device name, and
                    app language.
                </p>

                <h3>Solar company information</h3>
                <p>
                    Solar providers may submit business names, contact details,
                    service descriptions, company logos, license information,
                    social media links, and other business profile information.
                </p>

                <h3>Technical information</h3>
                <p>
                    Our hosting environment or website tools may process technical
                    information such as IP addresses, browser details, access
                    timestamps, and error logs for security, troubleshooting,
                    and website operation. We also keep activity logs of important
                    account actions, including the IP address and browser used. We use
                    your IP address to work out your country for visitor statistics.
                </p>
            </section>

            <section class="legal-card">
                <h2>3. How We Use Your Information</h2>
                <p>We may use collected information to:</p>
                <ul>
                    <li>Create and manage customer and solar provider accounts.</li>
                    <li>Display company profiles and solar services.</li>
                    <li>Help customers discover and contact solar providers.</li>
                    <li>Deliver quote requests to companies and offers to customers.</li>
                    <li>Send emails and push notifications about your account, requests,
                        offers, and projects.</li>
                    <li>Send service updates and promotional notifications from SunXFinder
                        or from solar companies.</li>
                    <li>Review accounts and manage approval or verification.</li>
                    <li>Respond to enquiries, handle complaints, and provide support.</li>
                    <li>Produce visitor and usage statistics.</li>
                    <li>Protect the platform against fraud, abuse, and unauthorized access.</li>
                    <li>Maintain, troubleshoot, and improve our services.</li>
                    <li>Meet applicable legal obligations.</li>
                </ul>
            </section>

            <section class="legal-card">
                <h2>4. Publicly Visible Information</h2>
                <p>
                    Information included in a public solar company profile may be
                    visible to website visitors. This may include the company name,
                    logo, city, service descriptions, business contact details,
                    and social media links.
                </p>
                <p>
                    Ratings and comments you leave on a company can also be publicly
                    visible on that company's profile, with a shortened form of your
                    name.
                </p>
                <p>
                    Do not submit passwords, private identity documents, or other
                    confidential personal information into fields intended for
                    public display. We do not intentionally display customer
                    passwords or private authentication credentials.
                </p>
            </section>

            <section class="legal-card">
                <h2>5. Sharing of Information</h2>
                <p>
                    SunXFinder does not needlessly disclose personal information.
                    Information may be shared in limited circumstances, including:
                </p>
                <ul>
                    <li>
                        <strong>With your direction:</strong> when you choose to
                        contact a solar provider or submit information for that purpose.
                    </li>
                    <li>
                        <strong>Quote requests:</strong> a request sent to a chosen
                        company is shared with that company. A request sent without
                        choosing a company is open to all solar companies on SunXFinder.
                        In both cases the company can see your full name, email address,
                        telephone number, city, and request details, even if it does not
                        send you an offer.
                    </li>
                    <li>
                        <strong>Invoices and projects:</strong> a company you work with
                        can use your name and contact details on offers, invoices, and
                        project records.
                    </li>
                    <li>
                        <strong>Complaints:</strong> a complaint about a company may be
                        shared with that company so that it can be resolved.
                    </li>
                    <li>
                        <strong>Service providers:</strong> with hosting,
                        maintenance, security, email delivery, push notification (Google
                        Firebase Cloud Messaging), IP-to-country lookup, or other
                        providers supporting platform operations, where applicable.
                        Some of them may process information outside your country.
                    </li>
                    <li>
                        <strong>Legal requirements:</strong> when disclosure is
                        required by applicable law or a lawful request.
                    </li>
                    <li>
                        <strong>Security:</strong> when reasonably necessary to
                        investigate fraud, protect users, or safeguard the platform.
                    </li>
                </ul>
                <p>
                    We do not promise that information shared directly with an
                    independent solar company will be governed by this policy.
                    Please review that company's own privacy practices.
                </p>
            </section>

            <section class="legal-card">
                <h2>6. Passwords and Account Security</h2>
                <p>
                    We use reasonable measures intended to protect account
                    information. Passwords are stored using secure password
                    hashing, and access to account data is restricted.
                </p>
                <p>
                    No website, server, or electronic transmission can be guaranteed
                    completely secure. Please choose a strong, unique password and
                    notify us if you suspect unauthorized use of your account.
                </p>
            </section>

            <section class="legal-card">
                <h2>7. Cookies and Similar Technologies</h2>
                <p>
                    SunXFinder or its website infrastructure may use cookies or
                    similar technologies to maintain sessions, remember your language,
                    support security, count visits, or improve website functionality.
                </p>
                <p>
                    You can manage cookies through your browser settings. Blocking
                    certain cookies may affect login or other website features.
                </p>
            </section>

            <section class="legal-card">
                <h2>8. Data Retention</h2>
                <p>
                    We retain information for as long as reasonably necessary to
                    provide the services, maintain accounts and records, resolve
                    disputes, protect platform security, and comply with applicable
                    legal obligations.
                </p>
                <p>
                    Retention periods may vary according to the type of information
                    and the reason it was collected.
                </p>
                <p>
                    When a customer account is deleted, it and its requests, offers,
                    projects, notifications, and ratings are removed from the service.
                    We may keep an archived copy of these records for recordkeeping,
                    security, dispute resolution, and legal purposes.
                </p>
            </section>

            <section class="legal-card">
                <h2>9. Your Choices and Rights</h2>
                <p>
                    Subject to applicable law and our legitimate operational
                    requirements, you may request to:
                </p>
                <ul>
                    <li>Access personal information associated with your account.</li>
                    <li>Correct inaccurate or incomplete information.</li>
                    <li>Request deletion of information that no longer needs to be retained.</li>
                    <li>Ask questions about how your information is handled.</li>
                </ul>
                <p>
                    You can delete your customer account yourself in the mobile app,
                    except while you have a project in progress. You can also turn
                    push notifications off in the app. To make any other request, use
                    the contact details below.
                </p>
                <p>
                    We may need to verify your identity before acting on a request.
                    Some information may need to be retained where required by law
                    or necessary for legitimate security and recordkeeping purposes.
                </p>
            </section>

            <section class="legal-card">
                <h2>10. Third-Party Websites</h2>
                <p>
                    Company profiles and other pages may link to external websites
                    or social media services. SunXFinder does not control those
                    websites and is not responsible for their content, security,
                    or privacy practices. Visiting them is at your own discretion.
                </p>
            </section>

            <section class="legal-card">
                <h2>11. Children's Privacy</h2>
                <p>
                    SunXFinder is intended for people who can lawfully use the
                    platform under the applicable rules in their location. We do
                    not knowingly collect children's personal information in
                    violation of applicable law. If you believe a child has
                    provided personal information improperly, contact us.
                </p>
            </section>

            <section class="legal-card">
                <h2>12. Changes to This Policy</h2>
                <p>
                    We may update this Privacy Policy as our services, technology,
                    or legal requirements change. Updates will be published on
                    this page with a revised date. Please review this page
                    periodically.
                </p>
            </section>

            <section class="legal-contact">
                <h2>Questions About Your Privacy?</h2>
                <p>
                    Contact the SunXFinder team if you have a privacy question or
                    want to make a request about your personal information.
                </p>
                <p>
                    Email:
                    <a href="mailto:privacy@sunxfinder.com">privacy@sunxfinder.com</a>
                </p>
        
            </section>

             {{-- <nav class="legal-footer-links" aria-label="روابط الصفحات القانونية">
                <a href="{{ route('privacy-policy', ['lang' => 'ar']) }}">سياسة الخصوصية</a>
                <a href="{{ url('/?lang=ar') }}">العودة إلى الصفحة الرئيسية</a>
            </nav> --}}

        </div>
    </main>
@endsection
