# VaxCare — Vaccination Management System

VaxCare is the complete PHP/MySQL application requested by the supplied Aptech eProject brief. It implements the parent, administrator, and hospital modules from registration through final vaccination reporting.

## Quick start with XAMPP

1. Start **MySQL** from the XAMPP Control Panel.
2. Open PowerShell in this project folder.
3. Run `php setup.php` once. The installer is safe to run again.
4. Run `php -S 127.0.0.1:8000 -t public`.
5. Open `http://127.0.0.1:8000/`.

For Apache, copy this folder into `C:\xampp\htdocs`, start Apache and MySQL, run `php setup.php`, and open the project's `/public/` directory in the browser.

## Demo login credentials

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@vaxcare.test` | `Admin@123` |
| Parent | `parent@vaxcare.test` | `Parent@123` |
| Hospital | `hospital@vaxcare.test` | `Hospital@123` |

Change these passwords before using the application with real information.

## Database credentials

The XAMPP defaults are used unless environment variables override them:

| Setting | Default |
|---|---|
| Host / port | `127.0.0.1:3306` |
| Database | `vaccination_management` |
| Username | `root` |
| Password | empty |

Supported overrides: `APP_URL`, `APP_TIMEZONE`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.

## Submission files

- Application source: `app/` and `public/`
- Database installer: `setup.php`
- Database schema: `database/schema.sql`
- Ready database backup: `database/vaccination_management.sql`
- Complete documentation: `docs/PROJECT_REPORT.md`
- Verified interface screenshots: `docs/screenshots/`

## Main workflows

- Parent: register/login, manage child details, see calculated dates, search hospitals, request a booking, see booking status, read vaccination reports, update profile.
- Administrator: view all children, see upcoming dates, review and approve/reject requests, add/update/delete hospitals, view vaccine availability, inspect bookings, filter date-wise vaccination reports.
- Hospital: register/login, publish availability and stock, receive approved appointments, record Vaccinated or Not vaccinated outcomes.

No package installation or internet connection is required. The UI uses only local PHP, CSS, and JavaScript.
