# Email setup in Account

Open **Admin → Account**. There is no separate Email notifications page; old links redirect to Account.

1. Each owner saves **Your notification email** in **Email and notifications**. This changes only that account. Active owners also receive notifications about other admins. A blank address stops delivery to that owner.
2. The **System email sender** is shared by the website and managed only by the pinned main owner, **Said_Mnuby**. Other owners and admins see only their personal notification email. The main owner opens **Set up system sender / Change system sender** and chooses **Gmail** or **Brevo**. System notification settings are also editable only by the main owner; other owners can view reports and checks.
3. Enter the email address. For Gmail, enter the 16-character Google App Password after enabling Google 2-Step Verification. For Brevo, verify the sender in Brevo and enter an API key from SMTP & API → API Keys, not an SMTP key.
4. Click **Test connection**. A real test is sent to your previously saved notification email. The shared connection is saved only after the provider accepts the test. Changing the sender does not change owners’ notification addresses. The page shows **Setup successful**. Provider acceptance does not guarantee inbox placement; check Spam too.

A failed test keeps the previous working connection. A blank code reuses the saved credential only when the provider and sender have not changed. Codes are never prefilled or shown again. **Pause all system emails** stops new notifications and worker delivery for everyone. Test connection enables them again after a successful test.

Gmail is verified live. Brevo routing and failed setup behavior are covered by isolated tests; a live Brevo connection requires the owner's verified sender and API key.

## Automatic delivery

Completed admin sign-ins (after 2FA) and new admin accounts queue notifications. Messages include name, username, role and Tanzania time. They never contain passwords, sign-in codes or raw IP addresses. Existing admins can save their address in Account; new accounts have an optional email field.

The Windows task **ElimuTaifa Admin Email** runs the worker hidden every minute while the configured Windows user is signed in, including on battery power. Keep the computer running for local delivery. On hosting, configure cron separately to run `php scripts/send_admin_emails.php` every minute. No manual code editing is needed for provider setup. Existing pending emails are processed before background checks, and check failures do not prevent delivery.

## Storage and deployment

## Notifications & Checks

The main owner manages sign-in, new-account, critical, daily and Friday emails in **Notifications → Notifications and schedule**. The page contains only schedule and notification preferences; delivery history, check results and worker explanations are not displayed. All channels and automatic exam source checks are enabled initially. Reports default to 07:00 East Africa Time. The daily report covers yesterday; Friday covers the seven complete days before Friday. A late worker catches up once, without repeating a report. Active owners must have an account email.

Source checks rotate through published exam cycles, one every five minutes, at most once per cycle per day. They verify a directory and sample school without altering mappings or publication status. Three consecutive failed checks alert the owner. Fatal errors alert immediately through the minute worker; error groups with at least three occurrences and five failed sign-ins within 15 minutes also trigger alerts. Similar event alerts are limited to one per hour; source alerts to one per day.

Checks and delivery tracking continue in the background. Optional development progress is explicitly an owner assessment with a recorded date. Traffic counts are anonymous estimates; successful visitors are distinct visitors with parsed results, not search attempts. Uptime, latency, load capacity and SEO reach are not measured. Critical monitoring depends on the worker and database being available; complete server failure requires an external uptime monitor.

Checks: `php tests/notifications.php`. Tests use an isolated database and send no real email.

Sender credentials are encrypted with AES-256-GCM in `app_settings`, using the protected server encryption key already used for account security. Keep the database and key backed up securely; a deployment needs both. Previously configured Gmail credentials were migrated, and the plain-text value removed from `storage/email.php`. That private file is no longer used for normal setup.

The worker processes up to 20 pending messages per run and keeps delivery status internally. Failed messages are not automatically retried because a timeout can happen after provider acceptance. Interrupted processing needs investigation before resending. Completed records are cleaned after 14 days when the enabled worker runs. Disabling notifications retains pending messages until re-enabled.

Deploy Composer dependencies with `composer install --no-dev`. Gmail uses verified STARTTLS; Brevo uses the HTTPS transactional-email API.

Checks: `php tests/admin_email.php` and `php tests/run.php`. Fixture tests send no real email.

References: [Google App Passwords](https://support.google.com/accounts/answer/185833), [Brevo transactional email API](https://developers.brevo.com/docs/send-a-transactional-email).
