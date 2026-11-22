# Security Review: UI-03B.2 Client & Contact

**Review date:** 2026-10-26
**Scope:** Source-only review of the Client & Contact migration, `ClientBrief`,
`Prod02Controller`, and related duplicate-check, audit, and PROD-03 integration.

## Findings

| # | Severity | Location | Finding | Confidence |
|---|---|---|---|---:|
| 1 | HIGH | `resources/views/prod03/briefs/form.blade.php:6`, `resources/views/prod03/briefs/show.blade.php:6`, rendered through `resources/views/layouts/app.blade.php:77` | Stored cross-site scripting: the dynamic page description concatenates `client_name`, and the layout renders the section using unescaped `@yield`. A Marketing user can store HTML in `client_name`, which may execute when another Marketing user opens the relevant PROD-03 page. This is an existing integration issue, not caused by the new migration columns. | 9/10 |

## Migration and compatibility checklist

- **PASS — Additive nullable schema:** `2026_10_26_071500_add_client_contact_fields_to_client_briefs.php`
  adds `client_type` varchar(100), `client_address` text,
  `client_position` varchar(255), `client_email` varchar(255),
  `client_phone` varchar(50), and `client_notes` text, all nullable. `up()`
  does not alter or remove existing columns.
- **PASS — Model and validation:** `ClientBrief::$fillable` contains all six
  fields. `Prod02Controller` validates nullable strings with limits matching
  each column; email uses `email|max:255`.
- **PASS — Step 1 preservation:** the Step 2 update fills the model using only
  validated submitted fields. Project Information fields omitted by the Step 2
  form are not cleared.
- **PASS — Existing workflow:** official submission still requires
  `client_name`, `communication_source`, `requirement_type`, and `description`.
  Duplicate-check inputs and audit behavior are unchanged. PROD-03 continues
  to consume the existing `ClientBrief` relationship and fields.
- **WARNING — Rollback loses new data:** `down()` drops all six columns and
  permanently removes values stored in them. Do not roll back after users have
  entered these fields unless the data has been separately preserved.
- **WARNING — Schema precondition:** the migration has no existing-column
  guard. It assumes all six names are absent. Source migrations show no
  conflicting declarations, but the live schema was not checked.

## Recommendation

**Migration: GO, conditional on a separate preflight** confirming the intended
database and that all six columns are absent. This review is based on source
only and does not establish the state of any database.

**Feature rollout: address the HIGH stored-XSS finding** in the PROD-03
descriptions before treating the integrated workflow as security-reviewed.
That issue is separate from the schema migration.

## Review limitations

No `.env` file was read, no database was accessed or modified, and no migration,
seed, rollback, test, or commit operation was run as part of the review itself.
