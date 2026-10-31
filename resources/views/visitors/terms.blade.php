@extends('visitors.layouts.app')

@section('title', 'SunXFinder - Terms and Conditions')

@section('content')
    <style>

    </style>

    <main class="legal-page" lang="en">
        <div class="legal-container">
            <header class="legal-hero">
                <span class="legal-eyebrow">Terms &amp; Conditions</span>
                <h1>Terms and Conditions</h1>
                <p>
                    Please read these terms carefully before using SunXFinder to discover solar solutions
                    or connect with solar energy companies and service providers.
                </p>
                <span class="legal-updated">Last updated: October 31, 2026</span>
            </header>

            <section class="legal-card">
                <h2>1. Acceptance of These Terms</h2>
                <p>These Terms and Conditions govern your access to and use of SunXFinder, including its website and mobile
                    app. By using either of them or creating an account, you agree to these terms. If you do not agree, please discontinue use of the relevant
                    services.</p>
                <p>If you use SunXFinder on behalf of a company or other organization, you confirm that you are authorized
                    to act on its behalf.</p>
            </section>

            <section class="legal-card">
                <h2>2. About SunXFinder</h2>
                <p>SunXFinder is an online platform intended to help customers discover solar energy companies, explore
                    their services, request quotes, compare offers, follow project progress, and rate the providers they
                    work with.</p>
                <p>Unless a separate written agreement expressly says otherwise, SunXFinder is not the seller, installer,
                    manufacturer, or warranty provider for products or services offered by independent providers listed on
                    the platform.</p>
            </section>

            <section class="legal-card">
                <h2>3. Eligibility and Accounts</h2>
                <p>You must be legally permitted to use the platform under the laws applicable to you. When creating an
                    account, you agree to:</p>
                <ul>
                    <li>Provide accurate, complete, and current information.</li>
                    <li>Keep your login credentials confidential and use a strong password.</li>
                    <li>Use your account only for lawful purposes.</li>
                    <li>Notify us if you suspect unauthorized access to your account.</li>
                    <li>Update your information when it changes.</li>
                </ul>
                <p>We may review, restrict, suspend, or refuse an account where reasonably necessary to protect the
                    platform, enforce these terms, or comply with applicable law.</p>
            </section>

            <section class="legal-card">
                <h2>4. Customer Responsibilities</h2>
                <p>Customers agree to provide genuine information, communicate respectfully, and use company listings for
                    legitimate enquiries. Before entering into an agreement, customers should independently evaluate
                    providers, quotations, equipment specifications, installation arrangements, warranties, and payment
                    terms.</p>
                <p>Customers are responsible for the decisions they make and any contracts they enter into directly with a
                    provider.</p>
                <p>When you send a quote request without choosing a company, it is open to all solar companies on
                    SunXFinder, and they can see your contact details in order to respond. The Privacy Policy explains
                    what is shared.</p>
                <p>Ratings and complaints must be honest and based on your own experience with the company.</p>
            </section>

            <section class="legal-card">
                <h2>5. Solar Company Responsibilities</h2>
                <p>Companies using SunXFinder agree to:</p>
                <ul>
                    <li>Provide truthful business, contact, service, and licensing information.</li>
                    <li>Keep their profiles accurate and update information when necessary.</li>
                    <li>Maintain licenses and permits required for their business activities.</li>
                    <li>Use only logos, photographs, and materials they are authorized to publish.</li>
                    <li>Respect customer privacy and comply with applicable laws.</li>
                    <li>Describe products, services, prices, qualifications, and warranties honestly.</li>
                    <li>Use customer contact details only to handle that customer's request or project.</li>
                    <li>Send notifications only through the platform, only to the customers they are permitted to
                        reach, and never in a misleading or excessive way.</li>
                    <li>Make sure the offers and invoices they issue are accurate and meet applicable tax and invoicing
                        requirements.</li>
                </ul>
                <p>Companies remain responsible for their products, installations, quotations, contracts, warranties, and
                    dealings with customers.</p>
            </section>

            <section class="legal-card">
                <h2>6. Listings, Approval, and Verification</h2>
                <p>SunXFinder may review company profiles and display approval or verification indicators where available.
                    Such indicators reflect only the scope of the relevant review and are not a guarantee of a company's
                    competence, financial standing, product quality, safety, or future performance.</p>
                <p>Users should independently confirm important claims, licenses, references, equipment specifications, and
                    contractual terms before making a purchase or hiring a provider.</p>
            </section>

            <section class="legal-card">
                <h2>7. Products, Services, and Transactions</h2>
                <p>Unless a separate agreement states otherwise, transactions for solar products and services are between
                    the customer and the relevant provider. SunXFinder does not guarantee the availability, pricing,
                    quality, suitability, safety, or legality of third-party products or services.</p>
                <p>Offers and invoices shown on the platform are issued by the provider, not by SunXFinder. SunXFinder does
                    not process payments for these transactions. Accepting an offer on the platform is a step between you
                    and that provider, and SunXFinder is not a party to any resulting agreement.</p>
                <p>Delivery, payment, installation, maintenance, warranties, and after-sales support are governed by the
                    agreements between the customer and provider. Solar output and savings depend on equipment,
                    installation, site conditions, weather, usage, and maintenance; no specific result is guaranteed by a
                    listing alone.</p>
            </section>

            <section class="legal-card">
                <h2>8. Prohibited Activities</h2>
                <p>You must not:</p>
                <ul>
                    <li>Use the platform for unlawful, fraudulent, or deceptive purposes.</li>
                    <li>Submit false identities, credentials, reviews, or business information.</li>
                    <li>Access another user's account or data without authorization.</li>
                    <li>Upload malware or attempt to disrupt the website or its security.</li>
                    <li>Infringe intellectual property, privacy, or other legal rights.</li>
                    <li>Abuse the platform to send spam, harass users, or mislead customers.</li>
                    <li>Scrape or exploit platform data in violation of applicable law or these terms.</li>
                </ul>
            </section>

            <section class="legal-card">
                <h2>9. Intellectual Property and Submitted Content</h2>
                <p>SunXFinder's branding, design, and original platform materials are owned by or licensed to their
                    respective rights holders and are protected by applicable laws. You may not use them beyond what is
                    permitted by law or by written permission.</p>
                <p>Companies and users remain responsible for the content they submit and confirm they have the rights
                    needed to publish it. By submitting content, you permit SunXFinder to display and use it as reasonably
                    necessary to operate and present the relevant platform features, subject to applicable law and any
                    separate agreement.</p>
                <p>Ratings and comments you submit may appear publicly on the company's profile with a shortened form of
                    your name. We may hide content that breaches these terms.</p>
            </section>

            <section class="legal-card">
                <h2>10. Privacy</h2>
                <p>Our handling of personal information is described in our <a class="legal-inline-link"
                        href="{{ route('privacy-policy', ['lang' => 'en']) }}">Privacy Policy</a>. Please review it to
                    understand how information may be collected and used.</p>
            </section>

            <section class="legal-card">
                <h2>11. Website Availability and Changes</h2>
                <p>We aim to maintain a useful and reliable service but do not guarantee uninterrupted access, error-free
                    operation, or that every listing will always be current. We may update, suspend, remove, or discontinue
                    features or listings for maintenance, security, operational, or legal reasons.</p>
            </section>

            <section class="legal-card">
                <h2>12. Disclaimers</h2>
                <p>To the extent permitted by law, SunXFinder is provided on an “as available” basis. Platform information
                    is for general discovery and informational purposes and is not a substitute for professional technical,
                    electrical, engineering, or legal advice.</p>
                <p>Users should obtain appropriate professional advice and written quotations before making significant
                    purchasing, installation, or investment decisions.</p>
            </section>

            <section class="legal-card">
                <h2>13. Limitation of Liability</h2>
                <p>To the extent permitted by applicable law, SunXFinder is not liable for indirect or consequential losses,
                    lost profits, or losses arising from third-party listings, provider conduct, product defects,
                    installation work, or transactions between customers and providers.</p>
                <p>Nothing in these terms excludes or limits liability where such exclusion or limitation is prohibited by
                    law.</p>
            </section>

            <section class="legal-card">
                <h2>14. Suspension and Termination</h2>
                <p>We may restrict or suspend access if we reasonably believe an account breaches these terms, threatens
                    platform security, or is being used unlawfully. Where appropriate and permitted by law, we may provide
                    notice or an opportunity to resolve the issue.</p>
                <p>You may stop using SunXFinder at any time. Customers can delete their account from the mobile app, except
                    while they have a project in progress. Account closure and data retention remain subject to applicable
                    legal requirements and our Privacy Policy.</p>
            </section>

            <section class="legal-card">
                <h2>15. Changes to These Terms</h2>
                <p>We may revise these terms as the platform or legal requirements change. The updated version will be
                    published on this page with a revised date. Please review the terms periodically.</p>
            </section>

            <section class="legal-card">
                <h2>16. Governing Law</h2>
                <p>These terms are subject to applicable law. Before publication, SunXFinder's operator should specify the
                    governing law and appropriate dispute-resolution forum based on the business's place of establishment
                    and applicable legal requirements.</p>
            </section>

            <section class="legal-contact">
                <h2>Questions About These Terms?</h2>
                <p>Contact the SunXFinder team if you have questions about these Terms and Conditions.</p>
                <p>Email: <a href="mailto:info@sunxfinder.com">info@sunxfinder.com</a></p>
            </section>

            {{-- <nav class="legal-footer-links" aria-label="Legal pages">
                <a href="{{ route('privacy-policy', ['lang' => 'en']) }}">Privacy Policy</a>
                <a href="{{ url('/?lang=en') }}">Back to Home</a>
            </nav> --}}
        </div>
    </main>
@endsection
