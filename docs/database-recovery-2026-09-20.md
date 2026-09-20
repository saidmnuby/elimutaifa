# MariaDB recovery — 20 September 2026

## Completed

- Confirmed login HTTP 500 was caused by missing application database privileges.
- GRANT failed because the Aria `mysql.db` table had a wrong page checksum.
- Created a logical ElimuTaifa backup, gracefully shut down MariaDB, then copied
  and SHA-256-verified 263 files from the stopped data directory. The first restart
  attempt failed because the privilege table was corrupt; offline repair followed.
- Repaired only `mysql.db` using `aria_chk --recover --backup` with MariaDB stopped.
  Its four damaged records were not recovered. Other projects' old database-level
  grants cannot be inferred from the repaired table; audit them against a known
  good backup before recreating them. No guessed broad permissions were added.
- Restored SELECT, INSERT, UPDATE, DELETE for `elimutaifa_app`@`localhost` on
  `elimutaifa` only. Passwords and application configuration were not changed.
- Checked mysql.db/global_priv/tables_priv/columns_priv and all 15 ElimuTaifa tables.
- Verified login HTTP 200 and the application health check.
- Created post-repair application and privilege dumps. Restored the application
  dump into an isolated temporary database, checked all tables, then dropped only
  that temporary database. This does not constitute testing password/2FA login.

## Backup location (outside the website)

`C:\xampp\backups\mariadb-recovery-20260920-2967a4fe04f9444e87332d83fdc15eb6`

- `elimutaifa-before.sql`: application dump before repair.
- `data-before/`: stopped-server physical recovery snapshot, including damaged
  files. It is forensic/recovery material, **not** a clean system backup.
- `my.ini`: existing configuration.
- `elimutaifa-after.sql`: tested, post-repair application dump.
- `privileges-after.sql`: current privilege tables, containing sensitive hashes.

Keep this directory private. Preserve an encrypted copy on another device;
same-disk backups do not protect against disk failure. Do not publish these files.

## Remaining risk and prevention

The server log also reports InnoDB page/log sequence mismatches and missing
tablespaces in other databases. These were not repaired in this operation. They
can be consistent with mismatched/restored files, but the cause was not proven.
An OK application-table check does not prove the whole shared server is healthy.

1. Stop MySQL using XAMPP's Stop control or mysqladmin shutdown; wait for the
   mysqld process to exit before copying physical files or shutting down Windows.
2. Never mix ibdata/ib_logfile/Aria files from different backups, delete transaction
   logs as a routine fix, or copy live data folders as if they were a consistent backup.
3. Make regular logical application backups, retain dated privilege/configuration
   backups, and periodically restore-test them. No scheduled backup job was installed
   by this recovery; the backup above is a one-time recovery backup.
4. Investigate the shared InnoDB errors before further resets/reinstalls. Repair or
   rebuild the shared instance only after validating backups for every affected project.
5. Review disk health and Windows shutdown/power events to investigate recurrence;
   this recovery did not establish a disk or power fault.

No guarantee of non-recurrence is possible while shared-server warnings remain.
Do not run offline `aria_chk` while MariaDB is running. Reference:
[MariaDB aria_chk documentation](https://github.com/mariadb-corporation/mariadb-docs/blob/main/server/clients-and-utilities/aria-clients-and-utilities/aria_chk.md).
