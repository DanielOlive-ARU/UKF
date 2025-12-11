# Backlog

Deferred modernization tasks for StockTrackProLite and WarehouseProLite.

## Modernization

### JavaScript Library Upgrades

- [ ] **Upgrade Chart.js from v1.0.2 to v4.x**
  - Current: `new Chart(ctx).Bar(data, options)` (legacy v1 API)
  - Target: `new Chart(ctx, { type: 'bar', data, options })` (v4 API)
  - Affected file: `StockTrackProLite/reports.php`
  - Notes: The v4 API is completely different; requires rewriting chart initialization code
  - Add SRI hash after upgrade (generate via https://www.srihash.org/)

- [ ] **Upgrade jQuery from v1.11.3 to v3.x**
  - Affected file: `StockTrackProLite/includes/footer.php`
  - Notes: Review for deprecated APIs (e.g., `.bind()` → `.on()`, removed methods)
  - Add SRI hash after upgrade (generate via https://www.srihash.org/)

### References

- Business case: `LegacyBusinessCase.docx` §Maintainability
- Copilot instructions: `.github/copilot-instructions.md` (external assets over HTTP)
