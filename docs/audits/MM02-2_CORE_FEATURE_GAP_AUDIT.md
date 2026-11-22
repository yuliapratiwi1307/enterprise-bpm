# MM-02.2 Master Material Core Feature Gap Audit

**Project:** Enterprise BPM Laravel  
**Module:** MM-05 Master Material  
**Audit type:** Read-only source and test inspection  
**Decision:** **CORE INCOMPLETE**

## 1. Executive Summary

MM-05 already provides the principal Procurement workflows for maintaining units, categories, materials, attribute definitions, variants/SKUs, and typed specification values. The inspected services enforce uniqueness, active-reference checks, category/type validation, and the Parent Lock / category consistency protections. Feature tests cover important CRUD, validation, authorization, preservation, and concurrency cases.

The core consumer workflow is not complete. An authenticated Asst. Manager Produksi can open the same master-data workspaces in read-only mode, but there is no dedicated catalog flow for searching and selecting a material variant by its specification. The variant page is paginated without search/filter controls, and its Blade branch for read-only roles displays “Hanya baca” instead of the saved attribute values. No inspected MM view/controller displays a material reference price. The dashboard’s supplier, quote, and price links are placeholders.

These are gaps in the approved core use case—searching and choosing a reusable material based on its specifications—not a recommendation to add purchasing calculations or unrelated ERP features. Supplier CRUD, quote CRUD, price CRUD, waste, purchase-quantity calculations, HPP, BOM, and cross-module production logic remain outside MM-02.2 core.

## 2. Kondisi Aktual Master Material

Routes for the MM workspace are grouped under `auth` in `routes/web.php`. Role authorization is enforced in controllers through `MmAuthorization`, rather than by a separate role-specific route group. The route set exposes the dashboard, materials, categories, units, attribute definitions, variants, and variant-attribute updates.

The relevant implementation is in:

- `MmWorkspaceController::index()` and `materials()` for dashboard and material listing.
- `MmMasterDataController` and `MmMasterDataService` for category/unit/material maintenance.
- `MmSpecificationController` and `MmAttributeDefinitionService` for attribute definitions.
- `MmSpecificationController::variants()` and `MmVariantService` for variants and typed specification values.
- `MmAuthorization` for read/manage role checks.
- `2026_11_05_130000_create_mm_material_master_foundation.php` for the MM tables and database constraints.

The inspected schema includes foreign-key relationships and uniqueness constraints for master codes/SKU and variant-definition pairs. Service validation additionally checks active references and enforces that specification definitions match the parent material’s category and the submitted value’s data type. The current service source also contains the previously implemented Parent Lock and category-update concurrency protections; this audit did not modify or retest them.

## 3. Fitur yang Sudah Berfungsi

### Procurement

- Create, update, and deactivate unit and category records.
- Create, update, and deactivate materials.
- Search material records by code/name and filter by status on the materials page.
- Create, update, and deactivate category-specific attribute definitions, including type, optional unit, required flag, and sort order.
- Create, update, and deactivate material variants/SKUs.
- Enter and update text, numeric, and boolean values for a variant’s category definitions.
- Preserve specification values on variant updates and retain records when deactivating rather than deleting them.
- Enforce duplicate SKU/code and invalid/inactive-reference validation in application code and database constraints.
- Reject cross-category attribute values, invalid typed values, and missing required values.
- Reject re-parent/category changes where the implemented Parent Lock or saved specification constraints apply.

### Read-only roles and authorization

- `MmAuthorization` grants read access to `ADMIN_BPM`, `PROCUREMENT`, `ASST_MANAGER_PRODUKSI`, and `ADMIN_PRODUKSI`; only `PROCUREMENT` can manage.
- Controllers call the shared authorization checks on MM pages and mutation actions.
- Feature tests exercise forbidden direct mutation requests for read-only/unlisted roles and unauthenticated redirects.
- Role access is read-only at the HTTP/controller boundary, not merely by hiding form controls in the UI.

## 4. Fitur yang Belum Lengkap

- **Material search is not a consumer selection flow.** The materials page searches code/name, but it does not offer a guided material-to-variant selection experience based on specification values.
- **Variants are not searchable/filterable.** `MmSpecificationController::variants()` supplies a paginated list; the inspected controller/view has no query or filter controls for narrowing variants by material, category, SKU, or specification.
- **Saved specification values are not readable to consumer roles in the variant list.** The view renders the typed fields in the Procurement manage branch. For non-managers, it renders only the read-only notice, so Asst. Manager Produksi cannot compare saved variant values there.
- **Reference price is not presented.** The inspected dashboard, material, and variant views do not show a price by material/variant. `MmMaterialPriceService` and its model exist, but no price display/read workflow was found in the inspected MM controllers/views.
- **No availability-oriented consumer catalog.** The general lists display active status and can include inactive records; there is no dedicated active-only result set for a consumer selection flow.
- **Dashboard links for supplier/quote/price work are placeholders.** The workspace view describes those areas as later integration; this is not a functioning CRUD or price-history UI.

## 5. Fitur yang Belum Tersedia

- A consumer-facing catalog flow that finds a material and selects a specific variant based on its saved specifications.
- A read-only presentation of variant specification values for Asst. Manager Produksi.
- A read-only reference-price presentation in the inspected MM interface.
- An MM-to-SJR material selection integration was not found in inspected application/controller/service/view references. This audit therefore does not claim that SJR can select MM materials.

## 6. Hasil Pemeriksaan Procurement Workspace

The Procurement workflow is present for master-data maintenance:

1. **Open Master Material:** dashboard route/view exists.
2. **Create category and unit:** create/update/deactivate routes, controller actions, and service methods exist.
3. **Create material:** material CRUD is available, with category/reference validation.
4. **Define attributes:** definitions are category-scoped and support text, number, and boolean values, optional units, required flags, and ordering.
5. **Create variant/SKU:** variant create/update/deactivate actions exist and validate material/unit references and unique SKU.
6. **Enter specification values:** a category-specific form posts typed values; service validation checks category, type, required rules, and duplicate submitted definitions.
7. **Update/deactivate data:** update and non-destructive deactivate paths exist. Tests inspect that values remain after variant changes/deactivation.

The path is a set of functional management screens rather than a guided workflow. The dashboard’s supplier, quote, and price entries are placeholders, not available Procurement capabilities.

## 7. Hasil Pemeriksaan Asst. Manager Produksi

The role can open the MM dashboard, materials, specifications, and variants pages as read-only, subject to the shared authorization service. It can therefore inspect the material master and definitions, but the current experience does not satisfy the requested selection flow:

1. The materials page can search material code/name, but does not choose a variant.
2. The variant page has no search/filter controls for finding a matching SKU or specification.
3. The read-only branch on the variant page does not render stored attribute values.
4. The inspected views do not present reference prices.

Thus, the role has read permission but not a usable specification-led catalog workflow. The existence of read routes does not make the user journey complete.

## 8. Authorization Assessment

`MmAuthorization` separates read and manage capabilities. `PROCUREMENT` is the only manage role. `ADMIN_BPM`, `ASST_MANAGER_PRODUKSI`, and `ADMIN_PRODUKSI` are read-only; unlisted roles such as `MARKETING` and `DESIGNER` are denied. Route authentication redirects unauthenticated requests to login, while controller checks return forbidden responses for unauthorized access.

Evidence inspected:

- `tests/Feature/MmAuthorizationTest.php`: read pages without mutation controls, direct mutation denial, unauthenticated/unlisted role denial, and Procurement access.
- `tests/Feature/MmSpecificationTest.php`: read-only access and forbidden direct mutation for `ADMIN_BPM`, `ADMIN_PRODUKSI`, and `ASST_MANAGER_PRODUKSI`; forbidden access for `MARKETING` and `DESIGNER`.
- `app/Services/MmAuthorization.php` and the MM controller authorization calls.

Authorization is assessed as implemented and tested for the inspected boundaries. The gap is in what the permitted read-only catalog page presents, not in granting those users mutation rights.

## 9. Functional Gap Matrix

Status legend: **A** implemented and tested; **B** implemented but incomplete; **C** UI/placeholder only; **D** not available; **E** not required in MM-02.2 core.

| Fitur | Status | Kondisi aktual | Bukti source/test | Kekurangan | Prioritas |
|---|---|---|---|---|---|
| Master Unit | A | Procurement can create/update/deactivate units; active references are used by definitions and variants. | `MmMasterDataController`, `MmMasterDataService`, `MmAuthorizationTest::test_procurement_can_create_update_and_deactivate_material_categories_and_units` | No selection/catalog UX gap specific to units; no additional unit behavior is inferred as a requirement. | — |
| Material Category | A | Procurement can manage categories; material/definition category changes are guarded when saved specifications constrain them. | `MmMasterDataService`, `MmAttributeDefinitionService`, Parent Lock/category regression tests | Core administration exists. | — |
| Material Master | A | CRUD, active status, code/name search, and status filter are implemented. | `MmWorkspaceController::materials`, `MmMasterDataService`, `MmAuthorizationTest` | Search returns materials, not matching selectable variants/specification profiles. | P0 |
| Attribute Definition | A | Category-scoped definitions support typed values, optional units, required state, and ordering. | `MmAttributeDefinitionService`, `MmSpecificationTest::test_procurement_can_create_update_and_deactivate_attribute_definitions` | No consumer presentation of definitions and corresponding saved values. | P0 |
| Material Variant / SKU | A | Procurement can create/update/deactivate variants with unique SKU and active reference checks. | `MmVariantService`, `MmSpecificationTest::test_procurement_can_manage_variants_and_preserve_values_on_variant_update` | Variant list is paginated but not searchable/filterable for catalog selection. | P0 |
| Variant Specification Values | A | Typed values are validated and persisted; category/type/required failures are covered. | `MmVariantService::setAttributes`, `MmSpecificationTest::test_variant_attribute_values_enforce_type_category_and_required_rules` | Stored values are not rendered in the read-only branch for Asst. Manager Produksi. | P0 |
| Search and selection | B | Material search exists for code/name; there is no variant-by-specification selection flow. | `MmWorkspaceController::materials`, `MmSpecificationController::variants`, `materials.blade.php`, `variants.blade.php` | Cannot search and choose the correct reusable variant using its specification values. | P0 |
| Procurement workspace | B | Master-data screens work; supplier/quote/price dashboard entries are placeholders. | `MmWorkspaceController::index`, `resources/views/mm05/index.blade.php` | Master-data workflow is not a guided catalog lifecycle; placeholder entries are not functioning features. | P2 |
| Read-only workspace | B | Read access and mutation restrictions work; common management screens are reused. | `MmAuthorization`, `MmSpecificationTest::test_read_only_roles_can_view_but_direct_mutations_are_forbidden` | No consumer-ready catalog; saved variant specifications are hidden from read-only roles. | P0 |
| Reference price view | D | Price model/service are present; no inspected MM page/controller reads and displays reference price. | `MmMaterialPriceService`, `MmMaterialPrice` model; inspected MM controllers/views | Approved flow asks users to view reference price when available. Define which eligible price is shown before adding a read-only display; do not add price CRUD to core. | P1 |
| Integritas data dan validasi | A | Database relationships/unique constraints and service validations enforce references, uniqueness, category, and typed values; concurrency safeguards exist in current source. | MM foundation migration, `MmMasterDataService`, `MmAttributeDefinitionService`, `MmVariantService`, specification/concurrency tests | Existing automated tests do not cover the missing consumer selection/read presentation. | — |
| MM/SJR integration | D | No MM material/variant selection reference was found in inspected SJR/application integration source. | Search of inspected `app` and SJR view references; no MM integration hit outside MM implementation | No claim that an SJR workflow can select an MM variant; integrate only after catalog acceptance and separate scope approval. | P1 |
| Supplier CRUD / Supplier Quote CRUD | E | Services/models are present, but not part of Master Material Core implementation. | `MmSupplierQuoteService`, related models; dashboard placeholders | Explicitly excluded from MM-02.2 core; do not implement here. | OUT |
| Material Price CRUD | E | Price model/service are present; CRUD is not part of the core catalog remediation. | `MmMaterialPriceService`, `MmMaterialPrice` | Read-only price presentation is a separate core gap; CRUD remains excluded. | OUT |
| Waste, purchase quantity, conversion, HPP, BOM | E | These are not Master Material Core responsibilities under the approved business principles. | Approved business boundary in audit request | Keep requirements in HPP/BOM or their separately approved modules. | OUT |
| Cross-module production logic | E | No need to add calculations or production behavior to the central master-data module. | Approved business boundary in audit request | Defer integration logic to its owning module and explicit scope. | OUT |

## 10. Daftar Pekerjaan yang Benar-benar Diperlukan

1. Provide an authenticated, read-only consumer catalog experience for `ASST_MANAGER_PRODUKSI` (and only other roles already authorized by the agreed matrix).
2. Support searching/filtering active materials and variants, including specification values that users need to compare; do not expose inactive records as selectable results.
3. Render the saved typed specification values and units in the consumer-facing variant result/detail so users can make a specification-based selection.
4. Decide the reference-price selection rule with the business owner (for example, what makes a price eligible/current and how ties or expired records are handled). Then expose only the read-only price data needed by the approved flow. Do not add price CRUD or assume “latest record” means “reference price.”
5. Add focused feature coverage for consumer search/result selection, read-only specification display, active-only selection, role boundaries, and reference-price display once its rule is approved.
6. Treat MM/SJR integration as a subsequent explicit acceptance gate; do not implement HPP, waste, purchase quantities, BOM, supplier maintenance, or pricing maintenance as part of this gap closure.

## 11. Rekomendasi Urutan Implementasi

1. Confirm the catalog selection behavior and reference-price eligibility/display rule with the project owner.
2. Implement the smallest read-only catalog query/presentation using existing MM entities and authorization; keep Procurement management screens intact.
3. Add variant search/filter and clear display of active state plus typed specification values/units.
4. Add a read-only price reference only after its business rule is approved; leave supplier/quote/price write workflows out of scope.
5. Add focused isolated-database tests for the consumer flow and role restrictions, retaining the existing database guard.
6. Review acceptance with Asst. Manager Produksi before considering SJR integration.

## 12. Risiko dan Dependensi

- **Core usability blocker:** users who need to select material by specification cannot complete that workflow from current screens.
- **Information visibility:** read-only users cannot see the persisted values needed to distinguish variants.
- **Price ambiguity:** a price entity/service alone does not define which price should be shown as a reference. A display implementation without an approved eligibility rule could present misleading or stale information.
- **Inactive records:** the current management lists expose active status, but a future selection flow must explicitly exclude or visibly disable inactive materials/variants.
- **Testing evidence:** this audit did not execute tests because the task is read-only and existing feature tests use database transactions. Test source was inspected. The prior reported focused MM suite result of 31 tests / 289 assertions is historical evidence from the preceding F1 verification, not a result rerun for this audit and not evidence for the missing catalog workflow.
- **SJR dependency:** no integration was found in inspected code; do not assume downstream workflows can consume MM records.
- **Scope protection:** HPP, waste, purchase quantity, BOM, supplier/quote CRUD, and price CRUD must remain outside the core catalog change.

## 13. Kesimpulan Kesiapan MM-02.2

### Decision: CORE INCOMPLETE

The Procurement master-data foundation is substantially implemented and tested, and the inspected authorization model correctly distinguishes manage from read-only access. However, the approved core business purpose requires users to search by specification, select a suitable variant, and inspect its information. The current consumer role cannot see saved variant specification values and has no variant selection/search flow. Reference-price viewing is also absent despite being part of the approved consumer journey when a reference exists.

MM-02.2 should therefore be scoped to closing the catalog/search/read-only presentation gaps and defining the reference-price read rule. This decision does not authorize implementation of the explicitly excluded purchasing, pricing-maintenance, HPP, waste, BOM, or cross-module features.

### Audit safety and Git status

- No application source, routes, permissions, migrations, or database records were changed by this audit.
- No migration or test command was run; no browser/production database was accessed or mutated.
- The pre-report Git status was already dirty on branch `main`, with unrelated tracked modifications and many untracked files. Those pre-existing changes were preserved.
- The only intended file change for this task is this audit report.
