<?php

namespace App\Domain\Forms\Services;

use App\Enums\FieldType;
use App\Enums\FormStatus;
use App\Models\FormField;
use App\Models\FormTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FormDefinitionService
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $fields
     */
    public function create(array $attributes, array $fields): FormTemplate
    {
        return DB::transaction(function () use ($attributes, $fields) {
            $template = FormTemplate::query()->create($attributes);
            $this->syncFields($template, $fields);

            return $template->load('fields');
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $fields
     */
    public function update(FormTemplate $template, array $attributes, array $fields): FormTemplate
    {
        if ($template->isLocked()) {
            throw ValidationException::withMessages([
                'form' => 'This form is locked because it already has submissions. Create a new version to change it.',
            ]);
        }

        return DB::transaction(function () use ($template, $attributes, $fields) {
            $template->update($attributes);
            $this->syncFields($template, $fields);

            return $template->fresh('fields');
        });
    }

    public function publish(FormTemplate $template): FormTemplate
    {
        if ($template->fields()->count() === 0) {
            throw ValidationException::withMessages([
                'form' => 'A form needs at least one field before it can be published.',
            ]);
        }

        if ($template->status === FormStatus::Locked) {
            throw ValidationException::withMessages([
                'form' => 'A locked form cannot be published again.',
            ]);
        }

        $template->update(['status' => FormStatus::Published]);

        return $template->refresh();
    }

    public function lock(FormTemplate $template): FormTemplate
    {
        if ($template->status !== FormStatus::Locked) {
            $template->update(['status' => FormStatus::Locked]);
        }

        return $template->refresh();
    }

    public function createNewVersion(FormTemplate $template): FormTemplate
    {
        return DB::transaction(function () use ($template) {
            $nextVersion = (int) FormTemplate::query()
                ->where('campaign_id', $template->campaign_id)
                ->where('name', $template->name)
                ->max('version') + 1;

            $copy = $template->replicate([
                'status',
                'version',
            ]);
            $copy->version = $nextVersion;
            $copy->status = FormStatus::Draft;
            $copy->save();

            foreach ($template->fields as $field) {
                $copy->fields()->create($field->only([
                    'key',
                    'label',
                    'type',
                    'required',
                    'sort_order',
                    'visible_to_client',
                    'is_identity',
                    'show_on_dashboard',
                    'options',
                    'min_value',
                    'max_value',
                ]));
            }

            return $copy->load('fields');
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function syncFields(FormTemplate $template, array $fields): void
    {
        $keys = [];

        foreach (array_values($fields) as $index => $field) {
            $key = Str::slug((string) ($field['key'] ?: $field['label']), '_');

            if ($key === '' || in_array($key, $keys, true)) {
                throw ValidationException::withMessages([
                    'fields' => 'Every field needs a unique key.',
                ]);
            }

            $keys[] = $key;
            $type = FieldType::from($field['type']);

            if (($field['show_on_dashboard'] ?? false) && ! $type->canShowOnDashboard()) {
                throw ValidationException::withMessages([
                    'fields' => "Field {$field['label']} cannot be shown on the dashboard.",
                ]);
            }

            FormField::query()->updateOrCreate(
                [
                    'form_template_id' => $template->id,
                    'key' => $key,
                ],
                [
                    'label' => $field['label'],
                    'type' => $type,
                    'required' => (bool) ($field['required'] ?? false),
                    'sort_order' => $field['sort_order'] ?? $index + 1,
                    'visible_to_client' => (bool) ($field['visible_to_client'] ?? true),
                    'is_identity' => (bool) ($field['is_identity'] ?? false),
                    'show_on_dashboard' => (bool) ($field['show_on_dashboard'] ?? false),
                    'options' => $field['options'] ?? null,
                    'min_value' => $field['min_value'] ?? null,
                    'max_value' => $field['max_value'] ?? null,
                ],
            );
        }

        $template->fields()->whereNotIn('key', $keys)->delete();
    }
}
