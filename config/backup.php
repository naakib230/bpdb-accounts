<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;

return [
    'backup' => [
        'name' => env('BACKUP_NAME', 'BPDB Pay Fixation 2026'),
        'source' => [
            'files' => [
                'include' => [storage_path('app/private/uploads')],
                'exclude' => [],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => base_path(),
            ],
            'databases' => [env('DB_CONNECTION', 'pgsql')],
        ],
        'database_dump_compressor' => null,
        'database_dump_file_timestamp_format' => null,
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => 'sql',
        'destination' => [
            'compression_method' => ZipArchive::CM_DEFLATE,
            'compression_level' => 6,
            'filename_prefix' => '',
            'disks' => [env('BACKUP_DISK', 'backups')],
        ],
        'temporary_directory' => storage_path('app/private/backup-temp'),
        'password' => null,
        'encryption' => 'default',
        'tries' => 1,
        'retry_delay' => 0,
    ],
    // Status and failures are reported through the portal and structured worker logs.
    'notifications' => [
        'notifications' => [],
        'notifiable' => Notifiable::class,
    ],
    'monitor_backups' => [[
        'name' => env('BACKUP_NAME', 'BPDB Pay Fixation 2026'),
        'disks' => [env('BACKUP_DISK', 'backups')],
        'health_checks' => [
            MaximumAgeInDays::class => 30,
        ],
    ]],
    // Cleanup is deliberately manual; creating a backup never deletes an older one.
    'cleanup' => [
        'strategy' => DefaultStrategy::class,
        'default_strategy' => [
            'keep_all_backups_for_days' => (int) env('BACKUP_KEEP_ALL_DAYS', 30),
            'keep_daily_backups_for_days' => (int) env('BACKUP_KEEP_DAILY_DAYS', 90),
            'keep_weekly_backups_for_weeks' => (int) env('BACKUP_KEEP_WEEKLY_WEEKS', 52),
            'keep_monthly_backups_for_months' => (int) env('BACKUP_KEEP_MONTHLY_MONTHS', 24),
            'keep_yearly_backups_for_years' => (int) env('BACKUP_KEEP_YEARLY_YEARS', 5),
            'delete_oldest_backups_when_using_more_megabytes_than' => null,
        ],
        'tries' => 1,
        'retry_delay' => 0,
    ],
];
