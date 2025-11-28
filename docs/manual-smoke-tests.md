# Manual Smoke Tests

Use this checklist after significant changes (schema updates, PDO refactors, styling passes). All steps assume the latest `UKF.sql` snapshot and the apps mirrored into `C:\xampp\htdocs` via `robocopy /MIR`.

## Warehouse – Login
1. Browse to `/WarehouseProLite` and submit valid credentials.
2. Confirm redirect to `dashboard.php` and that the KPI cards render.
3. Log out and attempt an invalid login; `index.php?error=1` should show the red notice.

## Warehouse – Deliveries
1. `deliveries.php` → “Record Delivery”.
2. Select a product, enter a positive quantity, optional supplier ref, submit.
3. Verify redirect with `?msg=added`, entry appears in the list, and the dashboard “Today” counter increments.

## Warehouse – Adjustments
1. `adjustments.php` → “Add Adjustment”.
2. Post both a +qty and -qty change; each should redirect with `?msg=saved`.
3. Confirm product stock updates accordingly (check via `products.php` or a report).

## Warehouse – QA Samples
1. Add a QA sample with two-decimal Brix/temperature values.
2. Edit the same entry, change status, ensure notices display.
3. Delete the entry; list shows `?msg=deleted`.

## Warehouse – Stocktake
1. `stocktake_new.php` → start a new take, note the ID.
2. Enter counts for a few SKUs and save.
3. `stocktake_view.php?id=…` shows variance lines; post adjustments if needed.
4. Once all variances resolved, mark the take as reconciled and verify it disappears from the “outstanding” banner.

## Warehouse – Reports
1. `reports.php` should render summary tiles, recent deliveries, and adjustments without PHP warnings.
2. Spot-check exported numbers against the dashboard to ensure consistency.

## Office – Orders & Products
1. `StockTrackProLite/login.php` → log in (legacy open access for now).
2. Add a product, edit it, then delete (expect FK notice if referenced).
3. Create a new order with multiple items; review it via `orders.php` and `order_view.php`.
4. Run `reports.php` and confirm sales charts/tables populate.

Document the date, dataset, and any anomalies when you complete this list so teammates can reproduce the same walkthrough.
