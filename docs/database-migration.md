# SQLite to XAMPP MariaDB migration

The application supports both PDO SQLite and PDO MySQL. Switching the shared connection covers admin accounts, content, messages, audit logs, login throttling, traffic and system events. Exam result retrieval does not change. There is no silent fallback: a failed MySQL connection must not split new data between databases.

## Configuration

`storage/database.php` is protected by `storage/.htaccess` and ignored by Git. See `storage/database.example.php`. Never publish credentials or use XAMPP root as the runtime account. Environment variables `ELIMUTAIFA_DB_DRIVER`, `HOST`, `PORT`, `NAME`, `USER`, and `PASSWORD` (all with the `ELIMUTAIFA_DB_` prefix) override the file. SQLite source location remains `ELIMUTAIFA_DB_PATH` or `storage/elimutaifa.sqlite`.

## Migration procedure

1. Start Apache and MySQL in XAMPP; confirm the server and its privilege tables are healthy.
2. Configure a new dedicated database/user name and random password in the protected file, keeping `driver=sqlite` until verification.
3. Arrange a maintenance window; create `storage/database-migration.lock`. Database-dependent requests are temporarily unavailable. Drain active requests before continuing.
4. Run `C:\xampp\php\php.exe scripts\migrate_mysql.php`. Local setup defaults to XAMPP root with no password; set `ELIMUTAIFA_MIGRATION_USER` / `ELIMUTAIFA_MIGRATION_PASSWORD` if needed. These are setup-only credentials.
5. The script refuses an existing database/user, creates a consistent timestamped SQLite backup, locks the source, imports in foreign-key order and compares every field plus row counts. Password hashes and IDs remain unchanged. Target data insertion is transactional; MariaDB schema/account operations are not transactional. Failure preserves the source and partially provisioned target for diagnosis. Do not blindly retry or delete an existing database.
6. Only after success, set `driver=mysql`, remove the maintenance lock and run `scripts\healthcheck.php` and `scripts\check_mysql.php`.
7. Test login, content create/edit/publish/archive/delete, inbox pagination/notes, account management, audit pagination, traffic and error monitoring, public announcements and all exam levels.

Isolated tests in `tests/run.php` deliberately retain temporary SQLite databases. `scripts/check_mysql.php` tests MySQL-specific queries inside a rolled-back transaction; it does not replace browser acceptance testing.

## Backups and rollback

Keep the original SQLite file and timestamped backup until acceptance. Once MariaDB receives new writes, reverting the driver alone loses access to those new records; reconcile/export them first. For ongoing backups use MariaDB logical dumps (for example `mysqldump --single-transaction` with a protected option file), store them outside the web root and test restoration in a separate database. Include `uploads/content/` in backups. phpMyAdmin can manage the new database, but must not be publicly exposed without protection.
