<?php

namespace App\Http\Controllers;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Forms\Services\FormDefinitionService;
use App\Enums\FieldType;
use App\Http\Requests\Forms\FormTemplateRequest;
use App\Models\Campaign;
use App\Models\FormTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormTemplateController extends Controller
{
    public function __construct(private readonly FormDefinitionService $forms) {}

    public function index(): Response
    {
        $this->authorize('viewAny', FormTemplate::class);

        return Inertia::render('forms/index', [
            'templates' => FormTemplate::query()->with('campaign')->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', FormTemplate::class);

        return Inertia::render('forms/edit', [
            'template' => null,
            'campaigns' => Campaign::query()->orderBy('name')->get(['id', 'name']),
            'selectedCampaignId' => $request->integer('campaign_id') ?: null,
            'fieldTypes' => $this->fieldTypes(),
        ]);
    }

    public function store(FormTemplateRequest $request, AuditLogger $audit): RedirectResponse
    {
        $template = $this->forms->create(
            [
                ...$request->safe()->only(['campaign_id', 'name', 'consent_text']),
                'requires_consent' => $request->boolean('requires_consent'),
                'qualification_enabled' => $request->boolean('qualification_enabled'),
                'qualification_rule' => $request->boolean('qualification_enabled') ? ['type' => 'all_yes_no_true'] : null,
            ],
            $request->input('fields', []),
        );

        $audit->record($request->user(), 'form.created', $template);

        return redirect()->route('forms.edit', $template)->with('success', 'Form berhasil dibuat.');
    }

    public function edit(FormTemplate $form): Response
    {
        $this->authorize('update', $form);

        return Inertia::render('forms/edit', [
            'template' => $form->load(['fields', 'campaign']),
            'campaigns' => Campaign::query()->orderBy('name')->get(['id', 'name']),
            'selectedCampaignId' => $form->campaign_id,
            'fieldTypes' => $this->fieldTypes(),
            'locked' => $form->isLocked(),
        ]);
    }

    public function update(FormTemplateRequest $request, FormTemplate $form, AuditLogger $audit): RedirectResponse
    {
        $old = $form->only(['name', 'status', 'version']);
        $form = $this->forms->update(
            $form,
            [
                ...$request->safe()->only(['campaign_id', 'name', 'consent_text']),
                'requires_consent' => $request->boolean('requires_consent'),
                'qualification_enabled' => $request->boolean('qualification_enabled'),
                'qualification_rule' => $request->boolean('qualification_enabled') ? ['type' => 'all_yes_no_true'] : null,
            ],
            $request->input('fields', []),
        );
        $audit->record($request->user(), 'form.updated', $form, $old, $form->only(['name', 'status', 'version']));

        return redirect()->route('forms.edit', $form)->with('success', 'Form diperbarui.');
    }

    public function publish(Request $request, FormTemplate $form, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $form);
        $this->forms->publish($form);
        $audit->record($request->user(), 'form.published', $form);

        return back()->with('success', 'Form diterbitkan.');
    }

    public function version(Request $request, FormTemplate $form, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $form);
        $copy = $this->forms->createNewVersion($form);
        $audit->record($request->user(), 'form.versioned', $copy, ['from' => $form->version], ['to' => $copy->version]);

        return redirect()->route('forms.edit', $copy)->with('success', 'Versi baru dibuat.');
    }

    public function preview(FormTemplate $form): Response
    {
        $this->authorize('view', $form);

        return Inertia::render('forms/preview', [
            'template' => $form->load(['fields', 'campaign']),
        ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function fieldTypes(): array
    {
        return collect(FieldType::cases())
            ->map(fn (FieldType $type) => ['value' => $type->value, 'label' => $type->label()])
            ->values()
            ->all();
    }
}
