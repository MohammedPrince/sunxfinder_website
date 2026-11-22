<?php

// "How it works" box at the top of company pages (company/partials/guide.blade.php). Same keys in en and ar.
// Pages with their own guide text in company/messages.php (quote requests, projects, invoices,
// special offers list, advertising list, ratings) are not repeated here.
// Steps may use <strong>: the text is ours and is printed unescaped.
return [
    'dashboard' => [
        'title' => 'How the dashboard works',
        'intro' => 'One page that shows what is happening in your company today and what needs your action.',
        'steps' => [
            '<strong>Start with "Awaiting your offer"</strong>: these are open customer requests you have not priced yet. Answer them first.',
            '<strong>Read the cards</strong>: offers you sent and your win rate, active projects, listed products, your rating, customers won, ads and special offers.',
            '<strong>Check the charts</strong>: new requests and your offers over the last 7 days, and how customers answered your offers.',
            '<strong>Use "Latest quote requests"</strong> at the bottom to open a request and send an offer directly.',
        ],
        'note' => 'For any period and more detail, open Overview → Reports. The bell in the top bar always lists the tasks waiting for you.',
    ],

    'profile' => [
        'title' => 'How your company profile works',
        'intro' => 'This is the information customers see on your public page and in the app. SunXFinder uses it to approve your company.',
        'steps' => [
            '<strong>Fill in the required details</strong>: company name, email, phone, city and license number.',
            '<strong>Upload your logo</strong> (JPG, PNG or WEBP, up to 2 MB) and add your years of experience, number of projects and social media links (one per line).',
            '<strong>Write your description in English and Arabic</strong>: your services, experience and the areas you serve.',
            '<strong>Press Save</strong>. The status box shows: incomplete, waiting for approval, approved or rejected.',
            '<strong>To change your password</strong>, type a new one and confirm it. Leave it empty to keep the current one.',
        ],
        'note' => 'Customers can find you only after SunXFinder approves your company. To change your username, contact SunXFinder support.',
    ],

    'products' => [
        'title' => 'How My Products works',
        'intro' => 'Your catalog: the panels, inverters, batteries and other products you sell.',
        'steps' => [
            '<strong>Press + Add Product</strong> and choose the supplier / brand and the category.',
            '<strong>Enter the details</strong>: product name, model number, a short description and the specifications (for example Capacity = 5 kW).',
            '<strong>Choose who sees it</strong>: tick "Show on company profile" to show it to customers, or leave it hidden.',
            '<strong>Keep the list up to date</strong> with the edit and delete buttons.',
        ],
        'note' => 'Your products are also used to build invoices and special offers quickly. Hide a product you no longer sell.',
    ],

    'product_form' => [
        'title' => 'How to add a product',
        'intro' => 'Each product you save can be shown on your company page and picked when you build an invoice or a special offer.',
        'steps' => [
            '<strong>Choose the supplier / brand and the category</strong>.',
            '<strong>Enter the product name and model number</strong>, then a short description.',
            '<strong>Add specifications</strong>, one per line, such as Wattage = 550 W or Voltage = 48 V. Press "Add specification" for more.',
            '<strong>Tick "Show on company profile"</strong> if customers should see it, then save.',
        ],
        'note' => 'Clear names and correct specifications help customers trust your offers.',
    ],

    'customers' => [
        'title' => 'How Company Customers works',
        'intro' => 'These are the customers who chose your company by accepting one of your offers.',
        'steps' => [
            '<strong>A customer appears here</strong> automatically once they accept your offer. You do not add customers yourself.',
            '<strong>Search</strong> by name, phone or city, and open the customer\'s latest project.',
            '<strong>Press Notify</strong> to send one customer a push notification, or <strong>Notify all my customers</strong> to message everyone.',
        ],
        'note' => 'Returning customers (2 or more projects) are counted at the top of this page and in Reports.',
    ],

    'notifications' => [
        'title' => 'How push notifications work',
        'intro' => 'Messages you send to your customers\' phones through the SunXFinder app.',
        'steps' => [
            '<strong>Press + Send Notification</strong> to write a new message.',
            '<strong>Follow each message</strong> in the list: how many customers received it and how many read it.',
            '<strong>Open a notification</strong> to see the full text and who read it.',
        ],
        'note' => 'You can message only your own customers. Notifications are deleted automatically after 5 days, also from the customers\' app.',
    ],

    'notification_form' => [
        'title' => 'How to send a notification',
        'intro' => 'The message arrives as a push on the customer\'s phone and stays in their app inbox.',
        'steps' => [
            '<strong>Choose the notification type</strong> (announcement, promotion, maintenance…).',
            '<strong>Select the customers</strong>. Hold Ctrl (Windows) or Command (Mac) to pick several, or use "Select all customers".',
            '<strong>Write the title and message in English and Arabic</strong>. Each customer gets the language of their app.',
            '<strong>Press send</strong>. The message goes out immediately and cannot be changed afterwards.',
        ],
        'note' => 'Customers who are not signed in to the app cannot receive pushes and are not listed.',
    ],

    'notification_show' => [
        'title' => 'How to read this page',
        'intro' => 'The result of one notification you sent.',
        'steps' => [
            '<strong>The numbers at the top</strong> show how many customers received it, and how many read it or have not read it yet.',
            '<strong>The content boxes</strong> show the English and Arabic text exactly as customers received it.',
            '<strong>The recipients list</strong> shows each customer, their app language, and when they read the message.',
        ],
        'note' => 'A customer with no signed-in device does not get the push on their phone.',
    ],

    'reports' => [
        'title' => 'How reports work',
        'intro' => 'Your company\'s results for any period, compared with the period before it.',
        'steps' => [
            '<strong>Choose a period</strong>: last 7 days, this month or a custom range, then apply.',
            '<strong>Read each section</strong>: requests and response time, offers and pricing compared with other companies, projects, customers, products, notifications, special offers and advertising.',
            '<strong>Watch the arrows</strong>: each number is compared with the previous period, so you see if you are improving.',
            '<strong>Save it</strong>: use Print / PDF for the whole report, or export a section as a spreadsheet file.',
        ],
        'note' => '"Lost offers: your price vs chosen price" shows whether your prices are too high. "Waiting for your offer" lists the requests you have not answered, oldest first.',
    ],

    'package_offer_form' => [
        'title' => 'How to build a special offer',
        'intro' => 'A special offer is a ready system at one price that you send to customers yourself, without waiting for a request.',
        'steps' => [
            '<strong>Write the title and description</strong> in English and Arabic.',
            '<strong>Set the maximum load</strong> (kW) the system can power, and optionally a "Valid until" date.',
            '<strong>Add the system items</strong> from your products, or type custom lines such as installation. The price the customer pays is shown at the bottom.',
            '<strong>Add an image</strong>: customers see it in the push and inside the offer.',
            '<strong>Save</strong>. The offer is not sent yet: you send it to customers from the next page.',
        ],
        'note' => 'You can edit an offer only until you send it the first time. After that, close it and make a copy to change it.',
    ],

    'package_offer_show' => [
        'title' => 'How to send and follow this offer',
        'intro' => 'From this page you send the offer to customers and see who accepted it.',
        'steps' => [
            '<strong>Check "What customers see"</strong>: the items, the maximum load and the price, before you send.',
            '<strong>Send to customers</strong>: select the customers, write a short push message in English and Arabic, then press send.',
            '<strong>Follow the answers</strong>: the numbers at the top count sent, accepted and declined, and "Customer answers" lists each customer.',
            '<strong>Start the project</strong> for each customer who accepted. It is waiting in Active Projects.',
            '<strong>Close the offer</strong> when you no longer want new customers to accept it. Use "Make a copy" to send a changed version.',
        ],
        'note' => 'Accepting is final on both sides. An offer that was never sent can be deleted; a sent offer can only be closed.',
    ],

    'ad_form' => [
        'title' => 'How to request an ad',
        'intro' => 'You send a request, SunXFinder confirms the price, and the ad goes live after payment.',
        'steps' => [
            '<strong>Choose the start date</strong> and the number of weeks the ad should run.',
            '<strong>Add a link</strong> (optional) that opens when a customer taps the ad.',
            '<strong>Write a note</strong> about what you want to promote, then send the request.',
            '<strong>Wait for the price</strong>, pay by transfer, and the ad runs on its dates.',
        ],
        'note' => 'SunXFinder prepares the ad image for you. You can cancel a request until it has been priced.',
    ],

    'ad_report' => [
        'title' => 'How the monthly ad report works',
        'intro' => 'The results of your ads for one month.',
        'steps' => [
            '<strong>Choose the month</strong> and press apply.',
            '<strong>Read the totals</strong>: views, clicks and the click rate (clicks out of views).',
            '<strong>Compare your ads</strong> in the table: each ad with its app views, website views and clicks.',
        ],
        'note' => 'A view is one person seeing the ad on one day, so the same person counts once per day.',
    ],

    'invoice' => [
        'title' => 'How this invoice works',
        'intro' => 'This is the invoice the customer sees in the app with your offer.',
        'steps' => [
            '<strong>Check it</strong>: items, quantities, prices, discount, tax, shipping and the total.',
            '<strong>Print it or download the PDF</strong> with the buttons at the top.',
            '<strong>To change it</strong>, go back to the request and edit your offer. The invoice updates with it.',
        ],
        'note' => 'Once the customer accepts your offer, the invoice is locked and cannot be edited.',
    ],
];
