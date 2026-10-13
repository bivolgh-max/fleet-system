# Fleet Desk

Vehicle Fleet Maintenance and Compliance System for the PFD200 mini project.

Fleet Desk is a local PHP/MySQL application for a fictional small fleet. An administrator can maintain vehicle and driver records, assign drivers, record servicing and repairs, and review manually entered compliance dates and spending. It is a learning project, not a certified compliance service.

## What works

- Login with a hashed password, session regeneration, inactivity timeout, logout and CSRF protection.
- Vehicle creation, list, search, status filters, editing and safe archiving.
- Driver creation, searching, editing and basic contact/licence validation.
- One active driver assignment per vehicle and one active vehicle per driver, with retained history.
- Services and repairs, exact costs in integer pence, mileage updates and service schedules.
- MOT/insurance date warnings, service reminders and a dashboard.
- A cost report with a date range; archived vehicles remain included in historical spending.

Licence format and compliance dates are **not verified** against DVLA, MOT or insurer services. Due soon means expiry within the next 30 days inclusive. Service mileage is due soon within 500 miles and overdue at or above its target.

## Requirements

- XAMPP with PHP 8.1 or newer and MariaDB 10.4 or newer, or PHP 8.1+ and MySQL 8+.
- PHP extensions: PDO, pdo_mysql and mbstring. Automated unit tests additionally need pdo_sqlite.
- A modern browser. No Composer, npm or third-party CSS framework is needed to run the application.

## Windows XAMPP setup

1. Extract this project and copy the `fleet-system` folder to `C:\xampp\htdocs\`.
2. In the XAMPP Control Panel, start **Apache** and **MySQL**.
3. Open `http://localhost/phpmyadmin`, create a **new** database named `fleet_system` with `utf8mb4_unicode_ci`, then select it.
4. Use **Import** to import `database/schema.mysql.sql`. Do not import the SQLite test schema into MySQL.
5. If your MySQL port, username or password differs from XAMPP defaults, copy `config/config.example.php` to `config/config.local.php` and change only the local copy.
6. Open Windows Command Prompt and run:

```bat
cd C:\xampp\htdocs\fleet-system
C:\xampp\php\php.exe cli\create_admin.php
```

Choose your own administrator name, email and new password. There is no shared default login. Password entry is visible in this local terminal: do not record or screenshot it.

7. For fictional demonstration data, run this once, while vehicle and driver tables are empty:

```bat
C:\xampp\php\php.exe cli\seed_demo.php
```

It creates five vehicles, three drivers, one assignment, three services and two repairs. It refuses to overwrite a populated fleet. The sample registration and licence numbers are fictional; example.com addresses are used.

8. Open `http://localhost/fleet-system/public/` and sign in with your chosen account.

## Run the automated tests

```bat
C:\xampp\php\php.exe tests\run.php
```

The test suite uses an **in-memory SQLite database**, not your live MySQL database. It prints a JSON result and exits with code 1 if a test fails. A successful SQLite run does not prove MySQL import or concurrent row locking works.

The supplied evidence records 70 PHP validation/repository tests and 21 simulated HTTP checks that passed in PHP 8.3.33 WebAssembly with SQLite. The MySQL/MariaDB installation, Apache access restrictions, genuine browser usability and concurrency checks must still be completed on the target environment. See `docs/manual_test_plan.md`.

For optional reproduction of the WebAssembly checks, Node.js 24.19.0 was used. The pinned development dependencies and harnesses are supplied. From the project folder, run `npm install --ignore-scripts` and `npm run test:wasm`. This is not needed to run the PHP application. These checks overwrite the generated evidence JSON with a new run and use a fictional isolated database, never your configured MySQL database.

## Project structure

```text
app/         Validation, security helpers, repository and HTML views
config/      Configuration example; local credentials are ignored by Git
database/    MySQL schema, SQLite test schema, ERD and verification queries
public/      Entry point and local CSS assets
cli/         Local administrator and fictional sample-data setup
tests/       Automated PHP tests
docs/        Planning, design, user guide, test plan and static portfolio
evidence/    Machine-readable results from the checks actually executed
```

Use `public/` as the web root where possible. When the entire folder is placed under XAMPP htdocs, the supplied `.htaccess` files deny HTTP access to private directories. Verify those restrictions in Apache. A PHP built-in server should be started with `-t public`; do not serve the repository root. Never expose the demonstration XAMPP root account over the internet.

## GitHub portfolio

Repository: [bivolgh-max/fleet-system](https://github.com/bivolgh-max/fleet-system).

This README provides the project overview and setup instructions. Planning, architecture, the data dictionary, ERD, user guide and test plan are in [`docs/`](docs/). The public repository contains fictional demonstration data and an example configuration. Personal assessment documents, local credentials, database files and real driver records are excluded.

The static portfolio source is [`docs/index.html`](docs/index.html). To enable its separate website, open the repository's **Settings > Pages**, choose **Deploy from a branch**, select **main** and **/docs**, then save. See [the publication guide](docs/github_pages.md). The website is not claimed to be live until its URL has been checked.

GitHub Pages serves the portfolio; it does not execute the PHP/MySQL application. Run the application using the XAMPP steps above.

## Limitations and future development

This version is a single-administrator prototype. There is no driver self-service login, external compliance lookup, document upload, email/SMS reminder, immutable audit trail, MFA or production hosting. Login throttling is account-key based and is not a full distributed abuse-control system. Maintenance records have no edit/delete feature, which avoids erasing history but requires an explicit correction workflow in a future version. A historical service can update its vehicle schedule if new schedule values are supplied; administrators must enter the latest agreed schedule.

For real deployment: require HTTPS, a restricted database account, independent security review, tested backup/restore, privacy controls, multi-user concurrency tests and environment-specific configuration. Do not treat the local prototype as production-ready.

## Assistance and evidence

This package was prepared with AI assistance. Test results identify their actual environment. The student must understand, run and evaluate the project, confirm lecturer approval, follow the institution's AI-use rules and provide their own genuine reflection and interview evidence. Do not present automated assistance as independent work or claim unperformed testing.
