# UKF Modernization — Test Plan

## Purpose
This document defines the testing approach and maps tests to the business goals in the LegacyBusinessCase. Use this file as the canonical test plan for submission evidence.

## Scope
Covers `StockTrackProLite`, `WarehouseProLite`, shared `includes/`, and DB schema used by the project.

## High-level Strategy
- Unit tests: automated with PHPUnit (helpers, CSRF, password, login throttle, Database helper).
- Integration tests: automated with PHPUnit (DB transactions, auth flows). Requires local MariaDB with the project schema.
- Functional/manual tests: recorded in the manual test log template (`tests/MANUAL_TEST_LOG.md`).
- Security checks: CSRF, SQL injection, session fixation and login throttle.

## Requirements Traceability (excerpt)
- SEC-1 Modern authentication — Tests: `tests/Unit/PasswordTest.php`, `tests/Integration/AuthenticationTest.php`
- SEC-3 CSRF protection — Tests: `tests/Unit/CsrfTest.php`
- SEC-4 Prepared statements — Tests: `tests/Integration/DatabaseTest.php`
- SEC-5 Login throttling — Tests: `tests/Unit/LoginThrottleTest.php`
- REL-1 Transactions & REL-2 Charset — Tests: `tests/Integration/DatabaseTest.php`

## Test Environment
- OS: Windows 11
- PHP: XAMPP PHP 8.2 (used in this workspace)
- Database: MariaDB (local), schema from `stocktrackpro.sql`
- PHPUnit: `vendor/bin/phpunit.phar` (downloaded into `vendor/bin`)

## How to run automated tests
Open PowerShell in the project root and run:

```powershell
# Run all tests
C:\xampp\php\php.exe vendor\bin\phpunit.phar --configuration phpunit.xml

# Unit tests only
C:\xampp\php\php.exe vendor\bin\phpunit.phar tests\Unit

# Integration tests only (requires DB)
C:\xampp\php\php.exe vendor\bin\phpunit.phar tests\Integration
```

If Composer is available and configured, you can also run `composer install` then `composer test`.

## Pre-test setup checklist
- Ensure MariaDB is running and `stocktrackpro.sql` has been imported into a test database.
- Create a database user with least-privilege for tests (SELECT/INSERT/UPDATE/DELETE). Update `config/database.local.php` if needed.
- Confirm `config/database.php` points to the test DB with `charset=utf8mb4`.

## Evidence checklist (for 80–89% grade)
- PHPUnit output screenshot showing passing tests.
- HTML coverage report (optional but recommended).
- Completed manual test log with timestamps and tester name.
- Security test screenshots (CSRF rejection, failed SQL injection payloads handled gracefully).
- Short reflection section describing limitations and remaining risks.

## Test Execution Plan (brief)
1. Run unit tests and fix any failing assertions.
2. Run integration tests connected to a disposable test DB.
3. Execute manual functional tests from `tests/MANUAL_TEST_LOG.md`, capture screenshots and notes.
4. Run security checks (CSRF, SQLi, session regen) and document results.
5. Produce a Test Summary Report (pass/fail counts, requirement coverage, outstanding defects).

## Contacts
- Tester: [Your Name]
- Repo path: project root (this workspace)


---
Generated at: 2025-12-11
