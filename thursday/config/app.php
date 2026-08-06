<?php

return [
    'name'      => env('APP_NAME', 'School ERP'),
    'env'       => env('APP_ENV', 'production'),
    'debug'     => env('APP_DEBUG', false),
    'url'       => rtrim(env('APP_URL', 'http://localhost/school-erp/public'), '/'),
    'timezone'  => env('APP_TIMEZONE', 'Asia/Kathmandu'),
    'key'       => env('APP_KEY', ''),
    'locale'    => 'en',

    'session' => [
        'name'     => env('SESSION_NAME', 'school_erp_session'),
        'lifetime' => (int) env('SESSION_LIFETIME', 7200), // seconds
        'remember_me_days' => (int) env('REMEMBER_ME_DAYS', 30),
    ],

    'upload' => [
        'max_size' => (int) env('UPLOAD_MAX_SIZE', 5242880), // 5MB
        'allowed_image_ext' => ['jpg', 'jpeg', 'png', 'webp'],
        'allowed_document_ext' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
        'paths' => [
            'students'  => 'uploads/students/',
            'teachers'  => 'uploads/teachers/',
            'teacher_signatures' => 'uploads/teachers/signatures/',
            'teacher_documents'  => 'uploads/teacher_docs/',
            'homework'  => 'uploads/homework/',
            'documents' => 'uploads/documents/',
            'notices'   => 'uploads/notices/',
            'profile'   => 'uploads/profile/',
            'library_books'      => 'uploads/library/books/',
            'library_authors'    => 'uploads/library/authors/',
            'library_publishers' => 'uploads/library/publishers/',
        ],
    ],

    'mail' => [
        // 'smtp' (default, via PHPMailer), 'mail' (PHP's native mail()), or
        // 'log' (writes to storage/logs/mail.log only — never reports success).
        'mailer'     => env('MAIL_MAILER', 'smtp'),
        'host'       => env('MAIL_HOST', ''),
        'port'       => (int) env('MAIL_PORT', 587),
        'username'   => env('MAIL_USERNAME', ''),
        'password'   => env('MAIL_PASSWORD', ''),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@schoolerp.local'),
        'from_name'    => env('MAIL_FROM_NAME', 'School ERP'),
    ],

    'features' => [
        // Blocks login until the account's email is verified.
        'email_verification_required' => (bool) env('FEATURE_EMAIL_VERIFICATION', true),
        // Off by default per spec: security questions are a weaker fallback.
        'security_questions_enabled'  => (bool) env('FEATURE_SECURITY_QUESTIONS', false),
    ],

    'otp' => [
        'length'                  => (int) env('OTP_LENGTH', 6),
        'ttl_minutes'             => (int) env('OTP_TTL_MINUTES', 10),
        'max_attempts'            => (int) env('OTP_MAX_ATTEMPTS', 5),
        'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),
    ],

    'sms' => [
        // 'log' (default, safe no-op that writes to storage/logs/sms.log), 'twilio', or 'vonage'.
        'driver' => env('SMS_DRIVER', 'log'),
        'twilio' => [
            'sid'   => env('TWILIO_SID', ''),
            'token' => env('TWILIO_AUTH_TOKEN', ''),
            'from'  => env('TWILIO_FROM', ''),
        ],
        'vonage' => [
            'key'    => env('VONAGE_API_KEY', ''),
            'secret' => env('VONAGE_API_SECRET', ''),
            'from'   => env('VONAGE_FROM', ''),
        ],
    ],

    'pagination' => [
        'per_page' => 20,
    ],

    // Header details for the institutional fee bill (payments/institutional-bill
    // view). Override any of these in .env to match your institute without
    // touching the template.
    'institute' => [
        'council'      => env('INSTITUTE_COUNCIL', ''), // e.g. "Council for Technical Education and Vocational Training (CTEVT)"
        'name'         => env('INSTITUTE_NAME', env('APP_NAME', 'School ERP')),
        'address_line' => env('INSTITUTE_ADDRESS', ''),
        'phone'        => env('INSTITUTE_PHONE', ''),
    ],
];