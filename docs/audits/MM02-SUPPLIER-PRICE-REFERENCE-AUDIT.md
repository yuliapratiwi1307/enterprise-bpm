# MM-02 Supplier Price Reference Audit

**Project:** Enterprise BPM Laravel
**Module:** MM-05 Master Material
**Audit date:** 9 November 2026
**Mode:** Read-only source, migration, model, service, and test inspection

## 1. Executive conclusion

The existing schema has the required basic relationship chain from material variant/SKU to supplier quotes and reference-price records. It stores amounts, currency, quote validity, and price effective dates, and has foreign keys, positive-amount checks, date-range checks, and lookup indexes.

**The existing structure is not sufficient to enforce the approved supplier-price selection rules.** A supplier quote has no active/inactive field, no approval state, and no effective-from date. The material-price table has effective dates and an optional source quote, but it does not itself record supplier approval or active state. The services only record validated rows; neither selects a current/latest valid supplier offer. No pricing selection query or pricing behavior test was found.

The relationship structure can be reused; the minimum future work is to represent quote active/approval state, establish which date is the effective-date authority, provide a read-only eligible-price selector with tie results, and display it in the existing consumer catalog. This audit did not implement those changes.

## 2. Scope and evidence

Inspected:

- `app/Services/MmSupplierQuoteService.php`
- `app/Services/MmMaterialPriceService.php`
- Models `MmSupplier`, `MmSupplierQuote`, `MmMaterialPrice`, `MmMaterialVariant`, and `MmMaterial`
- `database/migrations/2026_11_05_130000_create_mm_material_master_foundation.php`
- `tests/Feature/MmSpecificationTest.php` and test references to quote/price records
- Existing P0 consumer catalog implementation report

The “database structure” below means the schema defined by the checked-in migration. No database connection or live database state was queried. No tests or migrations were run. `.env` was not read.

## 3. Database and model condition

The MM foundation migration defines:

### `mm_supplier_quotes`

- `material_variant_id`: required FK to `mm_material_variants`
- `supplier_id`: required FK to `mm_suppliers`
- `price_unit_id`: required FK to `mm_units`
- `amount`: `decimal(20,4)`, positive database check
- `currency`: `char(3)`, default `IDR`
- `quoted_on`: required date
- `valid_until`: nullable date; if present, must be on/after `quoted_on`
- `supplier_reference`, `source_document_path`, `notes`, `recorded_by`
- Indexes on `(material_variant_id, quoted_on)` and `(supplier_id, quoted_on)`
- **No quote `is_active`, approval status, approval timestamp/actor, or `effective_from` field**

### `mm_material_prices`

- `material_variant_id`: required FK to `mm_material_variants`
- `price_unit_id`: required FK to `mm_units`
- `source_quote_id`: nullable FK to `mm_supplier_quotes`
- `price_type`: string, default `reference`
- `amount`: `decimal(20,4)`, positive database check
- `currency`: `char(3)`, default `IDR`
- `effective_from`: required date
- `effective_until`: nullable date; if present, must be on/after `effective_from`
- `source_name`, `change_reason`, `recorded_by`
- Index on `(material_variant_id, price_type, effective_from)`
- **No material-price active flag or approval state**

The migration uses `RESTRICT` on deletes for quote/price relationships. It does not impose uniqueness on variant/date, supplier/date, or source quote, so multiple same-date supplier offers are representable. It does not itself guarantee that the price row’s variant, unit, and currency remain consistent with a referenced quote; `MmMaterialPriceService` performs those checks when a source quote is supplied.

## 4. Relationship map

```text
MmMaterial
  └── hasMany variants
        MmMaterialVariant
          ├── hasMany supplierQuotes
          │     └── belongsTo supplier
          │     └── belongsTo priceUnit
          │     └── hasMany materialPrices
          └── hasMany prices
                ├── belongsTo priceUnit
                └── belongsTo sourceQuote (optional)
```

`MmSupplier` has many quotes. `MmSupplierQuote` belongs to one variant, supplier, and unit; it may be the source of multiple `MmMaterialPrice` records. `MmMaterialPrice` belongs to a variant and unit, and may reference a quote. Thus a supplier can be obtained from a price record only through its optional `sourceQuote`; prices with no source quote do not identify a supplier.

## 5. Service behavior

### `MmSupplierQuoteService::recordQuote()`

- Validates variant, supplier, and unit existence; positive amount; three-character currency; `quoted_on`; and optional `valid_until >= quoted_on`.
- In a transaction, locks the variant and checks that variant, parent material, supplier, and unit are active at recording time.
- Creates the quote with `recorded_by`.
- Does **not** record or enforce approval, quote active state, a separate effective date, or any latest/current supplier-price selection.
- Active supplier/variant/material checks at insert time do not ensure they remain active later.

### `MmMaterialPriceService::recordPrice()`

- Validates variant, unit, optional source quote, amount, currency, `price_type`, and effective date range.
- Locks the variant and checks active variant, parent material, and unit.
- If a quote is supplied, checks matching variant, unit, and currency.
- Does **not** require a source quote, constrain `price_type` to `reference`, check quote expiration, supplier status, approval, or quote active state.
- Does **not** choose a latest row or prevent overlapping effective periods or same-date rows.

Neither service is a read/query selector. They record data only.

## 6. Approved business-rule assessment

| Rule | Existing support | Result |
|---|---|---|
| 1. Quote must be active and approved | Supplier has `is_active`, but quote has no active field and no approval state. Supplier activity is not a substitute for quote approval/activity and is checked only when recording a quote. | **Not enforceable as written.** Add quote-level state and an authorized way to set approval. |
| 2. Quote must not be expired | Quote has nullable `valid_until`; material price has nullable `effective_until`. The record services validate date ordering, but there is no read selector applying “not expired” at the date of catalog display. Null expiry semantics are not codified in a selector. | **Fields exist, rule not implemented.** Define null as no expiry (if approved) and apply both relevant date windows consistently. |
| 3. Select latest effective date | `MmMaterialPrice` has `effective_from`; quote has only `quoted_on`. No service/query orders and chooses eligible offers by effective date. | **Partial data support only.** Prefer `effective_from` from a linked reference-price row, or approve `quoted_on` as the effective-date surrogate. |
| 4. Same effective date means show supplier choices | Multiple rows can exist and the optional `source_quote_id` can lead to supplier. No query groups ties or catalog view presents multiple suppliers. Unlinked prices cannot identify a supplier. | **Representable but not implemented.** Same-date eligible records must be returned as a set, not arbitrarily reduced to one. Require a quote link for supplier prices. |
| 5. No valid quote means “Harga belum tersedia” | No pricing selector or price content exists in the consumer catalog. | **Not implemented.** Catalog must render this explicit empty state when no eligible row exists. |

## 7. Histori and data-consistency assessment

- Quotes and material prices are appendable records; the schema has no update/deactivate/status lifecycle for quotes and no explicit superseded flag.
- `RESTRICT` foreign keys preserve referenced quote, supplier, unit, variant, and price history against deletion.
- Quote and price rows are associated with the variant/SKU, not just the material, which is appropriate for specification-specific references.
- `source_quote_id` is nullable, so a `reference` price can have no supplier provenance.
- A quote can have several derived price records, and a quote can be valid on dates that differ from a price’s effective period. No current rule synchronizes `valid_until` and `effective_until`.
- No uniqueness or exclusion constraint prevents multiple eligible records for one supplier/variant/effective date or overlapping date ranges. Since the approved tie behavior is to show supplier choices, multiple suppliers on one date are legitimate; duplicate/conflicting records for the same supplier still need a clear display/data policy.
- In the inspected tests, quote and price helper methods create fixture records for variant re-parenting/history tests. No focused test was found for quote approval, quote expiry selection, latest effective price, date ties, supplier display, or the “Harga belum tersedia” state.

## 8. Minimum gaps to close before catalog display

1. **Represent quote eligibility.** Add quote-level active state and an explicit approval state (at minimum pending/approved/rejected, with inactive handled distinctly). Keep supplier `is_active` separate. Define an authorized approval action/audit provenance; `recorded_by` only identifies who recorded the quote.
2. **Choose the effective-date source.** Prefer `mm_material_prices.effective_from/effective_until` for the effective period of a supplier reference price, with a required `source_quote_id`. Alternatively, if the business intends the quote itself to be the price record, add an explicit effective-from date to quote and document that authority. Do not silently equate `quoted_on` with “effective date.”
3. **Implement one read selector.** For a variant, find records that are active, approved, not expired as of the selected current date, have active supplier/variant/parent material, and match the approved reference-price type and currency/unit rules. Select the greatest effective date; return all eligible suppliers at that date.
4. **Define unit/currency presentation.** Different price units or currencies cannot be safely compared or shown as one unqualified amount. Either filter/group choices by price unit and currency or define an approved normalization rule; no conversion should be inferred.
5. **Present results on the existing consumer catalog.** For each variant, show the selected amount, currency, unit, supplier, and effective/valid dates for every tied supplier choice. Render “Harga belum tersedia” when no eligible offer exists. Do not add Supplier CRUD or Pricing CRUD to this display task.
6. **Add focused automated coverage.** Test active+approved gating, expiration boundaries, newest effective date, same-date supplier ties, different units/currencies, inactive supplier/variant/material exclusion, no-valid-price copy, and Procurement/read-only access boundaries.

No schema, source, UI, route, or permission change is made by this audit.

## 9. Recommended minimal implementation boundary and files

The relationships do not need to be redesigned. A minimal follow-up should reuse the existing variant → price → source quote → supplier relationship and existing consumer catalog page, while adding only missing eligibility and selection behavior.

Potential files to change in an approved implementation:

1. **New migration** under `database/migrations/` for quote active/approval metadata; add an explicit quote effective date only if the business confirms quotes, rather than `MmMaterialPrice`, are the effective-price authority.
2. `app/Models/MmSupplierQuote.php` for new eligibility fields/casts/relationships where necessary.
3. `app/Services/MmSupplierQuoteService.php` and a narrowly scoped approval operation/authorized controller endpoint for transitioning a quote to approved/inactive; the existing recorder must not be allowed to self-approve by mass assignment.
4. `app/Services/MmMaterialPriceService.php` or a small dedicated read selector for effective-date, expiry, status, supplier, unit/currency, and same-date tie selection.
5. `app/Http/Controllers/MmSpecificationController.php` to eager-load or request price selections for catalog variants without N+1 queries.
6. `resources/views/mm05/variants.blade.php` to show tied supplier choices and the “Harga belum tersedia” state in the existing catalog.
7. `tests/Feature/MmSpecificationTest.php` or a focused pricing feature test file for the eligibility matrix, tie behavior, empty state, and role access.

This list is a recommendation, not authorization to implement. No supplier/pricing CRUD, role-matrix change, or unrelated production work is proposed.

## 10. Audit safety

- No application source, model, service, migration, route, UI, or permission was changed.
- No migration, database query, test, or write operation was run.
- `.env` was not read.
- No commit or push was performed.
- Git worktree was already dirty before this audit; existing changes were preserved.

## Final conclusion

**Existing relationships are sufficient; the current schema and services need minimal targeted changes before the approved supplier-price reference rules can be implemented.** The key blocker is not the material/variant linkage: it is missing quote eligibility/approval state, an unambiguous effective-date authority, and a selector that returns all suppliers tied at the latest valid date.
