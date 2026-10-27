<?php

namespace App\Models;

use App\Enums\FieldType;
use Database\Factories\FormFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    /** @use HasFactory<FormFieldFactory> */
    use HasFactory;

    protected $fillable = [
        'form_template_id',
        'key',
        'label',
        'type',
        'required',
        'sort_order',
        'visible_to_client',
        'show_on_dashboard',
        'options',
        'min_value',
        'max_value',
    ];

    protected function casts(): array
    {
        return [
            'type' => FieldType::class,
            'required' => 'boolean',
            'visible_to_client' => 'boolean',
            'show_on_dashboard' => 'boolean',
            'options' => 'array',
            'min_value' => 'float',
            'max_value' => 'float',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }
}
