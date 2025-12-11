# Manual Test Log Template

Use this template to record manual functional and exploratory test runs. Copy the table rows for each executed test case.

| Test ID | Date | Tester | Pre-conditions | Steps | Expected Result | Actual Result | Pass/Fail | Evidence (file) |
|---------|------|--------|----------------|-------|-----------------|---------------|----------:|-----------------|
| FT-AUTH-01 | 2025-12-11 | Daniel | Test user exists | 1) Go to /StockTrackProLite 2) Enter credentials 3) Submit | Redirect to dashboard |  |  | screenshot/auth_login_ok.png |


## Notes on using the log
- Take screenshots for key steps and attach them to the repo `tests/evidence/` folder.
- Include DB queries used for validation where appropriate (e.g., verify print_log entries).
- If a test fails, create a defect entry in `tests/DEFECTS.md` describing severity and steps to reproduce.

## Minimal fields for grading
- Test ID, Date, Tester, Steps, Expected Result, Actual Result, Pass/Fail, Evidence

Generated: 2025-12-11
