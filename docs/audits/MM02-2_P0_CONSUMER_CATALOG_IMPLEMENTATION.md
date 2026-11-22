# MM-02.2 P0 — Consumer Material Catalog

**Project:** Enterprise BPM Laravel
**Module:** MM-05 Master Material
**Date:** 9 November 2026
**Scope:** Read-only consumer catalog and focused regression tests

## Summary

The existing variants page now supports catalog search by material code/name, SKU, variant name, and stored specification values. It supports category and material filters, shows saved typed specification values with their definition units, and uses pagination that preserves active query filters.

Users without the Procurement manage role see only active variants whose parent material is active. The page renders their specifications and status without edit controls. Procurement retains the existing variant management forms and can still see inactive records. Role definitions, routes, services, and database schema were not changed.

## Files changed

1. `app/Http/Controllers/MmSpecificationController.php`
   - Validates query filters and search input.
   - Filters by material category or material ID.
   - Searches material code/name, SKU, variant name, and text/numeric/boolean specification values using bound query values.
   - Restricts non-managers to active variants with active parent materials.
   - Eager-loads category, base unit, attribute definition, and definition unit; paginates while retaining the query string.
2. `resources/views/mm05/variants.blade.php`
   - Adds search, category, and material controls on the existing variants page.
   - Shows all persisted typed specification values and their units to read-only roles as well as Procurement.
   - Keeps mutation forms conditional on the existing `canManage` authorization result.
3. `tests/Feature/MmSpecificationTest.php`
   - Adds coverage for material/SKU/variant-name search, category/material filters, specification-value search, complete typed-value display, and exclusion of inactive catalog records.
   - Extends read-only role coverage to assert specifications are visible without mutation links.
   - Existing Procurement management and direct-mutation authorization cases remain in place.
4. `docs/audits/MM02-2_P0_CONSUMER_CATALOG_IMPLEMENTATION.md`
   - Records this implementation and verification.

No changes were made to `MmWorkspaceController`, `MmVariantService`, `MmAuthorization`, `materials.blade.php`, routes, migrations, SJR, HPP, supplier, pricing, or production workflow files.

## Behavior and authorization

- Search is case-insensitive for material code/name, SKU, and variant name; it also checks stored text and numeric values. Boolean values can be searched using common Indonesian/English true/false terms.
- Category and material filters use existing relationships and IDs.
- Read-only users, including `ASST_MANAGER_PRODUKSI`, `ADMIN_BPM`, and `ADMIN_PRODUKSI`, see active catalog records and all values saved for those variants, including values whose attribute definition is now inactive.
- `PROCUREMENT` remains the only role with management controls. Existing server-side manage checks continue to protect direct HTTP mutations.
- `MARKETING` and `DESIGNER` remain denied by the existing authorization service.
- No price or supplier data is displayed or introduced.

## Backups

Backups were made before editing, outside the repository:

`/Users/bayusuwardani/.copilot/session-state/2ee2d129-861d-4117-8c00-08b4541bcede/files/mm02-2-p0-backup-20261109`

Verified backup SHA-256 checksums:

| Backup file | SHA-256 |
|---|---|
| `MmSpecificationController.php` | `1ef0b3971566acb8f7892ffb5323515d589099c8f176968d710087f947c884ee` |
| `variants.blade.php` | `f0f5011abc78df3ac0a11485769cb560fbb5aed0afa766b3af5c209a37856112` |
| `MmSpecificationTest.php` | `8d39c42ec46d73d21541f2f37bf8d12089481af6af6e805453381370c5efef1f` |

## Verification

The focused command was run twice, including after formatting:

```text
php artisan test --compact tests/Feature/MmSpecificationTest.php tests/Feature/MmAuthorizationTest.php
PASS — 28 tests, 317 assertions
```

The project test bootstrap and `Tests\TestCase` database guard remained active. The test run verified the configured isolated PostgreSQL target `enterprise_bpm_test`; no active/browser/production database was used.

Additional checks:

- PHP syntax: `MmSpecificationController.php` — passed.
- PHP syntax: `MmSpecificationTest.php` — passed.
- Scoped Pint: only those two changed PHP files — passed.
- `git diff --check` — passed. Since the affected source/test files were untracked in the pre-existing worktree, backup-to-current `git diff --no-index --check` was also run for all three edited files; no whitespace errors were reported.
- Feature requests rendered the changed Blade view successfully.
- No migration or database-schema command was run.

## Risks and remaining limitations

- Search uses PostgreSQL case-insensitive substring matching and checks persisted numeric values by casting them to text. This is correct for the current PostgreSQL test target but may require query/index review if the catalog grows substantially. No schema/index change was in scope.
- No browser-level interaction test was run; endpoint feature tests exercised rendered pages and query results.
- Supplier prices and reference prices remain intentionally absent from this stage.
- The repository was already dirty before this task. Existing unrelated changes were preserved; no reset, restore, cleanup, commit, or push was performed.

## Decision

**MM-02.2 P0 Consumer Material Catalog: PASS.**

The requested consumer search, filters, specification display, active-only read catalog, Procurement management continuity, and role protections are implemented and covered by the focused tests. Work stopped at this scope; no supplier-price or follow-on feature work was started.
