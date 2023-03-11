<?php

return [
    'SOURCE' => env('GLIDE_SOURCE', storage_path('')),
    'CACHE' => env('GLIDE_CACHE', storage_path('app')),
    'DRIVER' => env('GLIDE_DRIVER', 'imagick'),
    'BASE_PATH' => env('GLIDE_BASE_PATH', 'img'),
    'BASE_URL' => env('GLIDE_BASE_URL', config('app.url')),
    'DEFAULT_PARAMS' => [
        'fm' => 'jpg',
        'q' => '80',
        'fit' => 'max'
    ],
    'DEFAULT_THUMBNAIL' => [
        'q' => '80',
        'w' => '200',
    ],
    'PRESETS' => [],
];