<?php

return [
    'app_id' => env('WHATSAPP_APP_ID'),
    'app_secret' => env('WHATSAPP_APP_SECRET'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
    'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),
    'test_number' => env('WHATSAPP_TEST_NUMBER', '+15551575779'),
    'business_number' => env('WHATSAPP_BUSINESS_NUMBER'),
    
    'api_version' => 'v21.0',
    'messaging_service_url' => env('MESSAGING_SERVICE_URL', 'http://delish-messaging:3000'),
    'messaging_api_key' => env('MESSAGING_API_KEY'),
];