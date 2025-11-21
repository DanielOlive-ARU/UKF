# AI Coding Guidelines for UKF

## Big Picture
- Two procedural PHP 5-era apps (`StockTrackProLite/` for office, `WarehouseProLite/` for floor) share one MariaDB schema from `UKF.sql`. Each page is a self-contained controller/view that includes helpers, runs inline SQL, and echoes HTML directly. The codebase now runs under PHP 8.2 using a shared PDO helper.
- Warehouse routes include `includes/auth.php` (session gate) while office routes accept any credentials via `StockTrackProLite/login.php`; the business case mandates converging these into a single login + dashboard.
- Fruit label printing currently runs from `FruitLabels.mdb`. Future work moves those tables into MariaDB (e.g., `label_product_settings`, `label_print_log`) so barcodes can be triggered from the unified PHP UI.
- Business priorities (`LegacyBusinessCase.docx`): PHP 8 upgrade readiness, consolidated authentication, transaction-safe stock handling, reliable stocktake variance flow, and barcode integration.

## Developer Workflow
- Run under XAMPP 3.3: drop both app folders into `C:\xampp\htdocs`, import `UKF.sql`, browse to `/StockTrackProLite` or `/WarehouseProLite`. The legacy portable stack lives under `UKFLegacy/xampp56`; use that tree when diffing Apache/PHP configs or grabbing sample data from `UKFLegacy/UKF.sql`.
- Runtime deploys still copy the working tree into `C:\xampp\htdocs`. Keep editing source in the repo workspace and mirror to XAMPP with `robocopy /MIR` so test runs stay in sync.
- DB credentials are hard-coded as `root`/`` with host `127.0.0.1:3306` and are loaded from `config/database.php` via the PDO helper. Override them in `config/database.local.php` before sharing builds.
- No automated tests exist. Document manual walkthroughs (orders, deliveries, adjustments, QA, stocktake) in PRs so teammates can replay them against the shared DB snapshot.
- Forms post back to the same file, run business logic before HTML, then redirect with `header('Location: page.php?msg=...')`. Preserve this until a router/controller layer is introduced.
- Shared styling lives per app under `assets/style.css`; keep changes scoped so warehouse and office UIs remain visually aligned while the merge plan lands.

- **Runtime baseline (Section 1):**
	- The canonical PHP 5.6 setup is checked in at `UKFLegacy/xampp56/php/php.ini`. It runs with `display_errors=On`, `short_open_tag=Off`, `date.timezone=`, `output_buffering=4096`, and enables the MySQL extensions. Mirror the same ini choices when standing up PHP 8.2 (extension equivalents only) so behaviour stays consistent across runtimes.
	- Sessions currently store to `session.save_path="\Users\DanoI\AppData\Local\Development\UKFLegacy\xampp56\tmp"` with `session.name=PHPSESSID`, `session.use_only_cookies=1`, and no regeneration strategy. Capture an equivalent writable path and cookie policy in the new bootstrap before changing runtimes.
	- Both apps now bootstrap via `includes/db.php`, which simply requires `includes/database.php`. All new code should call the PDO helper directly; the mysql compatibility shim has been removed.
	- Warehouse pages call `session_start()` inside `includes/auth.php`, whereas office pages never start sessions. Document the session expectations (cookie name, regen strategy) before swapping runtimes so you can verify they behave the same way under PHP 8.
	- Keep a PHP 5.6 + PHP 8.2 matrix for smoke tests (login, order entry, delivery intake, stocktake approval) so regressions caused by runtime differences are spotted early.
- **Language compatibility sweep (Section 2):**
	- All DB access now goes through the PDO helper (`Database::query`, `Database::fetchOne`, `Database::transaction`). When adding new pages, never reintroduce `mysql_*`; bind user input instead of concatenating strings.
	- Sanitise inputs via bound parameters. Casting (e.g., `(int)$_POST['id']`) is still fine for guard rails, but actual SQL statements must use prepared placeholders.
	- Multi-step flows (orders, deliveries, adjustments, stocktakes) now rely on `Database::connection()->lastInsertId()` inside transactions. Wrap any new multi-statement workflows the same way.
	- Search for other deprecated constructs as you touch files (e.g., implicit globals, old hashing). Add TODO comments when you uncover new modernization debt (like upgrading to `password_hash`).

## Data & Integration
- Tables `products`, `orders`, `order_items`, `deliveries`, `adjustments`, `qa_samples`, and `stock_takes` drive both apps; schema changes ripple to every module. Update SQL statements and HTML enums together when touching these tables.
- Stock deltas happen across multiple scripts (orders decrement, deliveries increment, adjustments +/-). Keep their math consistent or introduce a shared helper to avoid double-counting.
- `FruitLabels.mdb` stores GTIN/best-before defaults per SKU. When migrating, maintain a mapping document so barcode settings can be validated alongside the PHP flows.

## Tooling & Linting
- PHP binaries are available via XAMPP (e.g., `"C:\xampp\php\php.exe" -l path\to\file.php`). Use that path or a VS Code PHP extension for linting because PHP is not on the global `PATH`.
- No Composer dev tools are installed yet. If you introduce `phpcs`/`phpstan`, document the commands in the README and prefer project-local installs.

## Conventions & Risks
- Authentication data lives in separate tables: `users` (office, MD5 hashes) and `wh_users` (warehouse). Plan to normalize them and upgrade to `password_hash` during the PHP 8 transition.
- Destructive actions (`*_delete.php`) accept GET parameters with no CSRF checks; when adding protections, centralize them (e.g., `includes/security.php`) so both apps stay consistent.
- Character sets mix `utf8` connections with `latin1` tables. Record any encoding fixes because they may require DB migrations (see `LegacyBusinessCase.docx` §Maintainability).
- External assets still load over HTTP (old jQuery/Chart.js). Switch to HTTPS/local copies before production to avoid mixed-content issues.

## References
- `LegacyBusinessCase.docx` captures stakeholder goals, modernization phases, and the AI-generated appendix outlining future database tables. Review it whenever prioritizing backlog items or interpreting business terminology.
