@extends('layouts.app')

@section('title', 'Master Template Spesifikasi | Enterprise BPM')

@section('content')
<style>
.template-page { max-width: 1400px; margin: 0 auto; padding: 12px 24px 32px; color: #172033; }
.template-hero, .template-panel { border: 1px solid #dfe7f1; border-radius: 15px; background: #fff; box-shadow: 0 3px 12px rgba(30, 58, 95, .04); }
.template-hero { margin-bottom: 15px; padding: 22px; background: linear-gradient(120deg, #f1f6fd, #fff); }
.template-panel { padding: 18px; }
.template-head, .template-actions, .template-modal-head, .template-modal-footer { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.template-head { flex-wrap: wrap; }
.template-head h1 { margin: 0; color: #172b49; font-size: 25px; }
.template-head p, .template-muted { margin: 7px 0 0; color: #64748b; font-size: 12px; line-height: 1.6; }
.template-btn, .template-icon-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 0; border-radius: 8px; background: #1e3a5f; color: #fff; cursor: pointer; font-size: 12px; font-weight: 700; text-decoration: none; }
.template-btn { min-height: 38px; padding: 9px 13px; }
.template-btn:hover, .template-icon-btn:hover { background: #294d79; color: #fff; }
.template-btn.secondary { background: #e8eef5; color: #1e3a5f; }
.template-btn.secondary:hover { background: #dce6f1; }
.template-btn:disabled, .template-icon-btn:disabled { cursor: wait; opacity: .65; }
.template-icon-btn { width: 35px; height: 35px; padding: 8px; }
.template-icon-btn svg { width: 17px; height: 17px; }
.template-icon-btn.secondary { background: #e8eef5; color: #1e3a5f; }
.template-icon-btn.warning { background: #fff1d2; color: #805700; }
.template-icon-btn.warning:hover { background: #ffe5a8; }
.template-alert { margin: 0 0 14px; padding: 12px 15px; border: 1px solid #b9e2c7; border-radius: 10px; background: #effaf2; color: #17653a; font-size: 13px; }
.template-table-wrap { overflow-x: auto; }
.template-table { width: 100%; min-width: 680px; border-collapse: collapse; }
.template-table th, .template-table td { padding: 13px 12px; border-bottom: 1px solid #edf1f5; text-align: left; vertical-align: middle; font-size: 13px; }
.template-table th { background: #f7f9fc; color: #61728a; font-size: 11px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
.template-table tbody tr:last-child td { border-bottom: 0; }
.template-table tbody tr:hover { background: #fbfdff; }
.template-code { color: #234f82; font-weight: 750; white-space: nowrap; }
.template-name { display: flex; align-items: center; gap: 9px; font-weight: 700; color: #26364b; }
.template-status { display: inline-flex; padding: 4px 8px; border-radius: 999px; background: #e8f6ee; color: #207044; font-size: 10px; font-weight: 750; white-space: nowrap; }
.template-status.inactive { background: #f1f3f6; color: #667085; }
.template-actions { justify-content: flex-start; }
.template-actions form { display: inline-flex; margin: 0; }
.template-empty { padding: 28px 14px !important; color: #64748b; text-align: center !important; }
.template-pagination { padding-top: 15px; }
.template-dialog { width: min(940px, calc(100% - 28px)); max-width: 940px; max-height: 90vh; overflow: auto; padding: 0; border: 0; border-radius: 15px; box-shadow: 0 20px 70px rgba(15, 35, 65, .25); color: #172033; }
.template-dialog::backdrop { background: rgba(15, 35, 65, .5); backdrop-filter: blur(2px); }
.template-dialog.add-dialog { width: min(620px, calc(100% - 28px)); }
.template-modal-head { position: sticky; top: 0; z-index: 1; padding: 17px 21px; border-bottom: 1px solid #e8edf3; background: #fff; }
.template-modal-head h2 { margin: 0; color: #172b49; font-size: 18px; }
.template-modal-head p { margin: 5px 0 0; color: #64748b; font-size: 12px; }
.template-modal-body { padding: 20px 21px; }
.template-modal-footer { position: sticky; bottom: 0; justify-content: flex-end; padding: 14px 21px; border-top: 1px solid #e8edf3; background: #fff; }
.template-close { display: inline-flex; width: 34px; height: 34px; align-items: center; justify-content: center; border: 0; border-radius: 8px; background: #edf2f8; color: #344b68; cursor: pointer; font-size: 21px; }
.template-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 13px; }
.template-field { display: grid; min-width: 0; gap: 6px; }
.template-field.full { grid-column: 1 / -1; }
.template-field label, .template-field > span { color: #40526d; font-size: 12px; font-weight: 700; }
.template-field input, .template-field select, .template-field textarea, .template-attribute-table input, .template-attribute-table select { width: 100%; min-height: 38px; border: 1px solid #ced9e7; border-radius: 8px; padding: 8px 10px; background: #fff; color: #24354b; font: inherit; font-size: 13px; }
.template-field textarea { min-height: 78px; resize: vertical; }
.template-field input:focus, .template-field select:focus, .template-field textarea:focus, .template-attribute-table input:focus, .template-attribute-table select:focus { outline: 3px solid rgba(37, 99, 235, .15); border-color: #3b82f6; }
.template-readonly { display: flex; min-height: 38px; align-items: center; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; color: #234f82; font-size: 13px; font-weight: 750; }
.template-error { margin: 0 0 16px; padding: 12px 14px; border: 1px solid #f1b6b6; border-radius: 9px; background: #fff3f3; color: #922b2b; font-size: 12px; }
.template-error ul { margin: 6px 0 0; padding-left: 19px; }
.template-section-title { margin: 21px 0 10px; color: #26364b; font-size: 14px; font-weight: 800; }
.template-attribute-table { width: 100%; min-width: 850px; border-collapse: collapse; }
.template-attribute-table th, .template-attribute-table td { padding: 9px 8px; border-bottom: 1px solid #edf1f5; text-align: left; vertical-align: middle; font-size: 12px; }
.template-attribute-table th { background: #f7f9fc; color: #61728a; font-size: 10px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
.template-sortable-row { cursor: grab; }
.template-sortable-row:active { cursor: grabbing; }
.template-sortable-row.is-dragging { background: #edf4ff; opacity: .55; }
.template-sortable-row.drag-over td { border-top: 2px solid #3972b8; }
.template-order { display: inline-flex; min-width: 54px; align-items: center; gap: 8px; color: #415975; font-weight: 700; }
.template-drag-handle { display: inline-flex; width: 17px; color: #71839b; cursor: grab; }
.template-drag-handle svg { width: 15px; height: 15px; }
.template-type { display: inline-flex; padding: 5px 8px; border-radius: 7px; background: #edf3fb; color: #234f82; font-size: 11px; font-weight: 700; }
.template-add-attribute { margin-top: 12px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 11px; background: #f9fbfd; }
.template-add-attribute h3 { margin: 0 0 12px; color: #26364b; font-size: 13px; }
.template-add-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1.2fr; gap: 10px; align-items: end; }
.template-add-grid .template-field label { font-size: 11px; }
.template-add-submit { margin-top: 12px; }
.template-danger-icon { background: #fff0f0; color: #a52e2e; }
.template-danger-icon:hover { background: #ffe0e0; color: #8d2020; }
@media (max-width: 760px) {
    .template-page { padding: 10px 14px 24px; }
    .template-hero, .template-panel { padding: 15px; }
    .template-form-grid, .template-add-grid { grid-template-columns: 1fr; }
    .template-field.full { grid-column: auto; }
    .template-modal-head, .template-modal-body, .template-modal-footer { padding-right: 15px; padding-left: 15px; }
}
</style>

<div class="template-page">
    <section class="template-hero">
        <div class="template-head">
            <div>
                <h1>Master Template Spesifikasi</h1>
                <p>Kelola template spesifikasi yang digunakan pada material.</p>
            </div>
            <button type="button" class="template-btn" data-open-dialog="addTemplateModal">
                <span aria-hidden="true">+</span> Tambah Template
            </button>
        </div>
    </section>

    @if(session('message'))
        <div class="template-alert" role="status">{{ session('message') }}</div>
    @endif
    @if($errors->any())
        <div class="template-error" role="alert">
        <strong>Perubahan belum dapat disimpan:</strong>
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        </div>
    @endif

    <section class="template-panel" aria-label="Daftar template spesifikasi">
        <div class="template-table-wrap">
            <table class="template-table" id="template-list">
                <thead>
                    <tr>
                        <th scope="col">Kode</th>
                        <th scope="col">Nama Template</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td class="template-code">{{ $template->code }}</td>
                            <td>
                                <div class="template-name">
                                    <span>{{ $template->name }}</span>
                                    <span class="template-status {{ $template->is_active ? '' : 'inactive' }}">
                                        {{ $template->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                            </td>
                            <td>{{ $template->category?->name ?? '-' }}</td>
                            <td>
                                <div class="template-actions">
                                    <button
                                        type="button"
                                        class="template-icon-btn secondary"
                                        title="Edit template dan atribut"
                                        aria-label="Edit {{ $template->name }}"
                                        data-open-dialog="editTemplate{{ $template->id }}"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"></path>
                                        </svg>
                                    </button>
                                    <form
                                        method="POST"
                                        action="{{ route('mm05.templates.delete', $template) }}"
                                        data-loading-form
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="template-icon-btn template-danger-icon"
                                            title="Hapus template"
                                            aria-label="Hapus {{ $template->name }}"
                                            data-confirm="Hapus template {{ $template->name }}? Penghapusan hanya dapat dilakukan jika belum digunakan oleh material."
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="m19 6-1 14H6L5 6"></path><path d="M10 11v5M14 11v5"></path>
                                            </svg>
                                        </button>
                                    </form>
                                    @if($template->is_active)
                                        <form method="POST" action="{{ route('mm05.templates.deactivate', $template) }}" data-loading-form>
                                            @csrf
                                            <button type="submit" class="template-icon-btn warning" title="Nonaktifkan template" aria-label="Nonaktifkan {{ $template->name }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                                                    <circle cx="12" cy="12" r="9"></circle><path d="M9 9h6v6H9z"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('mm05.templates.activate', $template) }}" data-loading-form>
                                            @csrf
                                            <button type="submit" class="template-icon-btn secondary" title="Aktifkan template" aria-label="Aktifkan {{ $template->name }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <circle cx="12" cy="12" r="9"></circle><path d="m10 8 6 4-6 4z"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="template-empty">Belum ada template spesifikasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="template-pagination">{{ $templates->links() }}</div>
    </section>
</div>

<dialog id="addTemplateModal" class="template-dialog add-dialog">
    <form method="POST" action="{{ route('mm05.templates.store', ['modal' => 'create']) }}" data-loading-form>
        @csrf
        <div class="template-modal-head">
            <div>
                <h2>Tambah Template</h2>
                <p>Kode template dibuat otomatis oleh sistem.</p>
            </div>
            <button type="button" class="template-close" data-close-dialog aria-label="Tutup">×</button>
        </div>
        <div class="template-modal-body">
            @if($errors->any())
                <div class="template-error" role="alert">
                    <strong>Template belum dapat disimpan:</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="template-form-grid">
                <div class="template-field full">
                    <label for="new-template-name">Nama Template *</label>
                    <input id="new-template-name" name="name" maxlength="150" value="{{ old('name') }}" required>
                </div>
                <div class="template-field full">
                    <label for="new-template-category">Kategori *</label>
                    <select id="new-template-category" name="category_id" required>
                        <option value="">Pilih kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="template-field full">
                    <label for="new-template-description">Deskripsi (opsional)</label>
                    <textarea id="new-template-description" name="description" maxlength="5000">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>
        <div class="template-modal-footer">
            <button type="button" class="template-btn secondary" data-close-dialog>Batal</button>
            <button type="submit" class="template-btn" data-loading-label="Menyimpan...">Simpan Template</button>
        </div>
    </form>
</dialog>

@foreach($templates as $template)
    @php
        $editFormId = 'editTemplateForm'.$template->id;
        $isEditOpen = (int) request()->query('edit_template') === (int) $template->id;
    @endphp
    <dialog id="editTemplate{{ $template->id }}" class="template-dialog" data-template-dialog="{{ $template->id }}">
        <div class="template-modal-head">
            <div>
                <h2>Edit Atribut Template</h2>
                <p>{{ $template->name }} · {{ $template->category?->name ?? '-' }}</p>
            </div>
            <button type="button" class="template-close" data-close-dialog aria-label="Tutup">×</button>
        </div>

        <div class="template-modal-body">
            @if($errors->any())
                <div class="template-error" role="alert">
                    <strong>Perubahan belum dapat disimpan:</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                id="{{ $editFormId }}"
                method="POST"
                action="{{ route('mm05.templates.update', ['template' => $template, 'edit_template' => $template->id]) }}"
                data-loading-form
            >
                @csrf
                @method('PUT')
                <div class="template-form-grid">
                    <div class="template-field">
                        <span>Kode</span>
                        <output class="template-readonly">{{ $template->code }}</output>
                    </div>
                    <div class="template-field">
                        <label for="template-name-{{ $template->id }}">Nama Template *</label>
                        <input
                            id="template-name-{{ $template->id }}"
                            name="name"
                            maxlength="150"
                            value="{{ $isEditOpen ? old('name', $template->name) : $template->name }}"
                            required
                        >
                    </div>
                    <div class="template-field full">
                        <label for="template-category-{{ $template->id }}">Kategori *</label>
                        <select id="template-category-{{ $template->id }}" name="category_id" required>
                            @foreach($categories as $category)
                                <option
                                    value="{{ $category->id }}"
                                    @selected((int) ($isEditOpen ? old('category_id', $template->category_id) : $template->category_id) === (int) $category->id)
                                >
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="template-field full">
                        <label for="template-description-{{ $template->id }}">Deskripsi (opsional)</label>
                        <textarea id="template-description-{{ $template->id }}" name="description" maxlength="5000">{{ $isEditOpen ? old('description', $template->description) : $template->description }}</textarea>
                    </div>
                </div>
            </form>

            <h3 class="template-section-title">Atribut Template</h3>
            <div class="template-table-wrap">
                <table class="template-attribute-table">
                    <thead>
                        <tr>
                            <th scope="col">Nama Atribut</th>
                            <th scope="col">Jenis Data</th>
                            <th scope="col">Wajib/Opsional</th>
                            <th scope="col">Urutan</th>
                            <th scope="col">Input Type</th>
                            <th scope="col">Satuan</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody data-sortable-body>
                        @forelse($template->templateAttributes as $assignment)
                            @php
                                $attributeIndex = $loop->index;
                                $resolvedUnitId = $assignment->resolvedUnitId();
                                $resolvedUnit = $assignment->hasExplicitUnitPreference()
                                    ? $assignment->unit
                                    : $assignment->attributeDefinition?->unit;
                                $selectedUnitId = $isEditOpen
                                    ? old("attributes.{$attributeIndex}.unit_id", $resolvedUnitId)
                                    : $resolvedUnitId;
                            @endphp
                            <tr draggable="true" data-sortable-row>
                                <td>
                                    <input type="hidden" form="{{ $editFormId }}" name="attributes[{{ $attributeIndex }}][id]" value="{{ $assignment->id }}">
                                    {{ $assignment->attributeDefinition?->label ?? '-' }}
                                </td>
                                <td>{{ ucfirst($assignment->attributeDefinition?->data_type ?? '-') }}</td>
                                <td>
                                    <select form="{{ $editFormId }}" name="attributes[{{ $attributeIndex }}][is_required]" aria-label="Status wajib {{ $assignment->attributeDefinition?->label }}">
                                        <option value="0" @selected($isEditOpen ? (string) old("attributes.{$attributeIndex}.is_required", $assignment->is_required ? '1' : '0') === '0' : ! $assignment->is_required)>Opsional</option>
                                        <option value="1" @selected($isEditOpen ? (string) old("attributes.{$attributeIndex}.is_required", $assignment->is_required ? '1' : '0') === '1' : $assignment->is_required)>Wajib</option>
                                    </select>
                                </td>
                                <td>
                                    <div class="template-order">
                                        <span class="template-drag-handle" title="Seret untuk mengubah urutan" aria-hidden="true">
                                            <svg viewBox="0 0 16 16" fill="currentColor">
                                                <circle cx="4" cy="3" r="1.2"></circle><circle cx="12" cy="3" r="1.2"></circle>
                                                <circle cx="4" cy="8" r="1.2"></circle><circle cx="12" cy="8" r="1.2"></circle>
                                                <circle cx="4" cy="13" r="1.2"></circle><circle cx="12" cy="13" r="1.2"></circle>
                                            </svg>
                                        </span>
                                        <input
                                            form="{{ $editFormId }}"
                                            type="hidden"
                                            name="attributes[{{ $attributeIndex }}][display_order]"
                                            value="{{ $isEditOpen ? old("attributes.{$attributeIndex}.display_order", $loop->iteration) : $loop->iteration }}"
                                            data-display-order
                                        >
                                        <span data-sortable-position>{{ $isEditOpen ? old("attributes.{$attributeIndex}.display_order", $loop->iteration) : $loop->iteration }}</span>
                                    </div>
                                </td>
                                <td>
                                    <select
                                        form="{{ $editFormId }}"
                                        name="attributes[{{ $attributeIndex }}][input_type]"
                                        aria-label="Input Type {{ $assignment->attributeDefinition?->label }}"
                                        required
                                    >
                                        @foreach(['text' => 'Text', 'number' => 'Number', 'boolean' => 'Boolean'] as $inputTypeValue => $inputTypeLabel)
                                            <option
                                                value="{{ $inputTypeValue }}"
                                                @selected(
                                                    (string) ($isEditOpen
                                                        ? old("attributes.{$attributeIndex}.input_type", $assignment->input_type)
                                                        : $assignment->input_type) === $inputTypeValue
                                                )
                                            >
                                                {{ $inputTypeLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select form="{{ $editFormId }}" name="attributes[{{ $attributeIndex }}][unit_id]" aria-label="Satuan {{ $assignment->attributeDefinition?->label }}">
                                        <option value="" @selected($selectedUnitId === null || $selectedUnitId === '')>Tanpa satuan</option>
                                        @if($resolvedUnit && (! $resolvedUnit->is_active || ! $resolvedUnit->is_specification_unit))
                                            <option
                                                value="{{ $resolvedUnit->id }}"
                                                @selected((int) $selectedUnitId === (int) $resolvedUnit->id)
                                            >
                                                {{ $resolvedUnit->name }} (satuan lama; dipertahankan)
                                            </option>
                                        @endif
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}" @selected((int) $selectedUnitId === (int) $unit->id)>{{ $unit->code }} — {{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <form
                                        id="deleteTemplateAttribute{{ $assignment->id }}"
                                        method="POST"
                                        action="{{ route('mm05.templates.attributes.detach', ['template' => $template, 'attribute' => $assignment, 'edit_template' => $template->id]) }}"
                                        data-loading-form
                                    >
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    <button
                                        type="submit"
                                        form="deleteTemplateAttribute{{ $assignment->id }}"
                                        class="template-icon-btn template-danger-icon"
                                        title="Hapus atribut dari template"
                                        aria-label="Hapus {{ $assignment->attributeDefinition?->label }} dari template"
                                        data-confirm="Hapus atribut ini dari template? Definisi atribut master tidak akan dihapus."
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="m19 6-1 14H6L5 6"></path><path d="M10 11v5M14 11v5"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="template-empty">Belum ada atribut yang dipasang ke template ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <form
                method="POST"
                action="{{ route('mm05.templates.attributes.attach', ['template' => $template, 'edit_template' => $template->id]) }}"
                class="template-add-attribute"
                data-attribute-form
                data-loading-form
            >
                @csrf
                <h3>Tambah Atribut</h3>
                <div class="template-add-grid">
                    <div class="template-field">
                        <label for="new-attribute-{{ $template->id }}">Pilih Atribut</label>
                        <select id="new-attribute-{{ $template->id }}" name="attribute_definition_id" data-definition-select required>
                            <option value="">Pilih atribut</option>
                            @foreach(($availableDefinitionsByCategory[$template->category_id] ?? []) as $definition)
                                @unless($template->templateAttributes->contains('attribute_definition_id', $definition->id))
                                    <option
                                        value="{{ $definition->id }}"
                                        data-type="{{ $definition->data_type }}"
                                        data-unit="{{ $definition->unit_id }}"
                                        @selected((string) old('attribute_definition_id') === (string) $definition->id && $isEditOpen)
                                    >
                                        {{ $definition->label }} ({{ $definition->data_type }})
                                    </option>
                                @endunless
                            @endforeach
                        </select>
                    </div>
                    <div class="template-field">
                        <label for="new-required-{{ $template->id }}">Wajib/Opsional</label>
                        <select id="new-required-{{ $template->id }}" name="is_required">
                            <option value="0" @selected(! $isEditOpen || old('is_required', '0') === '0')>Opsional</option>
                            <option value="1" @selected($isEditOpen && old('is_required') === '1')>Wajib</option>
                        </select>
                    </div>
                    <div class="template-field">
                        <label for="new-input-type-{{ $template->id }}">Input Type</label>
                        <select id="new-input-type-{{ $template->id }}" name="input_type" data-input-type-select disabled required>
                            <option value="">Pilih atribut dahulu</option>
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="boolean">Boolean</option>
                        </select>
                    </div>
                    <div class="template-field full">
                        <label for="new-unit-{{ $template->id }}">Satuan</label>
                        <select id="new-unit-{{ $template->id }}" name="unit_id" data-unit-select disabled>
                            <option value="">Tidak memakai satuan</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="template-add-submit">
                    <button type="submit" class="template-btn secondary" data-loading-label="Menambahkan...">Tambah Atribut</button>
                </div>
            </form>
        </div>

        <div class="template-modal-footer">
            <button type="button" class="template-btn secondary" data-close-dialog>Batal</button>
            <button type="submit" class="template-btn" form="{{ $editFormId }}" data-loading-label="Menyimpan...">Simpan Perubahan</button>
        </div>
    </dialog>
@endforeach

<script>
(() => {
    const updateAddress = (dialog) => {
        const url = new URL(window.location.href);
        if (dialog.id === 'addTemplateModal') {
            url.searchParams.set('modal', 'create');
            url.searchParams.delete('edit_template');
        } else {
            url.searchParams.set('edit_template', dialog.dataset.templateDialog);
            url.searchParams.delete('modal');
        }
        window.history.replaceState(null, '', url);
    };

    const clearModalAddress = () => {
        const url = new URL(window.location.href);
        url.searchParams.delete('modal');
        url.searchParams.delete('edit_template');
        window.history.replaceState(null, '', url);
    };

    document.querySelectorAll('[data-open-dialog]').forEach((button) => {
        button.addEventListener('click', () => {
            const dialog = document.getElementById(button.dataset.openDialog);
            if (dialog && !dialog.open) {
                updateAddress(dialog);
                dialog.showModal();
            }
        });
    });

    document.querySelectorAll('[data-close-dialog]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });

    document.querySelectorAll('dialog').forEach((dialog) => {
        dialog.addEventListener('close', clearModalAddress);
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });
    });

    document.querySelectorAll('[data-attribute-form]').forEach((form) => {
        const definitionSelect = form.querySelector('[data-definition-select]');
        const inputTypeSelect = form.querySelector('[data-input-type-select]');
        const unitSelect = form.querySelector('[data-unit-select]');

        const syncAttributeConfiguration = () => {
            const option = definitionSelect.selectedOptions[0];
            const dataType = option?.dataset.type ?? '';
            inputTypeSelect.value = dataType;
            inputTypeSelect.disabled = dataType === '';
            unitSelect.disabled = dataType === '';
            unitSelect.value = option?.dataset.unit ?? '';
        };

        definitionSelect.addEventListener('change', syncAttributeConfiguration);
        syncAttributeConfiguration();
    });

    document.querySelectorAll('[data-sortable-body]').forEach((body) => {
        const rows = () => Array.from(body.querySelectorAll('[data-sortable-row]'));

        const updatePositions = () => {
            rows().forEach((row, index) => {
                const position = index + 1;
                row.querySelector('[data-display-order]').value = position;
                row.querySelector('[data-sortable-position]').textContent = position;
            });
        };

        const rowAfterPointer = (pointerY) => rows()
            .filter((row) => !row.classList.contains('is-dragging'))
            .reduce((closest, row) => {
                const bounds = row.getBoundingClientRect();
                const offset = pointerY - bounds.top - bounds.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset, element: row };
                }

                return closest;
            }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;

        body.addEventListener('dragstart', (event) => {
            const row = event.target.closest('[data-sortable-row]');
            if (!row) {
                return;
            }

            row.classList.add('is-dragging');
            row.setAttribute('aria-grabbed', 'true');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', 'template-attribute');
        });

        body.addEventListener('dragover', (event) => {
            event.preventDefault();
            const dragging = body.querySelector('.is-dragging');
            if (!dragging) {
                return;
            }

            const target = rowAfterPointer(event.clientY);
            rows().forEach((row) => row.classList.remove('drag-over'));
            if (target) {
                target.classList.add('drag-over');
                body.insertBefore(dragging, target);
            } else {
                body.appendChild(dragging);
            }

            updatePositions();
        });

        body.addEventListener('dragend', (event) => {
            const row = event.target.closest('[data-sortable-row]');
            row?.classList.remove('is-dragging');
            row?.removeAttribute('aria-grabbed');
            rows().forEach((item) => item.classList.remove('drag-over'));
            updatePositions();
        });

        updatePositions();
    });

    document.querySelectorAll('form[data-loading-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const submitter = event.submitter;
            const confirmation = submitter?.dataset.confirm;
            if (confirmation && !window.confirm(confirmation)) {
                event.preventDefault();
                return;
            }

            if (submitter) {
                submitter.disabled = true;
                submitter.textContent = submitter.dataset.loadingLabel ?? 'Menyimpan...';
            }
        });
    });

    const requestedModal = @json(request()->query('modal') === 'create' ? 'addTemplateModal' : null);
    const requestedTemplate = @json((string) request()->query('edit_template', ''));
    const dialogToOpen = requestedModal
        ? document.getElementById(requestedModal)
        : document.getElementById(`editTemplate${requestedTemplate}`);

    if (dialogToOpen && !dialogToOpen.open) {
        dialogToOpen.showModal();
    }
})();
</script>
@endsection
