<?php

namespace Database\Factories;

use App\Enums\FieldType;
use App\Models\FormField;
use App\Models\FormTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FormField>
 */
class FormFieldFactory extends Factory
{
    public function definition(): array
    {
        $label = fake()->unique()->word();

        return [
            'form_template_id' => FormTemplate::factory(),
            'key' => Str::slug($label, '_'),
            'label' => Str::title($label),
            'type' => FieldType::ShortText,
            'required' => true,
            'sort_order' => 1,
            'visible_to_client' => true,
            'is_identity' => false,
            'show_on_dashboard' => false,
            'options' => null,
            'min_value' => null,
            'max_value' => null,
        ];
    }
}
