<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Configuración de servicios externos utilizados por TicketShield.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google reCAPTCHA
    |--------------------------------------------------------------------------
    */

    'recaptcha' => [
        'site_key' => env('GOOGLE_RECAPTCHA_SITE_KEY'),
        'secret_key' => env('GOOGLE_RECAPTCHA_SECRET_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Brevo - API de correo transaccional
    |--------------------------------------------------------------------------
    |
    | La clave se obtiene de las variables de entorno.
    | Nunca escribas la clave directamente en este archivo.
    |
    */

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
    ],

];
