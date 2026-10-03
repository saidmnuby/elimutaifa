<?php
declare(strict_types=1);

const ET_ADMIN_MIGRATION_REQUIRED_TABLES = [
    'admin_users', 'content_items', 'submissions', 'login_attempts', 'audit_logs',
    'traffic_daily', 'traffic_unique_visitors', 'traffic_recent', 'system_events', 'app_settings',
];

const ET_ADMIN_MIGRATION_OPTIONAL_TABLES = [
    'admin_two_factor', 'admin_recovery_codes', 'placement_items', 'form_one_cycles', 'form_five_cycles',
];

function et_admin_migration_target_tables(): array
{
    return array_merge(ET_ADMIN_MIGRATION_REQUIRED_TABLES, ET_ADMIN_MIGRATION_OPTIONAL_TABLES);
}

/** Reject unknown source tables rather than silently omitting potentially important data. */
function et_admin_migration_plan(array $sourceTableNames): array
{
    $sourceTables = array_values(array_unique(array_filter($sourceTableNames, static fn(mixed $name): bool => is_string($name) && $name !== '')));
    $targetTables = et_admin_migration_target_tables();
    $missingRequired = array_values(array_diff(ET_ADMIN_MIGRATION_REQUIRED_TABLES, $sourceTables));
    $unrecognized = array_values(array_diff($sourceTables, $targetTables));
    if ($missingRequired !== [] || $unrecognized !== []) {
        throw new RuntimeException('Unsupported migration schema. Missing required tables: '
            . implode(', ', $missingRequired) . '; unrecognized tables: ' . implode(', ', $unrecognized));
    }

    return [
        'target_tables' => $targetTables,
        'source_tables' => array_values(array_filter($targetTables, static fn(string $table): bool => in_array($table, $sourceTables, true))),
    ];
}
