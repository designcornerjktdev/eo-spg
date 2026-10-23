<?php

namespace App\Http\Controllers;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Forms\Services\FormDefinitionService;
use App\Enums\FieldType;
use App\Enums\FormStatus;
use App\Http\Requests\Forms\FormTemplateRequest;
use App\Models\Campaign;
use App\Models\FormTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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

        return redirect()->route('forms.edit', $template)->with('success', 'Form created.');
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
            'deleteBlocker' => $this->deleteBlocker($form),
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

        return redirect()->route('forms.edit', $form)->with('success', 'Form updated.');
    }

    public function publish(Request $request, FormTemplate $form, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $form);
        $this->forms->publish($form);
        $audit->record($request->user(), 'form.published', $form);

        return back()->with('success', 'Form published.');
    }

    public function version(Request $request, FormTemplate $form, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $form);
        $copy = $this->forms->createNewVersion($form);
        $audit->record($request->user(), 'form.versioned', $copy, ['from' => $form->version], ['to' => $copy->version]);

        return redirect()->route('forms.edit', $copy)->with('success', 'New version created.');
    }

    public function destroy(Request $request, FormTemplate $form, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $form);

        if ($blocker = $this->deleteBlocker($form)) {
            throw ValidationException::withMessages(['form' => $blocker]);
        }

        $audit->record($request->user(), 'form.deleted', $form, $form->only(['name', 'campaign_id', 'version', 'status']));
        $form->delete();

        return redirect()->route('forms.index')->with('success', 'Form '.$form->name.' deleted.');
    }

    /**
     * The submission -> form FK cascades, so only drafts without entries may be deleted.
     */
    private function deleteBlocker(FormTemplate $form): ?string
    {
        if ($form->status !== FormStatus::Draft) {
            return 'Only draft forms can be deleted. Published forms are kept as history.';
        }

        if ($form->submissions()->exists()) {
            return 'This form already has entries, so it cannot be deleted.';
        }

        return null;
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
