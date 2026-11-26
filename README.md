# Advocate Management System — Laravel 13 MVP

Requirements: PHP 8.3+, Composer 2, MySQL 8 or compatible MariaDB.

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

This is an MVP source scaffold, not a fully production-audited system. Tasks, calendar, document vault, invoicing, payments, expenses, PDF exports, reminders, detailed permissions, password reset, audit trails and automated tests remain to be implemented. Add policies and private file storage before entering confidential client data. Tailwind is loaded from CDN for quick evaluation; compile assets with Vite for production.
