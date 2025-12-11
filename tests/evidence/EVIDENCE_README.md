# Evidence Bundle — UKF Modernization (captured 2025-12-11)

This folder contains test and static-analysis artifacts collected for assignment evidence. Use these files when preparing your submission.

Files included:
- `phpcs_full.txt` — full PHPCS report (style errors/warnings)
- `phpcs_report.txt` — PHPCS summary (if present)
- `phpstan_report.txt` — PHPStan static analysis output
- `phpunit_output.txt` — PHPUnit run output
- `git_commit.txt` — current commit short SHA
- `git_branch.txt` — current branch name
- `git_status.txt` — working tree status (porcelain)
- `manifest.json` — high-level metrics and commands (generated)

Key commands used to produce these files (run from repo root):

```powershell
# PHPCS full report
C:\xampp\php\php.exe vendor\bin\phpcs.phar --standard=phpcs.xml --extensions=php includes StockTrackProLite WarehouseProLite --report=full --report-file=tests\evidence\phpcs_full.txt

# PHPStan
C:\xampp\php\php.exe vendor\bin\phpstan.phar analyse --configuration=phpstan.neon.dist includes StockTrackProLite WarehouseProLite > tests\evidence\phpstan_report.txt 2>&1

# PHPUnit
C:\xampp\php\php.exe vendor\bin\phpunit.phar --configuration phpunit.xml > tests\evidence\phpunit_output.txt 2>&1

# Git info
git rev-parse --short HEAD > tests\evidence\git_commit.txt
git rev-parse --abbrev-ref HEAD > tests\evidence\git_branch.txt
git status --porcelain > tests\evidence\git_status.txt
```

Notes and recommendations:
- Run `phpcbf` to auto-fix many PHPCS issues, then re-run PHPCS and PHPStan.
- Address PHPStan errors next (likely logic issues) before large refactors.
- Keep `tests/evidence/` out of commits if you prefer, or tag the repository state with `git tag evidence-<date>` and push.

Generated: 2025-12-11
