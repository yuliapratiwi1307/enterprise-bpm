<?php

namespace App\Http\Controllers;

use App\Models\MmAttributeDefinition;
use App\Models\MmCategory;
use App\Models\MmSpecificationTemplate;
use App\Models\MmSpecificationTemplateAttribute;
use App\Models\MmUnit;
use App\Services\MmAuthorization;
use App\Services\MmSafety;
use App\Services\MmSpecificationTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MmSpecificationTemplateController extends Controller
{
    public function index(Request $request): View
    {
        MmAuthorization::authorizeManage($request);
        MmSafety::check();

        $categories = MmCategory::query()->where('is_active', true)->orderBy('name')->get();
        $templates = MmSpecificationTemplate::query()
            ->with(['category', 'templateAttributes.attributeDefinition.unit'])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $availableDefinitionsByCategory = MmAttributeDefinition::query()
            ->with('unit')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->groupBy('category_id');
        $units = MmUnit::query()
            ->where('is_active', true)
            ->where('is_specification_unit', true)
            ->orderBy('name')
            ->get();

        return view('mm05.templates', compact('templates', 'categories', 'availableDefinitionsByCategory', 'units'));
    }

    public function attributes(Request $request, MmSpecificationTemplate $template): JsonResponse
    {
        MmAuthorization::authorizeManage($request);
        MmSafety::check($template, 'mm_specification_templates');

        $validated = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('mm_categories', 'id')],
        ]);

        if (! $template->is_active || (int) $validated['category_id'] !== (int) $template->category_id) {
            throw ValidationException::withMessages([
                'specification_template_id' => 'Template tidak aktif atau tidak sesuai dengan kategori yang dipilih.',
            ]);
        }

        $template->load(['templateAttributes.attributeDefinition.unit', 'templateAttributes.unit']);
        $attributes = $template->templateAttributes->map(function (MmSpecificationTemplateAttribute $assignment) use ($template): array {
            $definition = $assignment->attributeDefinition;

            if (
                $definition === null
                || ! $definition->is_active
                || (int) $definition->category_id !== (int) $template->category_id
                || ! in_array($assignment->input_type, ['text', 'number', 'boolean'], true)
            ) {
                throw ValidationException::withMessages([
                    'specification_template_id' => 'Template memiliki definisi atribut yang tidak aktif atau konfigurasinya tidak valid.',
                ]);
            }

            $unitId = $assignment->resolvedUnitId();

            return [
                'id' => (int) $definition->id,
                'label' => $definition->label,
                'input_type' => $assignment->input_type,
                'is_required' => $assignment->is_required,
                'display_order' => $assignment->display_order,
                'unit_id' => $unitId === null ? null : (int) $unitId,
                'unit' => $assignment->hasExplicitUnitPreference()
                    ? $assignment->unit?->name
                    : $definition->unit?->name,
                'option_values' => $assignment->option_values ?? [],
            ];
        })->values();

        return response()->json([
            'template_id' => (int) $template->id,
            'category_id' => (int) $template->category_id,
            'attributes' => $attributes,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, MmSpecificationTemplateService $service): RedirectResponse
    {
        MmAuthorization::authorizeManage($request);
        $validated = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('mm_categories', 'id')],
            'code' => ['prohibited'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
        ]);

        $template = $service->createTemplate($validated);

        return redirect()->route('mm05.templates.index')
            ->with('message', "Template spesifikasi {$template->code} berhasil dibuat.");
    }

    public function update(Request $request, MmSpecificationTemplate $template, MmSpecificationTemplateService $service): RedirectResponse
    {
        MmAuthorization::authorizeManage($request);
        $validated = $request->validate([
            'category_id' => ['sometimes', 'required', 'integer', Rule::exists('mm_categories', 'id')],
            'code' => ['prohibited'],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string'],
            'attributes' => ['sometimes', 'array'],
            'attributes.*' => ['required', 'array:id,is_required,display_order,input_type,unit_id'],
            'attributes.*.id' => ['required', 'integer', 'distinct'],
            'attributes.*.is_required' => ['required', 'boolean'],
            'attributes.*.display_order' => ['required', 'integer', 'distinct', 'min:1', 'max:65535'],
            'attributes.*.input_type' => ['required', 'string', Rule::in(['text', 'number', 'boolean'])],
            'attributes.*.unit_id' => ['nullable', 'integer', Rule::exists('mm_units', 'id')],
        ]);

        $service->updateTemplate($template, $validated);

        return redirect()->route('mm05.templates.index')
            ->with('message', 'Template spesifikasi berhasil diperbarui.');
    }

    public function deactivate(Request $request, MmSpecificationTemplate $template, MmSpecificationTemplateService $service): RedirectResponse
    {
        MmAuthorization::authorizeManage($request);
        $service->deactivateTemplate($template);

        return redirect()->route('mm05.templates.index')->with('message', 'Template dinonaktifkan. Material lama tetap menggunakan data historis.');
    }

    public function destroy(
        Request $request,
        MmSpecificationTemplate $template,
        MmSpecificationTemplateService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);
        $service->deleteTemplate($template);

        return redirect()->route('mm05.templates.index')->with('message', 'Template spesifikasi berhasil dihapus.');
    }

    public function activate(Request $request, MmSpecificationTemplate $template, MmSpecificationTemplateService $service): RedirectResponse
    {
        MmAuthorization::authorizeManage($request);
        $service->activateTemplate($template);

        return redirect()->route('mm05.templates.index')->with('message', 'Template diaktifkan kembali.');
    }

    public function attachAttribute(
        Request $request,
        MmSpecificationTemplate $template,
        MmSpecificationTemplateService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);
        $validated = $request->validate([
            'attribute_definition_id' => ['required', 'integer', Rule::exists('mm_attribute_definitions', 'id')],
            'is_required' => ['sometimes', 'boolean'],
            'display_order' => ['prohibited'],
            'input_type' => ['sometimes', 'string', Rule::in(['text', 'number', 'boolean'])],
            'unit_id' => ['nullable', 'integer', Rule::exists('mm_units', 'id')],
            'option_values' => ['nullable', 'array'],
        ]);

        $service->attachAttribute($template, $validated);

        return redirect()->route('mm05.templates.index', ['edit_template' => $template->id])
            ->with('message', 'Atribut ditambahkan ke template.');
    }

    public function updateAttribute(
        Request $request,
        MmSpecificationTemplate $template,
        MmSpecificationTemplateAttribute $attribute,
        MmSpecificationTemplateService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);
        $request->validate([
            'is_required' => ['sometimes', 'boolean'],
            'display_order' => ['prohibited'],
            'input_type' => ['sometimes', 'string', Rule::in(['text', 'number', 'boolean'])],
            'unit_id' => ['nullable', 'integer', Rule::exists('mm_units', 'id')],
            'option_values' => ['nullable', 'array'],
        ]);

        $service->updateAttribute(
            $template,
            $attribute,
            $request->only(['is_required', 'display_order', 'input_type', 'unit_id', 'option_values']),
        );

        return redirect()->route('mm05.templates.index', ['edit_template' => $template->id])
            ->with('message', 'Pengaturan atribut template berhasil diubah.');
    }

    public function detachAttribute(
        Request $request,
        MmSpecificationTemplate $template,
        MmSpecificationTemplateAttribute $attribute,
        MmSpecificationTemplateService $service,
    ): RedirectResponse {
        MmAuthorization::authorizeManage($request);
        $service->detachAttribute($template, $attribute);

        return redirect()->route('mm05.templates.index', ['edit_template' => $template->id])
            ->with('message', 'Atribut dihapus dari template. Definisi master tetap tersedia.');
    }
}
