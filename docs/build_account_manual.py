from pathlib import Path

base=Path(__file__).parent
exec((base/'build_sponsor_manual.py').read_text(encoding='utf-8').split('# Cover')[0])
OUT=base/'ElimuTaifa_Owner_Admin_Account_Management_Guide.docx'
d.core_properties.title='ElimuTaifa Owner and Admin Account Management Guide'
d.core_properties.subject='Account access authentication recovery and notifications'
s.header.paragraphs[0].text='ELIMUTAIFA  |  Account Management'
s.footer.paragraphs[0].runs[0].text='Owner and Admin guide  •  '

p('ELIMUTAIFA').runs[0].bold=True
d.add_paragraph('Owner and Admin Account Management Guide','Title')
d.add_paragraph('Sign in security recovery and email notifications','Subtitle')
p('Version 1.0  |  4 October 2026')
h('One guide with clear role boundaries')
p('Use this guide to create staff accounts, sign in, set up two-step authentication, save recovery codes, change your password and manage email. Shared account tasks are explained first. Owner-only user management and Main Owner-only system settings are explained separately.')
p('The guide uses the English field and button names shown in ElimuTaifa. It describes the current implementation; it does not claim that any account, email connection or recovery test was performed while creating this document.')
h('Three roles to understand')
bullets(['Admin manages their own sign-in security, password and notification email.','Owner has the same personal account controls and can add and manage staff accounts through Admins.','Main Owner is the designated Owner who can manage the shared system sender and notification schedule. Creating another Owner does not transfer Main Owner authority.'])
p('No passwords, setup keys, recovery codes, API keys or personal account credentials are included in this guide.')

page('Contents and permissions')
table(['Part','Sections'],[('A Shared account tasks','1 Sign in; 2 Authentication; 3 Recovery codes; 4 Password; 5 Personal email'),('B Owner only','6 Sign up and adding users; 7 Roles and account access'),('C Main Owner only','8 System email sender; 9 Notifications and schedule'),('D Help and handover','10 Troubleshooting; 11 Checklists')],[1.8,5.1])
h('Permissions at a glance')
table(['Task','Admin','Owner','Main Owner'],[
('Sign in and use own 2FA','Yes','Yes','Yes'),('Change own password and email','Yes','Yes','Yes'),('Create own recovery codes','Yes','Yes','Yes'),('Add Admin or Owner accounts','No','Yes','Yes'),('Manage Admin role and status','No','Yes','Yes'),('View notification settings','No','Yes','Yes'),('Change sender and global schedule','No','No','Yes')],[3.5,1.0,1.1,1.3])
p('Owner rows are protected in the current Admins screen: it does not show the ordinary Update and Remove controls for those rows. Owners cannot use that screen to reset another person’s password or 2FA.')
h('Before you begin')
bullets(['Use your own account and an authenticator app.','Keep your phone clock set automatically.','Use the live website over HTTPS. Localhost is for development.','Keep a private place for recovery codes, separate from your phone.'])

page('Part A Shared account tasks')
d.add_heading('1 Sign in',2)
p('Applies to Admin and Owner accounts. You need an active account, a username, a password and your second authentication factor once enrolled.')
steps(['Open the ElimuTaifa admin sign-in page on your website.','Enter Username and Password, then select Sign in.','If 2FA is enabled, the Confirm sign-in page opens.','Enter your current authenticator code or an unused recovery code.','Select Confirm to complete sign-in and open the dashboard.'])
p('Sign in uses Username, not your notification email. Recovery codes replace the second factor only; you still need your password.')
h('First sign in')
p('An account without 2FA must complete authenticator setup before using the other admin sections. A restricted local development exception may temporarily apply to an unenrolled Owner; it is not a normal production sign-in option.')
h('Timeouts and unsuccessful attempts')
bullets(['Confirm sign-in expires after five minutes. Start again with your password if it expires.','Three incorrect second-factor attempts end the pending sign-in.','Repeated incorrect passwords or codes can temporarily block attempts. Follow the on-screen wait message or countdown.','The admin session can expire after 30 minutes without activity. Sign in again when asked.'])
h('Sign out')
p('Use the admin Logout control when you finish, especially on a shared computer. Closing a browser tab is not a reliable substitute for signing out.')

page('2 Authentication setup')
p('Applies to Admin and Owner accounts. ElimuTaifa uses password authentication followed by an authenticator code. Notification email is not a sign-in code service.')
steps(['On first sign-in, follow the Two-factor authentication screen. For an existing account, open Account → Account security → Set up two-step sign-in.','Enter Current password and select Set up 2FA.','Scan the QR code with your authenticator app, or enter the setup key manually.','Read the current six-digit code from that authenticator entry.','Enter Current password and the 6-digit code.','Select Confirm and enable 2FA.','Save the recovery codes immediately after activation.'])
h('Codes and keys are different')
table(['Item','Purpose'],[('Account password','First sign-in step and sensitive account changes'),('Authenticator code','Six-digit second factor; changes about every 30 seconds'),('Setup key or QR code','Enrols the authenticator; keep it private'),('Recovery code','Single-use replacement for the sign-in second factor'),('Google App Password','Gmail sending credential; not an ElimuTaifa sign-in code')],[2.0,4.9])
p('Setup expires after ten minutes. Start again if it expires and replace the old authenticator entry with the new setup key. An old entry will not match the new setup.')
p('If manual authenticator settings are needed, use TOTP, SHA-1, six digits and a 30-second interval. Check the phone clock if codes do not match.')
p('All Admins and Owners need 2FA. The web interface does not provide a normal option to turn it off. Never share the QR code or setup key.')

page('3 Recovery codes')
p('Applies to Admin and Owner accounts. Ten recovery codes appear after enabling 2FA. They are shown only once, and each code works once.')
h('Save your codes')
steps(['Stay on the Save your recovery codes now screen.','Use Download backup codes (.txt), or record the codes securely.','Store the file in a private protected location, separate from your phone.','Do not refresh or leave the page until the codes are saved.'])
p('The downloaded text file is not encrypted. Do not put it in a public folder or share it through an ordinary message. This manual is not a place to store your codes.')
h('Use a recovery code')
steps(['Sign in with Username and Password.','At Confirm sign-in, enter one unused recovery code in Authenticator / recovery code.','Select Confirm.','Mark that code as used in your private copy.'])
h('Create replacement codes')
steps(['Open Account → Account security → Manage two-step sign-in.','Enter Current password.','Enter a valid authenticator code or unused recovery code.','Select Create new recovery codes.','Save the new codes immediately and remove the old copy.'])
p('Creating replacement codes invalidates all old recovery codes. It does not replace the authenticator secret or revoke access from a lost phone.')
h('Lost phone or no usable codes')
p('Use an unused recovery code if you still have your password. If the phone is lost or compromised, ask a trusted developer to reset 2FA after verifying your identity. The supported developer reset removes enrolment and recovery codes, so you must enrol again. There is no public email-based 2FA reset endpoint.')

page('4 Change your password')
p('Applies to Admin and Owner accounts. You can change your own password in Account; the Admins screen does not reset other users’ passwords.')
steps(['Open Account → Account security.','Enter Current password.','Enter New password.','Enter the same value in Confirm new password.','Select Update password.','Check that the page confirms Password changed.'])
table(['Rule','Requirement'],[('Length','12 to 200 characters'),('Confirmation','Both new password fields must match'),('Current password','Must be correct'),('New value','Must differ from the current password')],[2.0,4.9])
p('Choose a unique password and store it in a password manager. Do not use your Gmail password or Google App Password as your ElimuTaifa password.')
h('Temporary password after account creation')
p('An Owner supplies the new account’s temporary password. Change it in Account after your first setup and sign-in. This is a recommended handover step; the implementation does not provide a separate forced first-login password-change wizard.')
h('Forgotten password')
p('The current sign-in screen does not provide a Forgot password email reset flow. Contact the Owner and a trusted developer for verified recovery. Recovery codes do not replace a forgotten password. Do not create a duplicate account to bypass recovery without an agreed access decision.')

page('5 Personal email and notifications')
p('Applies to Admin and Owner accounts. Each account has its own notification email. Updating it changes only that account.')
steps(['Open Account → Email and notifications.','Enter Your notification email.','Select Save my email.','Check the Account email saved confirmation.'])
p('Leave the field blank and save to stop receiving emails for your account. This does not pause system email for other users. Saving an address does not perform the system sender test or confirm inbox delivery.')
h('Which messages you receive')
table(['Recipient','Messages when the relevant channels are enabled'],[('Admin with a saved email','Own completed sign-in alerts and own new-account notification'),('Active Owner with a saved email','Staff sign-in and new-account alerts, critical alerts, daily and Friday reports')],[2.1,4.8])
p('Sign-in alerts are queued after the password and any required 2FA have been completed. New-account alerts do not include the temporary password. Delivery depends on the shared sender and worker being available.')
h('Two email fields serve different purposes')
table(['Field','Purpose'],[('Your notification email','Receives messages for your own account'),('Sender email','Sends system messages for all accounts; Main Owner-only setup')],[2.2,4.7])
p('The sender and your notification email may be different. They may also be the same address. Changing the sender does not replace other Owners’ or Admins’ notification addresses.')

page('Part B Owner only')
d.add_heading('6 Sign up and adding users',2)
p('There is no public self-service sign-up page for Admin or Owner accounts. An existing Owner creates staff accounts through Admins. The first account at a new installation is a developer setup task, not public sign-up.')
steps(['Sign in with an Owner account and complete 2FA.','Open Admins.','In Add admin, complete the fields below.','Select Create admin.','Check that the account appears in the list.','Share the username and temporary password privately with the intended person.','Ask them to enrol 2FA, save recovery codes and change the temporary password.'])
table(['Field','How to complete it'],[('Username','3–50 characters: letters, numbers, dots, underscores or hyphens; must be unique'),('Display name','2–80 characters'),('Email (optional)','Recipient address for this account; may be left blank'),('Role','Admin for ordinary staff, Owner for a trusted account manager'),('Temporary password','12–200 characters')],[1.9,5.0])
p('The account is created Active. Selecting Owner gives user-management privileges. It does not grant Main Owner control of the system email sender or schedule.')
p('The new-account email is a notification, not an invitation link or password delivery service. The Owner still needs to hand over the initial sign-in details securely.')

page('7 Roles and account access')
p('Owner-only functionality. In the Admins list, ordinary Admin rows have Role, Status, Update and Remove controls. Your own row points you to Account for password changes. Other Owner rows do not show the ordinary editing controls.')
h('Change an Admin role or status')
steps(['Open Admins and find the correct Admin row.','Choose Role → Admin or Owner.','Choose Status → Active or Inactive.','Select Update and check the resulting role and status.'])
p('Promoting an Admin to Owner grants account-management privileges. The row will then be treated as an Owner row in this screen. Review the decision carefully before promotion.')
h('Disable an account')
p('Choose Inactive and select Update. The account cannot continue normal authenticated access while inactive. For a temporary access stop, this preserves the account instead of removing it.')
h('Remove access')
steps(['Find the correct Admin row and select Remove.','Read and confirm Remove access for this admin.','Check that the account no longer appears as an available staff account.'])
p('Remove revokes access and keeps historical activity records. It is not simply an Inactive switch; this guide does not promise an Undo action. Use Inactive for a temporary pause.')
h('Protected accounts and unavailable actions')
bullets(['You cannot remove or change your own role/status through these user-management actions.','The designated Main Owner cannot be removed, demoted or disabled through these actions.','The backend also protects the last active Owner.','The Admins screen has no control to reset someone else’s password or authenticator.','Transferring Main Owner authority is not a supported action in this screen.'])

page('Part C Main Owner only')
d.add_heading('8 System email sender',2)
p('The shared sender sends messages for all accounts. Only the designated Main Owner can configure it. Other Owners and Admins manage their own recipient email instead.')
steps(['Open Account → Email and notifications.','Save Your notification email first. This address receives the setup test.','Open Set up system sender or Change system sender.','Choose Provider → Gmail or Brevo.','Enter Sender email and the matching provider credential.','Select Test connection and wait for the result.','Check the success message and look for the test in your inbox or Spam folder.'])
table(['Provider','Credential'],[('Gmail','16-character Google App Password for the sender account'),('Brevo','Transactional API key for the provider account, with a verified sender; not an SMTP key')],[1.4,5.5])
h('Gmail preparation')
p('Enable Google 2-Step Verification on the sending Google account and obtain an App Password if that account supports it. Enter the App Password in the Google App Password field. Do not enter the normal Gmail password. Google’s two-step account setup is separate from ElimuTaifa’s authenticator setup.')
h('Test and save behaviour')
p('The sender connection is saved only after the provider accepts a real test message. Provider acceptance does not guarantee inbox placement. A failed test keeps the previous connection.')
p('Leave the credential field blank only to reuse a saved credential for the same provider and sender. Changing either requires the matching credential. Credentials are not prefilled or displayed again.')
p('Pause all system emails stops delivery for everyone. A successful Test connection enables system email again. It does not change individual recipient addresses.')

page('9 Notifications and schedule')
p('Main Owner-only editing. Other Owners can open Notifications to view its settings; Admin accounts do not manage this page.')
steps(['Open Notifications → Notifications and schedule.','Select the message channels you want enabled.','Set Report time (East Africa Time).','Optionally open Record development progress.','Select Save settings.'])
table(['Channel','What it covers'],[('Admin sign-ins','Completed password and required 2FA sign-ins'),('New admin accounts','Alerts for the Owner and new account when their emails are set'),('Critical alerts','Detected fatal errors, repeated errors and repeated failed sign-ins'),('Daily morning report','Yesterday’s page views, visitors and visitors with successful results'),('Friday system report','Previous seven complete days, source health and recorded progress')],[2.0,4.9])
p('Reports go to active Owners with a saved account email. The default report time is 07:00 EAT. Event alerts are queued when detected; Report time schedules the daily and Friday reports.')
p('Progress percentage is an Owner assessment from 0 to 100, with optional notes. It is not an automatic development score. Reports do not measure SEO reach, server load capacity, uptime or response time.')
h('Automatic delivery and delays')
p('Emails use a background queue. The local worker is scheduled every minute while the configured Windows user is signed in and the computer is running. Delivery stops during shutdown or sleep. Hosting requires a server worker schedule.')
p('Source checks and notification delivery tracking run in the background; the Notifications page does not display delivery history or automatic check controls. Repeated source failures may generate alerts, but do not change published result mappings.')
p('Critical alerts depend on the worker, database and sender. A complete server outage can also prevent the alert email from being sent. Pending messages can wait while sending is paused; failed messages need investigation before resending.')

page('Part D Help and handover')
d.add_heading('10 Troubleshooting',2)
table(['Problem','What to do'],[
('No Sign up button','Ask an Owner to create the account through Admins.'),
('Incorrect username or password','Use Username rather than email. Check the details privately with the Owner.'),
('Sign-in temporarily blocked','Follow the wait message. Do not repeatedly submit guesses.'),
('Authenticator code does not match','Check the current setup entry and automatic phone clock. Use a fresh code.'),
('Setup expired','Start setup again and replace the old authenticator entry.'),
('Recovery code was already used','Use another unused code. Generate replacements once access is recovered.'),
('Phone lost and no codes','Contact a trusted developer for identity-verified 2FA reset.'),
('Forgotten password','There is no self-service email reset flow. Request verified assistance.'),
('New passwords do not match','Re-enter both fields with the same new value.'),
('System sender is not visible','Only the designated Main Owner can configure it.'),
('Gmail setup test fails','Check sender and Google App Password. The normal Gmail password is not used.'),
('Emails are delayed','Check your saved email, enabled channels, Spam, sender status and worker availability.'),
('Session expired','Reload and sign in again before resubmitting.')],[2.8,4.1])
p('When reporting a problem, include the screen name, time in EAT and error text. Do not include passwords, authenticator setup keys, recovery codes or provider credentials.')

page('11 Account handover checklists')
h('New Admin or Owner')
check(['The account has the intended username and role.','Initial credentials were shared privately.','The user can sign in.','The user has completed 2FA enrolment.','Ten recovery codes were saved privately, separate from the phone.','The temporary password was changed.','The user’s notification email was saved if email is wanted.','The user knows how to sign out and request help.'])
h('Owner review')
check(['Owner roles are limited to trusted account managers.','Staff who no longer need access are inactive or removed.','The correct account is selected before Update or Remove.','Lost-phone reports are handled separately from password recovery.'])
h('Main Owner email setup')
check(['The Main Owner recipient email is saved.','The intended provider and sender are selected.','A matching App Password or API key is supplied privately.','The provider has accepted the setup test.','The actual inbox or Spam folder has been checked.','Notification channels and EAT report time are saved.','The worker can run on the local computer or hosting server.'])
p('Tick these items only after performing the relevant checks. This guide does not record a completed sign-in, email test or account recovery.')

d.save(OUT)
print(OUT)
