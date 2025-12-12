# UKF
Team project UK Fruit

## Database configuration
Not performed as would lock assesors out.
- Copy `config/database.php` to `config/database.local.php` if you need to override credentials locally. Use Environment variables in production. You would setup the least privlege user with this SQL.

ALTER USER 'root'@'localhost' IDENTIFIED BY 'password';
CREATE USER 'user'@'localhost' IDENTIFIED BY 'password';
GRANT SELECT, INSERT, UPDATE, DELETE 
    ON stocktrackpro.* 
    TO 'user'@'localhost';
FLUSH PRIVILEGES;

- The shared PDO helper lives in `includes/database.php`. Pages can include it with `require_once dirname(__DIR__) . '/includes/database.php';` and call `Database::query()` / `Database::transaction()`; the legacy `mysql_*` shim has been removed.

## Local runtime (XAMPP 3.3, PHP 8.2)
1. Keep the editable source in your local `UKF` repo workspace (wherever you cloned it).
2. When you need to test, mirror the folders into XAMPP using:
	```powershell
	$repo = "C:\path\to\UKF"
	robocopy "$repo\StockTrackProLite" "C:\xampp\htdocs\StockTrackProLite" /MIR
	robocopy "$repo\WarehouseProLite" "C:\xampp\htdocs\WarehouseProLite" /MIR
	robocopy "$repo\includes" "C:\xampp\htdocs\includes" /MIR
	robocopy "$repo\config" "C:\xampp\htdocs\config" /MIR
	```
3. Browse to `http://localhost/StockTrackProLite` or `http://localhost/WarehouseProLite` after importing `stocktrackpro.sql`. HTTPS not used as no way to transfer certificate.

### Label printing prerequisites
- Warehouse label previews now render QR codes via PHP GD. The XAMPP build must have GD enabled or no QR will appear.
- To enable it, open `<xampp install>\php\php.ini`, find the line `;extension=gd`, and remove the leading semicolon so it reads `extension=gd`, then restart Apache.
- After GD is active, `WarehouseProLite/label_print.php` will embed QR + barcode images directly in the preview/print output. Without it, the page shows a warning banner.

## Linting & tooling
- PHP is not on the global `PATH`. Invoke the XAMPP binary directly when linting, e.g. `"C:\xampp\php\php.exe" -l WarehouseProLite/qa_edit.php`.
- Alternatively install a PHP-aware VS Code extension (Intelephense, PHP IntelelliSense) or project-local Composer tools (`phpcs`, `phpstan`) and document any new commands here.

## Authentication & sessions
- Both apps now enforce authentication through their shared headers: `StockTrackProLite/includes/header.php` requires `includes/auth.php`, and `WarehouseProLite/includes/header.php` does the same for warehouse routes. Any page that includes the header automatically checks `$_SESSION['user_id']`, `$_SESSION['user']`, and the corresponding `role` key before rendering.
- Login handlers populate those session keys via the PDO helper and regenerate the session ID on each successful authentication; keep the session shape and regeneration step identical when adding new entry points so the guard stays reliable.
- A simple session-scoped throttle blocks login attempts for one minute after 5 consecutive failures. Reuse the helper in `includes/login_throttle.php` if new authentication surfaces are added.
- CSRF tokens are issued per form via `includes/security.php`; each successful submission invalidates that token and logout clears the session cache. Always embed tokens using `Csrf::field()` when adding forms or POST endpoints.
- Logout scripts regenerate the session ID, clear data with `session_unset()`/`session_destroy()`, and explicitly expire the session cookie. Reuse that sequence (`session_start()`, `session_regenerate_id(true)`, `session_unset()`, `session_destroy()`, expire cookie, redirect) for any future logout or impersonation flows to prevent fixation and leave no residual session data.
- User Authentication is now by bcrypt() and cryptographically secure.

## Manual smoke tests
Run these flows after significant changes to confirm both apps behave:
- **Warehouse login** → `index.php` → `login.php` (valid + invalid credentials).
- **Deliveries** → add a delivery, verify dashboard counters update.
- **Adjustments** → post positive/negative adjustments and ensure stock updates.
- **QA samples** → add, edit, delete; confirm notices render.
- **Stocktake** → start a take, enter counts, review variance, mark reconciled.
- **Office orders** → create an order, review it on the orders list.

Record the date, dataset used, and any anomalies in PR descriptions so teammates can replay the same sequence.
