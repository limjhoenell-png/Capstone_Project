# ReCORD

Barangay records and visitor logbook for Laravel 12. Administrators manage employees, attendance, leave, holidays, payroll, and the visitor logbook. Employees see their own attendance, leave, published payslips, and the logbook rows tied to their device id.

The app can share a database with the desktop fingerprint system. When `attendance_log` and `logbook` exist, this app reads them. It does not create those two tables.

## Roles

- Admin: `admin@gmail.com`
- Employee: `employee@example.com`

`php artisan db:seed` sets both passwords from `ADMIN_SEED_PASSWORD` and `EMPLOYEE_SEED_PASSWORD`. If those are unset, the password is `ChangeMe!2026`. Change it before anyone else can sign in.

## Attendance

Daily attendance is one merged line per employee and date.

- A complete manual row overrides the device punches for that date.
- A manual row with a blank time-in or time-out keeps the device value for the blank side and is marked merged.
- Approved vacation and sick leave with no punches counts as a paid day.
- Analytics, payroll, and both attendance screens use this same rollup.

Shift start and grace minutes live in payroll settings. A punch after shift start plus grace is late.

## Payroll

Payroll preview and payslip generation use the merged attendance. Payslips start as drafts. Publish a draft before the employee can open it. Marking a draft paid also publishes it and leaves an in-app notice. Mail is sent when the mailer is configured; a mail failure does not roll back the payslip.

Turn on statutory deductions in payroll settings to deduct employee SSS (2025 salary-credit schedule), PhilHealth (2.5% with the 10,000–100,000 monthly band), Pag-IBIG, and TRAIN withholding tax. Custom deductions with those names are skipped while statutory deductions are on, so they are not taken twice. Net pay does not go below zero.

Work on a rest day or holiday adds the premium configured in payroll settings on top of ordinary pay. Night differential applies to hours between the configured night window, default 10:00 PM to 6:00 AM. The 13th month draft is one twelfth of regular pay earned in the selected year.

## Logbook

Employees only see legacy logbook rows whose fingerprint id matches their device id, plus their own app logbook entries. Visitor accounts can be saved and reused on a new logbook entry. Transaction type, authorization, valid ID, and the person being represented are stored when those columns exist.

Deleting an employee requires the posted name to match that account. Logbook rows are removed by device id, not by a matching name.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Tests:

```bash
php artisan test
```
