<?php

// Company Manual (Company panel → System → Company Manual). Same keys in en and ar.
// Steps and tips may use <strong>: the text is ours and is printed unescaped.
return [
    'title' => 'Company Manual',
    'eyebrow' => 'HELP & GUIDE',
    'page_description' => 'How to use the SunXFinder company panel, step by step.',
    'print' => 'Print / Save PDF',
    'contents' => 'Contents',
    'open_page' => 'Open this page',
    'back_to_top' => 'Back to top',
    'steps' => 'Step by step',
    'tips' => 'Good to know',
    'intro_title' => 'Welcome to SunXFinder',
    'intro_body' => 'SunXFinder connects your solar company with customers who need a solar system. Customers use the app to calculate their load and ask for prices. You send offers, win customers, install their systems and keep them updated, all from this panel.',
    'flow_title' => 'How a customer becomes your project',
    'flow' => [
        ['Customer asks', 'A customer calculates their load in the app and sends a quote request.'],
        ['You send an offer', 'You send one total price, with an invoice and a message.'],
        ['Customer chooses', 'The customer compares offers and accepts the one they like.'],
        ['You install', 'A project is created. You post progress until handover.'],
        ['Customer rates you', 'The customer rates your work. Good ratings bring more customers.'],
    ],

    // "How to use the system" dialog shown on the company's first sign-in.
    'journey_title' => 'How to use the system',
    'journey_start_title' => 'Start with these steps',
    'journey_start' => [
        ['Complete your company profile', 'Add your logo, description and contact details so SunXFinder can approve your company.', 'company.profile'],
        ['Add your products', 'The panels, inverters and batteries you sell. They show on your page and you use them in invoices.', 'company.products.index'],
        ['Send your first offer', 'Open Quote Requests and send your price to customers.', 'company.quote-requests.index'],
    ],
    'journey_manual' => 'Open the full manual',
    'journey_done' => 'Get started',
    'journey_note' => 'You can find this guide any time under "Help & Guide" in the top bar.',

    'sections' => [
        'start' => [
            'title' => 'Getting started',
            'intro' => 'Your account, signing in, and the parts of the screen.',
            'steps' => [
                '<strong>Create your account</strong> from "Create company account" on the SunXFinder website, then sign in at the company portal with your company username and password.',
                '<strong>Forgot your password?</strong> Press "Forgot password?" on the sign-in page and enter your registered email. You get a 6-digit code that works for 5 minutes. Enter it with your new password.',
                '<strong>The side menu</strong> holds every page, in groups: Overview, Management, Quotes & Projects, Customers and System.',
                '<strong>The top bar</strong> has Quick actions (find new requests, update a project, add a product, send a notification) and a bell with the tasks waiting for you.',
                '<strong>Switch language</strong> between English and العربية from the top bar. The whole panel changes, including right-to-left layout.',
            ],
            'tips' => [
                'Customers can only find you after SunXFinder approves your company. Complete your profile first (see Company profile).',
                'Many pages have a "How it works" box at the top. Close it when you no longer need it.',
                'Never share your password or your reset code. SunXFinder staff will never ask for them.',
            ],
        ],
        'profile' => [
            'title' => 'Company profile and approval',
            'intro' => 'The information customers see on your public page and in the app.',
            'steps' => [
                'Open <strong>Management → Company Profile</strong>.',
                'Fill in your <strong>company name, email, phone, city and license number</strong>. These are required for approval.',
                'Upload your <strong>logo</strong> (JPG, PNG or WEBP, up to 2 MB), add your years of experience and number of projects, and your social media links (one per line).',
                'Write your <strong>company description in English and Arabic</strong>: your services, experience and the areas you serve.',
                'Press <strong>Save</strong>. The status box shows: incomplete, waiting for approval, approved, or rejected.',
                'To change your password, type a new one in "New Password" and confirm it. Leave it empty to keep the current one.',
            ],
            'tips' => [
                'Once approved, "Company On Website" in the System menu opens your public page.',
                'To change your username, contact SunXFinder support.',
                'A complete profile with a clear logo and description wins more offers: customers compare your profile, not only your price.',
            ],
        ],
        'products' => [
            'title' => 'My products',
            'intro' => 'The panels, inverters, batteries and other products you sell.',
            'steps' => [
                'Open <strong>Management → My Products</strong> and press <strong>+ Add Product</strong>.',
                'Choose the <strong>supplier / brand</strong> and <strong>category</strong>, then enter the product name, model number and a short description.',
                'Add <strong>specifications</strong>, for example Capacity = 5 kW, Wattage = 550 W, Voltage = 48 V.',
                'Tick <strong>Show on company profile</strong> to make it visible to customers, then save.',
                'Use the edit and delete buttons in the list to keep your catalog up to date.',
            ],
            'tips' => [
                'Your products are also used to build special offers quickly.',
                'Hide a product you no longer sell instead of leaving wrong information visible.',
            ],
        ],
        'quotes' => [
            'title' => 'Quote requests and offers',
            'intro' => 'Customers asking for a solar system. This is where you win new customers.',
            'steps' => [
                'Open <strong>Quotes & Projects → Quote requests</strong>. A request is either "Sent to you directly" or "Open to all companies". The <strong>Next step</strong> column tells you what to do.',
                'Open a request and check the customer\'s <strong>energy needs</strong>: daily energy, peak load, and the suggested panels, inverter and batteries.',
                'Look at <strong>Competitor pricing</strong>: other companies\' prices are shown without their names.',
                'Press <strong>Make an Offer</strong>. Choose the currency (SDG or USD).',
                'Build the <strong>invoice</strong>: check the invoice number and date, add each item with quantity and unit price (or use Quick add), then any discount, tax and shipping. The total is calculated for you and must be at least 1,000.',
                'Write your <strong>message to the customer in English and Arabic</strong>: what is included, warranty, delivery time.',
                'Press <strong>Submit Offer</strong>. You can <strong>edit it</strong> any time until the customer decides.',
                'If the customer chooses you, <strong>View Project</strong> appears at the top of the request and a project is created.',
            ],
            'tips' => [
                'Customers usually decide quickly. Answer new requests the same day: faster offers win more often.',
                'If you prefer, turn the invoice off and type one total price yourself.',
                'Each invoice number can be used only once by your company.',
                'The SunXFinder commission shown in the offer summary is due only if the customer accepts your offer. The customer does not see it, and it is not on the invoice.',
                'Once the customer accepts, your offer and invoice are locked.',
            ],
        ],
        'package_offers' => [
            'title' => 'Special offers',
            'intro' => 'A ready system (panels, inverter, batteries, installation) that you push to your customers.',
            'steps' => [
                'Open <strong>Quotes & Projects → Special offers</strong> and press <strong>+ New special offer</strong>.',
                'Write the <strong>title and description</strong> in English and Arabic.',
                'Set the <strong>maximum load</strong> (kW) the system can power, and optionally a <strong>Valid until</strong> date.',
                'Add the <strong>system items</strong> from your products, or type custom lines such as installation. The price the customer pays is shown at the bottom.',
                'Save, add an image, then <strong>Send to customers</strong>. They get a push with the image and can open the offer in the app.',
                'A customer who accepts enters their devices in the load calculator. The app only lets them send a load up to your limit.',
                'Accepted offers appear under <strong>Customer answers</strong> and as projects waiting to start in Active Projects.',
            ],
            'tips' => [
                'You can edit an offer only until you send it the first time. To change a sent offer, close it and use "Make a copy".',
                'Accepting is final on both sides: you do not send the offer again.',
                'An offer that was never sent can be deleted. A sent offer can only be closed.',
            ],
        ],
        'projects' => [
            'title' => 'Active projects',
            'intro' => 'The installations for customers who accepted your offer. The customer sees every update in the app.',
            'steps' => [
                'Open <strong>Quotes & Projects → Active Projects</strong>. A new project shows as "Waiting to start".',
                'Open the project and use the <strong>customer contact details</strong> to arrange the site visit.',
                'Press <strong>Start Installation</strong> when your team is ready.',
                'Complete each <strong>milestone</strong> in order and write a short message for the customer (up to 500 characters). Progress is calculated automatically.',
                'When everything is installed and explained, press <strong>Confirm Customer Handover</strong>. The customer then confirms the handover in the app, which completes the project (automatically after 7 days if they do not answer).',
            ],
            'tips' => [
                'Handover cannot be undone: the project is locked after it. Check the installation first.',
                'Projects without an update for several days are flagged. Post a short update so the customer knows what is happening.',
                'Finishing on time and keeping customers informed is the best way to get good ratings.',
            ],
        ],
        'withdrawals' => [
            'title' => 'Withdrawing or changing the price',
            'intro' => 'When you cannot do an accepted job at the agreed price, tell the customer early. They keep their request and can choose another company.',
            'steps' => [
                'Open the project in <strong>Active Projects</strong>. Use the <strong>Problem with this job?</strong> box under the project workflow.',
                '<strong>Change the price</strong> (only before you start): enter the new price, pick a reason and add a note for the customer. The customer accepts or declines in the app. You cannot start the installation until they answer.',
                'If the customer <strong>accepts</strong>, your offer shows the new price and you can start. If they <strong>decline</strong>, the job ends and the request opens again for other companies. A declined price does not count against you.',
                '<strong>Withdraw from this job</strong> before you start: pick a reason and confirm. The job is cancelled right away, the customer is told, and other companies\' offers can be chosen again.',
                'After you started (or while waiting for the customer to confirm the handover), you can only <strong>Send cancellation request</strong>. Explain what happened in at least 10 characters. The SunXFinder team contacts you and the customer, then approves or rejects it. You can keep working, or take the request back, while you wait.',
                'You get an email with the decision. If it is rejected, the reason shows on the project page and the job carries on.',
            ],
            'tips' => [
                'Each withdrawal counts against your company for 30 days. At 3 withdrawals in 30 days, every new withdrawal needs SunXFinder\'s approval and your company is listed lower in the app.',
                'Asking for a new price is better than withdrawing: the customer can still say yes, and a decline is never counted.',
                'Your note is seen by the SunXFinder team only. The customer sees the reason you picked.',
                'When another company withdraws, you may get an email that a request is <strong>open again</strong> and your offer can be chosen. Check that your price and validity are still right, and update the offer if needed.',
            ],
        ],
        'invoices' => [
            'title' => 'Invoices',
            'intro' => 'All invoices you created with your offers.',
            'steps' => [
                'Open <strong>Quotes & Projects → Invoices</strong>. Search by invoice number, customer or quote number.',
                'Use <strong>View invoice</strong> to see or print it, or <strong>Download PDF</strong> to save it.',
                'You can edit or delete an invoice while the customer has not accepted the offer.',
            ],
            'tips' => [
                'An invoice is locked once the customer accepts the offer.',
                'Use "Payment instructions / remarks" in the invoice for bank details or deposit terms.',
            ],
        ],
        'customers' => [
            'title' => 'Company customers',
            'intro' => 'Customers who chose your company by accepting one of your offers.',
            'steps' => [
                'Open <strong>Customers → Company Customers</strong>. A customer appears here once they accept your offer.',
                'Search by name, phone or city, and open their latest project.',
                'Press <strong>Notify</strong> to send one customer a push notification, or <strong>Notify all my customers</strong>.',
            ],
            'tips' => [
                'Returning customers (2 or more projects) are counted on this page and in Reports.',
            ],
        ],
        'notifications' => [
            'title' => 'Push notifications',
            'intro' => 'Messages to your customers\' phones through the SunXFinder app.',
            'steps' => [
                'Open <strong>Customers → Push Notifications</strong> and press <strong>+ Send Notification</strong>.',
                'Choose the <strong>notification type</strong> (announcement, promotion, maintenance…).',
                'Select the <strong>customers</strong>. Hold Ctrl (Windows) or Command (Mac) to pick several, or use "Select all customers".',
                'Write the <strong>title and message in English and Arabic</strong>. Each customer gets the language of their app.',
                'Press send. Open a notification in the list to see who received and read it.',
            ],
            'tips' => [
                'You can send only to your own customers (those who accepted your offer). If SunXFinder opened all customers for your company, sends beyond your own customers are sponsored and count toward a monthly limit.',
                'Customers who are not signed in to the app cannot receive pushes and are not listed.',
                'Notifications are deleted automatically after 5 days, also from the customers\' app. Sponsored sends and special offers are kept.',
            ],
        ],
        'advertising' => [
            'title' => 'Advertising',
            'intro' => 'Ads on the SunXFinder app and website to reach more customers.',
            'steps' => [
                'Open <strong>Customers → Advertising</strong> and press <strong>+ Request an ad</strong>.',
                'Choose the <strong>ad type</strong> (home slider, company carousel, campaign push…), where it shows (app, website or both), the start date and number of weeks.',
                'Add a link, an ad image (JPG, PNG or WEBP, up to 4 MB) and notes about what you promote, then send the request.',
                'SunXFinder confirms the price. <strong>Pay by transfer</strong>, and the ad goes live on its dates.',
                'Follow <strong>views and clicks</strong> on the Advertising page and in the <strong>Monthly report</strong>.',
            ],
            'tips' => [
                'You can cancel a request until SunXFinder has priced it.',
                'A view is one person seeing the ad on one day. Paid ads show a "Sponsored" label.',
                'This page appears only when advertising is turned on for your company.',
            ],
        ],
        'ratings' => [
            'title' => 'My ratings',
            'intro' => 'What customers said after hiring you.',
            'steps' => [
                'Open <strong>Customers → My Ratings</strong> to see your average stars, the answers to each question (price, service…) and the comments.',
                'Filter by number of stars to find what to improve.',
            ],
            'tips' => [
                'A customer can rate you once per request, after accepting your offer.',
                'Your average and the comments (with the customer\'s first name) appear on your public page and in the app.',
            ],
        ],
        'reports' => [
            'title' => 'Dashboard and reports',
            'intro' => 'How your company is doing, at a glance and for any period.',
            'steps' => [
                'The <strong>Dashboard</strong> shows requests waiting for your offer, your win rate, active projects, ratings, ads and special offers.',
                'Open <strong>Overview → Reports</strong> and choose a period (last 7 days, this month, a custom range…). Each number is compared with the previous period.',
                'Read the sections: requests and response time, offers and pricing compared with other companies, projects, customers, products, push notifications, special offers and advertising.',
                'Use <strong>Print / PDF</strong> to save the report, or export a section as a spreadsheet file.',
            ],
            'tips' => [
                '"Lost offers: your price vs chosen price" helps you see whether your prices are too high.',
                'The "Waiting for your offer" list shows open requests you have not answered yet, oldest first.',
            ],
        ],
        'help' => [
            'title' => 'Getting help',
            'intro' => 'When something does not work or you have a question.',
            'steps' => [
                'Read the "How it works" box at the top of the page you are on.',
                'Email SunXFinder support at <strong>info@sunxfinder.com</strong>, or use the "Contact us" form on the website. Include your company username and the page you were on.',
            ],
            'tips' => [
                'Approval, verification, your username and advertising prices are handled by SunXFinder, not from this panel.',
            ],
        ],
    ],
];
