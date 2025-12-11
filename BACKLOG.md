# Backlog

Deferred modernization tasks for StockTrackProLite and WarehouseProLite.

## Modernization

### JavaScript Library Upgrades

- [x] **Upgrade Chart.js from v1.0.2 to v4.x** ✅ Complete (2024-12-11)
  - Upgraded to v4.4.7 with full API migration
  - Local copy at `StockTrackProLite/assets/js/chart.umd.min.js`
  - Eliminates CDN dependency and mixed-content concerns

- [ ] **Upgrade jQuery from v1.11.3 to v3.x**
  - Affected file: `StockTrackProLite/includes/footer.php`
  - Notes: Review for deprecated APIs (e.g., `.bind()` → `.on()`, removed methods)
  - Add SRI hash after upgrade (generate via https://www.srihash.org/)

### References

- Business case: `LegacyBusinessCase.docx` §Maintainability
- Copilot instructions: `.github/copilot-instructions.md` (external assets over HTTP)
