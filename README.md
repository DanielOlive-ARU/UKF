# UKF
Team project UK Fruit

## Database configuration
- Copy `config/database.php` to `config/database.local.php` if you need to override credentials locally (the `.local` file is git-ignored).
- The shared PDO helper lives in `includes/database.php`. Pages can include it with `require_once dirname(__DIR__) . '/includes/database.php';` and call `Database::query()` / `Database::transaction()` during the migration away from `mysql_*` APIs.
