<?php

// Admin Manual (Admin → System → Admin Manual). Same keys in en and ar.
// Steps and tips may use <strong>/<code>: the text is ours and is printed unescaped.
return [
    'title' => 'Admin Manual',
    'eyebrow' => 'HELP & GUIDE',
    'page_description' => 'How to run SunXFinder from the admin panel, step by step.',
    'print' => 'Print / Save PDF',
    'contents' => 'Contents',
    'open_page' => 'Open page',
    'back_to_top' => 'Back to top',
    'steps' => 'Step by step',
    'tips' => 'Good to know',
    'intro_title' => 'Your role as admin',
    'intro_body' => 'The admin panel controls the whole SunXFinder marketplace: you approve solar companies, look after customers, keep the catalog and calculator correct, follow every quote request and project, handle complaints, send notifications, sell ads, and keep the app, website and server healthy.',
    'flow_title' => 'The marketplace in five steps',
    'flow' => [
        ['Company joins', 'A company signs up. You check its details and approve it.'],
        ['Customer asks', 'A customer calculates their load in the app and sends a quote request.'],
        ['Companies offer', 'Approved companies send prices. You can remind or reassign.'],
        ['Customer accepts', 'A project starts and SunXFinder\'s commission becomes due.'],
        ['Install and rate', 'The company installs and hands over; the customer rates it.'],
    ],

    'sections' => [
        'start' => [
            'title' => 'Getting started',
            'intro' => 'Signing in, the menu, the top bar and the dashboard.',
            'steps' => [
                'Sign in at the admin portal with your admin username and password. Use "Forgot password?" to get a 6-digit email code if needed.',
                '<strong>The side menu</strong> has six groups: Overview, Management, App Management, Quotes, Ratings and System. The number next to a page is how many records it holds.',
                '<strong>The top bar</strong> shows alerts that need you (companies and customers waiting for approval, requests with no offers yet, ad requests, idle projects, failed or stuck background jobs), today\'s activity, and Quick actions.',
                '<strong>The Dashboard</strong> is the daily summary: new requests, offers, projects, customers, companies and ads.',
                'Most pages start with a <strong>"How it works"</strong> box. Close it once you know the page.',
            ],
            'tips' => [
                'Start each day with the top bar alerts, then check Pending complaints.',
                'Everything you change is recorded in Activity Logs with your name.',
            ],
        ],
        'companies' => [
            'title' => 'Solar companies',
            'intro' => 'Approve, verify and manage the companies on SunXFinder.',
            'steps' => [
                'Open <strong>Management → Solar companies</strong>. Filter by status (Pending, Approved, Rejected) or search by name.',
                'Open a company with <strong>Company details</strong> to see its profile, customers, quote activity, offers, projects and ratings.',
                'Press <strong>Update company</strong> to edit it. Under <strong>Account Settings</strong> set the <strong>Approval Status</strong>, <strong>Company Verified</strong> and <strong>Account Active</strong>.',
                'Set the <strong>SunXFinder Commission (%)</strong>: the share of each offer price that is due when a customer accepts the offer. Use 0 for none.',
                'Use <strong>Email in the App</strong> and <strong>Phone in the App</strong> (Show / Hide) to choose whether customers see the company\'s email and phone on its page in the app. Hiding the phone also hides WhatsApp. The directory marks hidden ones with "Hidden in app".',
                'Use <strong>Log in as company</strong> to see the company panel exactly as the company sees it, for support.',
                'Add a company yourself with <strong>+ Add New Company</strong>, or delete one with the delete button.',
            ],
            'tips' => [
                'Only approved and active companies can sign in, receive requests and appear in the app and on the website.',
                'Before approving, check the license number, phone, email and description.',
                'A commission change applies to offers the company sends or edits from then on. Customers never see the commission.',
                'Companies can read how to use their panel in their own Company Manual (System menu of the company panel).',
            ],
        ],
        'customers' => [
            'title' => 'Customers',
            'intro' => 'The customer accounts registered in the app and on the website.',
            'steps' => [
                'Open <strong>Management → Customers</strong>. Search by name, phone or city, and filter by status.',
                'Press <strong>Edit customer</strong> to change their details or <strong>Account Status</strong>. The <strong>App access</strong> box shows the last login and whether they are signed in.',
                'Use the <strong>App access</strong> menu in the list (Active / Stopped) to stop a customer using the app, with a message in English and Arabic.',
                'Press <strong>Send offer or news</strong> to send that customer a notification.',
                '<strong>Force logout all customers</strong> signs every customer out of the app. Use it only after a security issue or a major app change.',
            ],
            'tips' => [
                'After a force logout, customers get no push notifications until they sign in again.',
                'Deleted customers are kept in Archives.',
            ],
        ],
        'catalog' => [
            'title' => 'Suppliers and products',
            'intro' => 'The brands and products in the SunXFinder catalog.',
            'steps' => [
                'Open <strong>Management → Supplier</strong> to add the brands and manufacturers (for example a panel or inverter maker).',
                'Open <strong>Management → Products</strong> to add products: supplier, type (solar panel, inverter, battery…), name, model and specifications.',
                'Keep names and specifications accurate: companies pick from this catalog for their own product lists and package offers.',
            ],
            'tips' => [
                'Add the supplier first; a product needs one.',
            ],
        ],
        'calculator' => [
            'title' => 'Calculator groups',
            'intro' => 'The appliance list customers use in the app\'s load calculator.',
            'steps' => [
                'Open <strong>App Management → Calculator Groups</strong>. Each group is a category, such as Cooling or Kitchen.',
                'Edit a group to set its name, icon and display order, then add its <strong>appliances</strong> with the English and Arabic name and the <strong>power consumption in watts</strong>.',
                'Turn an appliance off with its status instead of deleting it if you may need it again.',
            ],
            'tips' => [
                'The watts you enter decide the customer\'s load and the system size companies quote for. Double-check them.',
            ],
        ],
        'app_home' => [
            'title' => 'Company carousel and app slider',
            'intro' => 'What customers see on the Home screen of the app and on the website.',
            'steps' => [
                '<strong>Company Carousel</strong>: choose which companies are featured, where (app, website or both), their order, how many seconds each one shows, and optional start and end dates.',
                '<strong>App Slider</strong>: add banners for ads and announcements. Pick the placement (top, inside Featured Companies, or bottom), upload a wide image (about 1280 × 640 px), set the display time, and optionally a link and dates.',
                'A slide is "Live" only while it is active and inside its dates. The app shows up to 10 live slides per placement.',
            ],
            'tips' => [
                'Changes reach the app within a minute.',
                'Paid ads are linked to their sale in Ad Sales so their views and clicks are counted.',
            ],
        ],
        'ad_sales' => [
            'title' => 'Ad sales',
            'intro' => 'The ads companies request or buy, their payment and results.',
            'steps' => [
                'Open <strong>App Management → Ad Sales</strong>. New requests from companies arrive here as "Requested".',
                '<strong>Price it</strong>: open the request, set the amount and dates, and mark it <strong>Awaiting payment</strong>.',
                '<strong>Record payment</strong>: when the transfer arrives, mark it <strong>Paid</strong> and add the receipt reference.',
                '<strong>Put it live</strong>: create the slide or carousel entry and link it to the sale.',
                'Use the <strong>monthly report</strong> for views, clicks and money received.',
            ],
            'tips' => [
                'Marking a sale Paid does not turn the ad on. You control that in App Slider or Company Carousel.',
                'Which companies see the Advertising page is set in System → Settings → Company Advertising.',
            ],
        ],
        'app_versions' => [
            'title' => 'App versions',
            'intro' => 'Force customers on old app versions to update.',
            'steps' => [
                'Open <strong>App Management → App Versions</strong> and edit the Android or iOS entry.',
                'Set the <strong>Minimum Version</strong>. Customers on an older version are asked to update.',
                'Enter the <strong>App Store / Play Store link</strong> and, if you want, an update message in English and Arabic.',
            ],
            'tips' => [
                'Raise the minimum version only after the new version is live in the store.',
            ],
        ],
        'quotes' => [
            'title' => 'Quote requests',
            'intro' => 'Every request customers send, and how far each one has gone.',
            'steps' => [
                'Open <strong>Quotes → Quote requests</strong>. Each request is open to all companies or sent to one company.',
                'Watch <strong>"Waiting for offers"</strong>: those customers have not heard from any company yet.',
                'Open a request to see the progress tracker, the <strong>offers received</strong> (cheapest first), the project, and the customer\'s energy needs.',
                'Under <strong>Admin actions</strong>: <strong>Remind companies</strong> by email, <strong>Reassign</strong> the request to another company, or <strong>Cancel request</strong>.',
                'In the offers table, use <strong>Change commission</strong> to set a different commission (percentage or fixed amount) for one offer.',
                'Use <strong>Print / Save PDF</strong> to share a request.',
            ],
            'tips' => [
                'Commission is due only when the customer accepts the offer; the list shows it for accepted offers.',
                'Reminders can be sent again only after a waiting time, so companies are not flooded.',
            ],
        ],
        'projects' => [
            'title' => 'Projects',
            'intro' => 'Installations created when a customer accepts an offer. Companies update them; you follow up.',
            'steps' => [
                'Open <strong>Quotes → Projects</strong>. Status: <strong>Accepted</strong> (not started), <strong>In progress</strong>, <strong>Completed</strong>.',
                'Use <strong>Stalled projects</strong> to find projects with no update for several days.',
                'Open a project to see milestones, every company update and the contact details of the company and customer, then follow up.',
                'Use <strong>Open quote request</strong> to reassign the company or act on the original request.',
            ],
            'tips' => [
                'Updates marked "Internal note" are hidden from the customer.',
            ],
        ],
        'complaints' => [
            'title' => 'Complaints',
            'intro' => 'Problems customers report about a company from the app.',
            'steps' => [
                'Open <strong>Quotes → Complaints</strong> and start with <strong>Pending</strong> ones.',
                'Open a complaint, read the report and photos, and set the status to <strong>In progress</strong>.',
                'Press <strong>Notify company</strong> to email the complaint to the company. The customer\'s name, email and phone are removed from that email.',
                'Write a <strong>reply to the customer</strong> and send it as an in-app notification or by email.',
                'Close it as <strong>Resolved</strong> or <strong>Rejected</strong> once the customer has been answered.',
            ],
            'tips' => [
                'Whenever you save with text in the reply box, that text is sent to the customer. Leave it empty to change only the status.',
            ],
        ],
        'notifications' => [
            'title' => 'Push notifications',
            'intro' => 'Messages to customers\' phones and their in-app inbox.',
            'steps' => [
                'Open <strong>Quotes → Push Notifications</strong> to see the history, who received each one and who read it.',
                '<strong>+ Quick notification</strong>: a short text announcement in English and Arabic, to all customers, one city or chosen customers.',
                '<strong>📣 New customer campaign</strong>: a rich message with an image, a company offer and a button. The preview shows the push and the in-app card.',
                '<strong>⏱ Automatic notifications</strong>: a schedule that sends a list of messages one by one on the weekdays (Saturday to Friday), every N days, at a set time. Use <strong>Send next message now</strong> to test it.',
                'Delete a notification to remove it from the customers\' inbox.',
            ],
            'tips' => [
                'Only customers signed in to the app can receive notifications. Sending cannot be undone.',
                'Plain notifications are deleted automatically after 5 days (admin and company ones). Campaigns, sponsored sends and package offers are kept.',
                'Automatic sends appear in System → Background Jobs as Done, Partly delivered or Failed.',
            ],
        ],
        'ratings' => [
            'title' => 'Ratings',
            'intro' => 'How customers rated the companies they hired.',
            'steps' => [
                'Open <strong>Ratings → Ratings</strong> to read the stars, answers and comments.',
                '<strong>Hide</strong> a rating to remove it from the public page and the average without deleting it. <strong>Delete</strong> removes it completely, and the customer can rate again.',
                'In <strong>Rating Questions</strong>, set the questions customers answer (such as Price or Service) on the 1–4 scale.',
            ],
            'tips' => [
                'Hide abusive comments rather than deleting them, so you keep a record.',
            ],
        ],
        'reports' => [
            'title' => 'Reports',
            'intro' => 'Activity and health of the marketplace for any period.',
            'steps' => [
                'Open <strong>Overview → Reports</strong> and choose a period. Numbers are compared with the previous period.',
                'Read the sections: quote requests, offers, projects, customers, companies, catalog, notifications, advertising, ratings and complaints.',
                'Use <strong>Print / PDF</strong> or export a section to share it.',
            ],
            'tips' => [],
        ],
        'send_email' => [
            'title' => 'Send email',
            'intro' => 'Write to solar companies, for example to invite them to join.',
            'steps' => [
                'Open <strong>System → Send Email</strong>. Type one or more addresses separated by commas, or pick a registered company.',
                'Write the subject and message, then send. Each recipient gets a separate email, and registered companies are greeted by name.',
                'You can also open a WhatsApp chat with the company number (include the country code).',
                'The <strong>Sent email history</strong> lets you resend or delete past emails.',
            ],
            'tips' => [],
        ],
        'settings' => [
            'title' => 'Settings',
            'intro' => 'App rules, app Home, maintenance and the API the app uses.',
            'steps' => [
                '<strong>Mobile app status</strong>: take the app offline for all customers (for example during an update) with a message in English and Arabic.',
                '<strong>Company Advertising</strong>: choose which companies see the Advertising page.',
                '<strong>Quote requests</strong>: how many open requests each customer may have, with exceptions for single customers.',
                '<strong>Company notifications</strong>: by default a company can notify only its own customers. <strong>Open all customers</strong> for a company and set its monthly limit.',
                '<strong>Website maintenance mode</strong>: close the public website with a maintenance page. Admins still see the site.',
                '<strong>App Home</strong>: show or hide Home sections and edit their titles.',
                '<strong>API selector</strong>: choose which API server the app uses, and turn an API offline.',
            ],
            'tips' => [
                'Changing the active API\'s address signs every customer out of the app.',
                'Turn maintenance and app-offline off again as soon as the work is done.',
            ],
        ],
        'legal' => [
            'title' => 'Privacy, Terms and acceptances',
            'intro' => 'The legal text on the website and who accepted it in the app.',
            'steps' => [
                'Open <strong>System → Privacy &amp; Terms</strong>. Each page has a header, numbered sections and a contact box, in English and Arabic.',
                'Edit, add, hide or reorder sections with the arrows. Changes appear on the website when you save.',
                'Open <strong>System → Terms Acceptance</strong> to see which customers accepted which version, and when. Export CSV for the full record.',
            ],
            'tips' => [
                'Never type section numbers in titles; they update by themselves.',
                'When the Terms "Last updated" date changes, customers accept the new version at their next sign-in.',
            ],
        ],
        'system' => [
            'title' => 'Logs, background jobs, backups and archives',
            'intro' => 'Keeping the system healthy and recovering data.',
            'steps' => [
                '<strong>Activity Logs</strong>: every sign-in, change and deletion by admins, companies and customers, with old → new values.',
                '<strong>Background Jobs</strong>: emails and pushes waiting or failed. Fix the cause shown in the hint, then <strong>Retry</strong>. The Automatic notifications section shows each scheduled send as Done, Partly delivered or Failed.',
                '<strong>Database Backups</strong>: create a backup before big changes, download it and keep a copy off the server.',
                '<strong>Archives</strong>: records deleted in the website or app are kept here, by type, source and date.',
            ],
            'tips' => [
                'A red alert on Background Jobs ("jobs waiting over 10 minutes" or "Automatic notifications are not being sent") means a server cron job stopped. See "Server cron jobs" below.',
                'Activity logs are kept for 365 days and removed automatically.',
            ],
        ],
        'server' => [
            'title' => 'Server cron jobs',
            'intro' => 'Two cron jobs must run every minute on the hosting (cPanel → Cron Jobs).',
            'steps' => [
                '<strong>Queue worker</strong> sends emails and push notifications: <code>php artisan queue:work --stop-when-empty --tries=3 --max-time=50</code>',
                '<strong>Scheduler</strong> sends automatic notifications and runs the daily clean-ups: <code>php artisan schedule:run</code> (no random delay).',
                'Both run from the project folder with PHP 8.2 or newer.',
            ],
            'tips' => [
                'If emails or pushes stop, check Background Jobs first: it tells you which of the two is not running.',
                'After deploying new code, run <code>php artisan migrate --force</code> once.',
            ],
        ],
    ],
];
