<?php

namespace App\Models;

use App\Enums\FieldType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionFieldValue extends Model
{
    protected $fillable = [
        'form_submission_id',
        'field_key',
        'field_type',
        'value_text',
        'value_number',
    ];

    protected function casts(): array
    {
        return [
            'field_type' => FieldType::class,
            'value_number' => 'float',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }
}
