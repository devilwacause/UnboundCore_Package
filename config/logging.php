<?php

return [
    'unbound_file_log' => [
        'driver' => 'daily',
        'path' => storage_path('logs/unbound_files.log'),
        'level' => env('LOG_LEVEL', 'debug')
    ]
];