<?php

// Arabic validation messages for the rules used by the website forms.
// Rules not listed here fall back to Laravel's English message.
return [
    'required' => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'email' => 'يرجى إدخال بريد إلكتروني صحيح.',
    'unique' => ':attribute مستخدم بالفعل.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'integer' => 'يجب أن يكون :attribute رقماً صحيحاً.',
    'numeric' => 'يجب أن يكون :attribute رقماً.',
    'in' => 'القيمة المختارة في :attribute غير صحيحة.',
    'regex' => 'صيغة :attribute غير صحيحة.',
    'image' => 'يجب أن يكون :attribute صورة.',
    'mimes' => 'يجب أن يكون :attribute ملفاً من نوع: :values.',
    'array' => 'يجب أن يكون :attribute قائمة.',
    'boolean' => 'قيمة :attribute غير صحيحة.',
    'date' => ':attribute ليس تاريخاً صحيحاً.',
    'digits' => 'يجب أن يتكون :attribute من :digits أرقام.',
    'size' => [
        'string' => 'يجب أن يكون طول :attribute :size أحرف بالضبط.',
        'numeric' => 'يجب أن تكون قيمة :attribute :size.',
    ],
    'min' => [
        'string' => 'يجب ألا يقل طول :attribute عن :min أحرف.',
        'numeric' => 'يجب أن تكون قيمة :attribute :min على الأقل.',
        'file' => 'يجب أن يكون حجم :attribute :min كيلوبايت على الأقل.',
        'array' => 'يجب أن يحتوي :attribute على :min عناصر على الأقل.',
    ],
    'max' => [
        'string' => 'يجب ألا يزيد طول :attribute عن :max حرفاً.',
        'numeric' => 'يجب ألا تزيد قيمة :attribute عن :max.',
        'file' => 'يجب ألا يزيد حجم :attribute عن :max كيلوبايت.',
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عناصر.',
    ],

    'custom' => [
        'password' => [
            'regex' => 'يجب أن تحتوي كلمة المرور على حرف إنجليزي كبير وحرف صغير ورقم ورمز.',
        ],
        'name' => [
            'regex' => 'يرجى كتابة اسم الشركة بالأحرف الإنجليزية والأرقام وعلامات الترقيم البسيطة فقط.',
        ],
        'username' => [
            'regex' => 'يمكن أن يحتوي اسم المستخدم على أحرف إنجليزية وأرقام و _ فقط.',
        ],
    ],

    'attributes' => [
        'name' => 'اسم الشركة',
        'username' => 'اسم المستخدم',
        'email' => 'البريد الإلكتروني',
        'phone' => 'رقم الهاتف',
        'city' => 'المدينة',
        'services' => 'الخدمات بالإنجليزية',
        'ar_services' => 'الخدمات بالعربية',
        'description' => 'نبذة الشركة بالإنجليزية',
        'ar_description' => 'نبذة الشركة بالعربية',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'social_media' => 'روابط التواصل الاجتماعي',
        'logo_path' => 'شعار الشركة',
        'year_of_exp' => 'سنوات الخبرة',
        'number_of_projects' => 'عدد المشاريع',
        'first_name' => 'الاسم الأول',
        'second_name' => 'الاسم الثاني',
        'third_name' => 'الاسم الثالث',
        'gender' => 'الجنس',
        'address_details' => 'العنوان',
    ],
];
