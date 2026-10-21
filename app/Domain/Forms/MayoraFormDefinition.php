<?php

namespace App\Domain\Forms;

use App\Enums\FieldType;

class MayoraFormDefinition
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function fields(): array
    {
        $defs = [
            ['nama', 'Nama', FieldType::ShortText, true, true, false, null, null],
            ['usia', 'Usia', FieldType::Number, true, false, true, 1, 100],
            ['no_handphone', 'No Handphone', FieldType::Phone, true, true, false, null, null],
            ['pekerjaan', 'Pekerjaan', FieldType::ShortText, true, false, false, null, null],
            ['domisili', 'Domisili', FieldType::ShortText, true, false, false, null, null],
            ['akun_instagram', 'Akun Instagram', FieldType::ShortText, false, true, false, null, null],
            ['kunjungi_poi', 'Mengunjungi POI minimal 2x seminggu', FieldType::YesNo, true, false, true, null, null],
            ['aktivitas_area', 'Biasa bekerja, belajar, atau berlibur di area tersebut', FieldType::YesNo, true, false, true, null, null],
            ['konsumsi_csd', 'Mengonsumsi CSD minimal 2x sebulan', FieldType::YesNo, true, false, true, null, null],
        ];

        return collect($defs)->values()->map(function (array $def, int $index) {
            [$key, $label, $type, $required, $identity, $dashboard, $min, $max] = $def;

            return [
                'key' => $key,
                'label' => $label,
                'type' => $type->value,
                'required' => $required,
                'sort_order' => $index + 1,
                'visible_to_client' => true,
                'is_identity' => $identity,
                'show_on_dashboard' => $dashboard,
                'options' => null,
                'min_value' => $min,
                'max_value' => $max,
            ];
        })->all();
    }
}
