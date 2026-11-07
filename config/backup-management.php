<?php

return [
    'uploads_path' => storage_path('app/private/uploads'),
    'uploads_archive_prefix' => 'storage/app/private/uploads/',
    'restore_max_bytes' => 20 * 1024 * 1024 * 1024,
    'restore_max_entries' => 100000,
    'cache_store' => env('BACKUP_CACHE_STORE', 'redis'),
    'queue_connection' => 'backups',
    'queue' => 'backups',
    'lock_seconds' => 21600,
    'status_seconds' => 86400,
];
