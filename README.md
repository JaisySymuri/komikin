# KOMIKIN --- Comic Rental Management System

KOMIKIN is a PHP/MySQL web application for managing a comic rental
business. It supports customer accounts, comic catalog and inventory
management, rental transactions, returns, employee operations, and
administrator reports.

## 1. Technology Stack

-   PHP 8.x (native PHP)
-   MySQL-compatible database
-   PDO with PDO MySQL
-   Bootstrap 5 and vanilla JavaScript
-   PHP sessions
-   Laragon for local development
-   InfinityFree for shared-hosting deployment

## 2. User Roles

  Feature                            Admin   Employee   Customer
  --------------------------------- ------- ---------- ----------
  Operational dashboard               Yes      Yes         No
  Manage comics and categories        Yes      Yes         No
  View customer directory             Yes      Yes         No
  Manage rentals and returns          Yes      Yes         No
  View reports and export CSV         Yes       No         No
  Create/manage employee accounts     Yes       No         No
  Browse catalog                      Yes      Yes        Yes
  Rent comics                         No        No        Yes
  View own rental history             No        No        Yes

Public registration creates Customer accounts only. Admin creates
Employee accounts. Protected pages must enforce roles server-side, not
only by hiding navigation links.

## 3. Business Rules

-   Default rental period: **7 days**.
-   Late fee: **Rp2,000 per comic per overdue day**.
-   Currency: Indonesian Rupiah (IDR).
-   A rental can contain multiple comic titles.
-   Rental prices are captured at checkout in rental details.
-   Stock decreases when a rental is confirmed and is restored when
    items are returned.
-   Individual items can be returned separately; duplicate returns must
    be prevented.
-   Rental and return operations use database transactions.
-   Payments are handled manually; no online payment gateway is
    included.

## 4. Local Installation (Laragon)

### Requirements

-   Laragon
-   PHP 8.x with `pdo_mysql`
-   MySQL
-   Modern web browser

### Setup

1.  Place the project at `C:\laragon\www\rental-komik`.
2.  Start Apache and MySQL in Laragon.
3.  Create a database named `rental_komik` using phpMyAdmin.
4.  Import the initial database schema supplied with the project.
5.  Apply the Phase 6 rental/return migration and then the Phase 9 RBAC
    migration when setting up an existing phased project.
6.  Configure `config/config.php` and `config/database.php` for local
    development.
7.  Open `http://localhost/rental-komik/`.
8.  Create the initial Admin account using the project's approved
    local-only setup procedure. Remove temporary setup scripts
    immediately; never upload a public admin-creation script.

### Local database example

``` php
$dbHost = '127.0.0.1';
$dbPort = '3306';
$dbName = 'rental_komik';
$dbUser = 'root';
$dbPass = '';
```

## 5. Configuration

### `config/config.php`

Before deployment, verify:

``` php
define('APP_ENV', 'production');
define('APP_DEBUG', false);
define('BASE_URL', 'https://YOUR-DOMAIN/rental-komik/');
```

Keep the trailing slash in `BASE_URL`. Use the exact HTTPS URL assigned
by the hosting provider. The application timezone is `Asia/Jakarta`.

### `config/database.php`

For production, use the exact MySQL details shown in the hosting control
panel:

``` php
$dbHost = 'YOUR_MYSQL_HOSTNAME';
$dbPort = '3306';
$dbName = 'YOUR_FULL_DATABASE_NAME';
$dbUser = 'YOUR_MYSQL_USERNAME';
$dbPass = 'YOUR_MYSQL_PASSWORD';
```

Do not use `localhost` unless the host explicitly instructs you to. Do
not use your hosting account password as the MySQL password. Never
publish real database credentials.

## 6. InfinityFree Deployment

1.  Confirm the hosting account and domain.
2.  Open **MySQL Databases** in the hosting control panel.
3.  Create/select the production database and note its exact hostname,
    database name, username, and MySQL password.
4.  Open that database using the provider's phpMyAdmin link.
5.  Export the local `rental_komik` database from Laragon.
6.  Import it into the production database using phpMyAdmin.
7.  Confirm all required tables and migration changes are present.
8.  Upload the application files into the website's
    `htdocs/rental-komik/` directory (or the document-root path
    specified by the host).
9.  Update `config/config.php` and `config/database.php` with production
    values.
10. Ensure `APP_DEBUG` is `false`.
11. Test the live website using the checklist below.

Do not assume the production database is the same as the local database.
Do not import the initial schema over an existing live database. Back up
production data before applying migrations.

## 7. Database Tables

Core tables:

-   `users`
-   `categories`
-   `comics`
-   `rentals`
-   `rental_details`
-   `rental_item_returns` (item-level return tracking)

The Phase 9 migration adds support for `admin`, `employee`, and
`customer` roles. Verify the actual SQL migration files in the current
project package before modifying a database.

## 8. Post-Deployment Test Checklist

### Authentication and roles

-   [ ] Customer can register, log in, and log out.
-   [ ] Public registration cannot create Admin or Employee accounts.
-   [ ] Admin can access reports and employee management.
-   [ ] Employee can access operational pages but cannot access reports
    or employee management, including by entering URLs directly.
-   [ ] Customer cannot access admin/employee pages.

### Catalog and inventory

-   [ ] Catalog, comic details, search, and category filters work.
-   [ ] Admin/Employee can manage comics, categories, and stock.
-   [ ] Cover upload rejects unsupported file types and oversized
    images.

### Rentals and returns

-   [ ] Customer can rent multiple comics.
-   [ ] Checkout reduces stock correctly.
-   [ ] Due date follows the configured rental period.
-   [ ] Admin/Employee can process full and partial returns.
-   [ ] Returned stock is restored exactly once.
-   [ ] Late fees and rental history are correct.

### Reports

-   [ ] Admin can open reports and export CSV.
-   [ ] Employee receives an authorization denial for report and export
    URLs.
-   [ ] Report totals match rental and return records.

## 9. Troubleshooting: Database Connection Error

A generic message such as:

``` text
A database error occurred. Please try again later.
```

means the application caught a database exception and hid its details
because production debug mode is disabled.

Example:

``` text
SQLSTATE[HY000] [1045] Access denied for user
```

This means the MySQL server was reached but rejected the supplied
credentials. Check:

1.  MySQL hostname copied exactly from the hosting panel.
2.  Full database name, including account prefix.
3.  MySQL username.
4.  MySQL password (not the hosting account password).
5.  The application is using the intended production configuration.

If the MySQL password is reset in the hosting panel, update
`config/database.php` to match.

### Safe debugging

-   Keep `APP_DEBUG` set to `false` on the public website.
-   Check the hosting provider's PHP error-log facility if available.
-   If temporary debugging is unavoidable, restrict access, enable it
    briefly, capture the error, and immediately restore `APP_DEBUG` to
    `false`.
-   Never expose database credentials or stack traces on a public page.

## 10. Security and Operations

-   Use HTTPS.
-   Keep `APP_DEBUG` disabled in production.
-   Keep database credentials private.
-   Use PDO prepared statements for database input.
-   Keep CSRF validation on state-changing forms.
-   Enforce authorization checks on every protected endpoint.
-   Store passwords with `password_hash()` and verify with
    `password_verify()`.
-   Validate uploaded cover images by file type and size; do not trust
    client-provided filenames or MIME types.
-   Remove temporary diagnostics, test scripts, and setup utilities from
    the public web directory.
-   Back up the database before updates and migrations.
-   Test changes locally before deploying to production.

## 11. Project Phase Status

  -----------------------------------------------------------------------
  Phase                   Scope                   Status
  ----------------------- ----------------------- -----------------------
  1                       Project setup           Completed

  2                       Database schema         Completed

  3                       PHP foundation and      Completed
                          database connection     

  4                       Authentication          Completed

  5                       Comic inventory and     Completed
                          catalog                 

  6                       Rental transactions and Completed and tested
                          returns                 

  7                       Customer administration Completed and tested
                          and reports             

  8                       Security hardening, UI  Prepared; live
                          polish, deployment      deployment requires
                          preparation             verification

  9                       Role-based access       Migration and tests
                          control                 reported successful

  10                      Audit trail and         Proposed; not
                          activity logs           implemented
  -----------------------------------------------------------------------

## 12. Important Notes

-   Use the actual configuration and SQL migration files included in the
    current project package as the source of truth.
-   Do not overwrite a working production database with a fresh schema.
-   Do not share passwords, private hosting details, or database exports
    publicly.
