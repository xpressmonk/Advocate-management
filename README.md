# Advocate Management System - Laravel 12 MVP

Requirements: PHP 8.2+, Composer 2, MySQL 8 or compatible MariaDB.

Install:
1. Extract ZIP and open terminal in this folder.
2. Create MySQL database `advocate_management`.
3. Copy `.env.example` to `.env` and update database credentials.
4. Run `composer install`
5. Run `php artisan key:generate`
6. Run `php artisan migrate --seed`
7. Run `php artisan serve`
8. Visit http://127.0.0.1:8000

Seeded administrator: admin@example.com / ChangeMe123!
Change the password immediately. Never deploy with default credentials.

Included: login/logout, dashboard, clients, advocates, legal cases, case/advocate assignments, hearing scheduling and hearing history.

Private case/client document uploads, authenticated downloads and deletion are also available. Files are stored outside the public web root, with file type and 10 MB size validation. Access is currently shared among all signed-in users; malware scanning and per-record permissions are not included.

Task management supports general or case-linked tasks, optional assignees, due dates, priorities and status tracking. The calendar combines task due dates with scheduled court hearings. Tasks are shared among signed-in users; recurring tasks and automated reminders are not included.

Invoice management supports case/client billing, fee line items, tax calculations, invoice statuses and partial or full payment recording. All users can view and manage invoices in the current MVP.

Expense management supports general or case-linked expenses, categories, vendors, payment methods, reimbursement flags and paid/pending/cancelled statuses.

PDF reporting supports downloadable invoices, individual payment receipts, case summaries and filtered expense reports.

Hearing reminders support email and webhook-based SMS delivery one day before a hearing. Reminder records are de-duplicated, and the scheduled command `php artisan hearings:send-reminders` runs daily. Email delivery requires mail configuration; SMS delivery requires `SMS_WEBHOOK_URL`.

Hearing records can be opened directly in Google Calendar from the hearing list, hearing details, case details, and calendar view.

Email and SMS settings are available under Reminder settings. SMTP passwords and SMS bearer tokens are stored encrypted. SMS uses a configurable webhook and bearer authorization.

Role-based access includes Administrator, Manager, Staff and Viewer roles. Administrator/Manager roles manage users and audit logs; Staff can create and update; Viewer is read-only. Sensitive mutation passwords and reminder credentials are excluded from audit details.

Automated feature tests cover documents, tasks, invoices, expenses, PDF reports, hearing reminders, Google Calendar links, reminder settings, RBAC and audit logging.

## Remaining development

The following modules still need implementation:

1. Detailed user roles and permissions.
2. Audit logs and broader automated tests.

**Important:** This is an initial runnable MVP source scaffold, not yet a complete production-ready legal management system. Add policies and private file storage before entering confidential client data. Tailwind is loaded from CDN for quick evaluation; compile assets with Vite for production.

## cPanel deployment

Use PHP 8.2 or newer and configure the domain's document root to point to this project's `public/` directory. The `public/.htaccess` file routes application requests through Laravel and disables directory listings. Keep the rest of the project, including `.env`, `vendor/`, and `storage/`, outside the web root. Ensure `storage/` and `bootstrap/cache/` are writable by the PHP process.


## Google Drive case-document integration

The distributed ZIP intentionally excludes `.env`, Git history, vendor files, and runtime cache/log contents. Keep your existing `.env` when updating an existing installation; for a fresh install, copy `.env.example` to `.env` and configure it.

This version stores **case documents** in a central firm's Google Drive account. Documents attached to a client without a case continue to use the existing private local disk. Google Drive files are private by default; the app does not create public sharing permissions. Document routes use the application's existing role permissions (`view`, `create`, `delete`), while Google Drive connection settings require `manage_settings`.

### Install dependencies

This project targets PHP 8.2 and Laravel 12.

```bash
composer install
composer update google/apiclient
```

The Google API PHP client is declared in `composer.json`. The update command refreshes `composer.lock` to include it.

### Google Cloud setup

1. Create/select a project in https://console.cloud.google.com/.
2. Enable **Google Drive API**.
3. Configure the OAuth consent screen. During testing, add the firm's central Google account as a test user if the app is in Testing mode.
4. Create an OAuth Client ID of type **Web application**.
5. Add this exact authorized redirect URI (replace the host with your production HTTPS domain when deployed):
   `http://127.0.0.1:8000/settings/google-drive/callback`
6. Download the OAuth client JSON file.
7. Create `storage/app/private/google/` and place the JSON file there as `oauth-client.json`. Never put it under `public/` or commit it to source control.

### Environment

Set these in `.env`:

```env
APP_URL=http://127.0.0.1:8000
GOOGLE_DRIVE_REDIRECT_URI="${APP_URL}/settings/google-drive/callback"
GOOGLE_DRIVE_ROOT_FOLDER_NAME="Advocate Management"
GOOGLE_DRIVE_MAX_UPLOAD_MB=20
```

The OAuth client JSON path is fixed at `storage/app/private/google/oauth-client.json`.

### Migrate and connect

```bash
php artisan optimize:clear
php artisan migrate
php artisan serve
```

Sign in as an administrator or manager with `manage_settings` permission, then open:

`http://127.0.0.1:8000/settings/google-drive`

Click **Connect central Google account** and authorize the firm's Google account. The app creates the root `Advocate Management` folder. New cases then get a child folder named `{case_number} - {title}`. If Drive is temporarily disconnected during case creation, the case still registers; its folder is created on the first case-document upload after Drive is reconnected.

### Permissions and storage

- Users with the `view` role permission can list/download documents.
- Users with the `create` permission can upload.
- Users with the `delete` permission can delete.
- Only users with `manage_settings` can connect/reconnect Google Drive.
- Case documents are stored in Google Drive. Client-only documents remain on the existing private local disk.
- Drive OAuth refresh tokens are encrypted in the database using Laravel's `APP_KEY`. Keep secure backups of both the database and `APP_KEY`.
- Never make the Drive root folder or case folders public. Do not expose the root-folder URL to ordinary users.
- Configure PHP `upload_max_filesize` and `post_max_size` to at least the configured upload limit.
- For production, use HTTPS and register the exact HTTPS OAuth redirect URI.

**Important:** This integration relies on the project's existing role-based permissions. It does not add per-case staff assignment. A user with the `view` permission can view all documents currently listed by this application. If you need case-by-case access, add explicit case assignments and enforce them in document queries and policies before production use.
