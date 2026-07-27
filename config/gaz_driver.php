<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Driver mobile app (Capacitor APK)
    |--------------------------------------------------------------------------
    */

    'location_ping_seconds' => (int) env('DRIVER_LOCATION_PING_SECONDS', 30),

    'app_url' => env('DRIVER_APP_URL', env('APP_URL', 'https://gaz.baitpait.space')),

    'background_notification_title' => env('DRIVER_BG_NOTIFICATION_TITLE', 'غاز اليمني'),

    'background_notification_text' => env(
        'DRIVER_BG_NOTIFICATION_TEXT',
        'مشاركة موقعك نشطة أثناء الوردية'
    ),

];
