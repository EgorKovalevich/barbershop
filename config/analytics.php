<?php

return [
    'google' => [
        'tag_id' => env('GOOGLE_ANALYTICS_TAG_ID'),
    ],

    'yandex' => [
        'counter_id' => env('YANDEX_METRIKA_COUNTER_ID'),
    ],

    'dashboard' => [
        'traffic_baseline' => [
            'daily_sessions' => (int) env('ANALYTICS_BASE_DAILY_SESSIONS', 260),
            'unique_ratio' => (float) env('ANALYTICS_UNIQUE_RATIO', 0.76),
            'avg_session_duration' => (int) env('ANALYTICS_AVG_SESSION_DURATION', 240),
        ],
        'popular_pages' => [
            ['path' => '/appointments', 'title' => 'Страница записи', 'share' => 0.38],
            ['path' => '/prices', 'title' => 'Цены', 'share' => 0.32],
            ['path' => '/contacts', 'title' => 'Контакты', 'share' => 0.18],
        ],
        'device_breakdown' => [
            ['label' => 'Мобильные устройства', 'share' => 0.64],
            ['label' => 'ПК и ноутбуки', 'share' => 0.31],
            ['label' => 'Планшеты', 'share' => 0.05],
        ],
        'geo' => [
            ['label' => 'Минск', 'share' => 0.46],
            ['label' => 'Гомель', 'share' => 0.18],
            ['label' => 'Гродно', 'share' => 0.12],
            ['label' => 'Брест', 'share' => 0.08],
            ['label' => 'Другие регионы', 'share' => 0.16],
        ],
    ],

    'technical' => [
        'uptime' => (float) env('ANALYTICS_UPTIME', 99.92),
        'avg_response_time' => (int) env('ANALYTICS_AVG_RESPONSE_TIME', 420),
        'avg_page_speed' => (float) env('ANALYTICS_PAGE_SPEED', 2.1),
        'bounce_rate' => (float) env('ANALYTICS_BOUNCE_RATE', 37.5),
        'error_rate' => (float) env('ANALYTICS_ERROR_RATE', 0.4),
        'api_error_budget' => (float) env('ANALYTICS_ERROR_BUDGET', 0.05),
    ],
];
