# UKF
Team project UK Fruit

## Database configuration
- Copy `config/database.php` to `config/database.local.php` if you need to override credentials locally (the `.local` file is git-ignored).
- The shared PDO helper lives in `includes/database.php`. Pages can include it with `require_once dirname(__DIR__) . '/includes/database.php';` and call `Database::query()` / `Database::transaction()`; the legacy `mysql_*` shim has been removed.

## Local runtime (XAMPP 8.2)
1. Keep the editable source in your local `UKF` repo workspace (wherever you cloned it).
2. When you need to test, mirror the folders into XAMPP using:
	```powershell
	$repo = "C:\path\to\UKF"
	robocopy "$repo\StockTrackProLite" "C:\xampp\htdocs\StockTrackProLite" /MIR
	robocopy "$repo\WarehouseProLite" "C:\xampp\htdocs\WarehouseProLite" /MIR
	robocopy "$repo\includes" "C:\xampp\htdocs\includes" /MIR
	robocopy "$repo\config" "C:\xampp\htdocs\config" /MIR
	```
3. Browse to `http://localhost/StockTrackProLite` or `http://localhost/WarehouseProLite` after importing `UKF.sql` into MariaDB.

## Linting & tooling
- PHP is not on the global `PATH`. Invoke the XAMPP binary directly when linting, e.g. `"C:\xampp\php\php.exe" -l WarehouseProLite/qa_edit.php`.
- Alternatively install a PHP-aware VS Code extension (Intelephense, PHP IntelelliSense) or project-local Composer tools (`phpcs`, `phpstan`) and document any new commands here.

## Manual smoke tests
Run these flows after significant changes to confirm both apps behave:
- **Warehouse login** → `index.php` → `login.php` (valid + invalid credentials).
- **Deliveries** → add a delivery, verify dashboard counters update.
- **Adjustments** → post positive/negative adjustments and ensure stock updates.
- **QA samples** → add, edit, delete; confirm notices render.
- **Stocktake** → start a take, enter counts, review variance, mark reconciled.
- **Office orders** → create an order, review it on the orders list.

Record the date, dataset used, and any anomalies in PR descriptions so teammates can replay the same sequence.
