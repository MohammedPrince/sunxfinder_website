<?php

// Merged on top of Laravel's built-in English messages: only clearer wording for
// the rules that otherwise say "format is invalid", plus friendly field names.
return [
    'custom' => [
        'password' => [
            'regex' => 'The password must include an uppercase letter, a lowercase letter, a number and a symbol.',
        ],
        'name' => [
            'regex' => 'Please write the company name in English letters, numbers and basic punctuation only.',
        ],
        'username' => [
            'regex' => 'The username may only contain English letters, numbers and _.',
        ],
    ],

    'attributes' => [
        'ar_services' => 'Arabic services',
        'description' => 'company profile (English)',
        'ar_description' => 'company profile (Arabic)',
        'year_of_exp' => 'years of experience',
        'number_of_projects' => 'number of projects',
        'address_details' => 'address',
        'social_media' => 'social media links',
    ],
];
