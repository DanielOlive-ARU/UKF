``markdown
# Evidence Bundle  UKF Modernization (captured 2025-12-11)

This folder contains test and static-analysis artifacts captured during the automated modernization work. Use these files to review what was run, reproduce results locally, and present submission evidence.

Files you will find (examples from this run):
- phpcs_full_after_phpcbf_final1.txt  phpcs_full_after_phpcbf_final6.txt  iterative PHPCS full reports after PHPCBF runs
- phpcbf_output_YYYYMMDDHHMMSS.txt  PHPCBF stdout for loop iterations
- phpstan_report_after_final5.txt / phpstan_report_after_finalX.txt  PHPStan outputs
- phpunit_output_after_final5.txt / phpunit_output_after_finalX.txt  PHPUnit run outputs
- git_commit_after_phpcbf_finalX.txt, git_branch_after_phpcbf_finalX.txt, git_status_after_phpcbf_finalX.txt  git metadata captured after runs
- manifest.json  generated manifest summarising evidence files and commands

Repro steps (Windows, from repo root):

`powershell
# Run PHPCBF (auto-fix) once
C:\xampp\php\php.exe vendor\bin\phpcbf.phar --standard=phpcs.xml --extensions=php includes StockTrackProLite WarehouseProLite

# Full PHPCS report
C:\xampp\php\php.exe vendor\bin\phpcs.phar --standard=phpcs.xml --extensions=php includes StockTrackProLite WarehouseProLite --report=full --report-file=tests\evidence\phpcs_full_after_phpcbf_YYYYMMDDHHMMSS.txt

# PHPStan
C:\xampp\php\php.exe vendor\bin\phpstan.phar analyse --configuration=phpstan.neon.dist includes StockTrackProLite WarehouseProLite > tests\evidence\phpstan_report_after_YYYYMMDDHHMMSS.txt 2>&1

# PHPUnit
C:\xampp\php\php.exe vendor\bin\phpunit.phar --configuration phpunit.xml > tests\evidence\phpunit_output_after_YYYYMMDDHHMMSS.txt 2>&1

# Capture git metadata
git rev-parse --short HEAD > tests\evidence\git_commit_after_YYYYMMDDHHMMSS.txt
git rev-parse --abbrev-ref HEAD > tests\evidence\git_branch_after_YYYYMMDDHHMMSS.txt
git status --porcelain > tests\evidence\git_status_after_YYYYMMDDHHMMSS.txt
`

Automated loop used during evidence collection:

`powershell
# runs PHPCBF repeatedly for ~25 minutes or until no auto-fixable issues
tests\scripts\phpcbf_loop.ps1
`

Key notes and recommendations:
- The branch used for evidence: evidence/auto-fix-2025-12-11 (commits pushed).
- PHPCBF can fix many style issues automatically; iterative runs reduced auto-fixable violations.
- PHPCS still reports non-fixable issues in several large files (notably WarehouseProLite/reports.php, label_print.php, stocktake_*.php)  these require manual fixes.
- After code edits, always re-run PHPStan and PHPUnit to catch semantic regressions.

Suggested next steps to finalise evidence:
1. Run PHPCBF until it reports no auto-fixable violations.
2. Re-run PHPCS, PHPStan, and PHPUnit and save outputs to 	ests/evidence/ with timestamps.
3. Manually fix remaining PHPCS issues in priority files and re-run static analysis.
4. Update manifest.json with the final filenames and exact commands executed.

Generated: 2025-12-11

``
