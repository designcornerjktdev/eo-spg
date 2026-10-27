<?php

namespace App\Domain\Forms;

use App\Enums\FieldType;

/**
 * Ready-made field sets an admin can start a new form from.
 */
class FormPresets
{
    private const CONSENT = 'Saya bersedia data pribadi saya (nama, nomor handphone, dan jawaban pada form ini) dikumpulkan dan diproses oleh penyelenggara dan brand terkait untuk keperluan aktivitas promosi, analisis, dan komunikasi pemasaran. Data saya disimpan dengan aman, tidak dijual kepada pihak lain, dan saya dapat meminta penghapusannya kapan saja sesuai UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi.';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'id' => 'sampling',
                'name' => 'Product sampling',
                'description' => 'Consumer profile with Yes/No qualification questions.',
                'requires_consent' => true,
                'consent_text' => self::CONSENT,
                'qualification_enabled' => true,
                'fields' => MayoraFormDefinition::fields(),
            ],
            [
                'id' => 'survey',
                'name' => 'Consumer survey',
                'description' => 'Product feedback with rating and purchase intent.',
                'requires_consent' => true,
                'consent_text' => self::CONSENT,
                'qualification_enabled' => false,
                'fields' => self::build([
                    ['nama', 'Nama', FieldType::ShortText, true, false],
                    ['no_handphone', 'No Handphone', FieldType::Phone, true, false],
                    ['usia', 'Usia', FieldType::Number, true, true, null, 1, 100],
                    ['jenis_kelamin', 'Jenis Kelamin', FieldType::SingleChoice, true, true, ['Laki-laki', 'Perempuan']],
                    ['rating_rasa', 'Rating rasa (1-5)', FieldType::Number, true, true, null, 1, 5],
                    ['akan_membeli', 'Akan membeli produk ini', FieldType::YesNo, true, false],
                    ['saran', 'Saran', FieldType::LongText, false, false],
                ]),
            ],
            [
                'id' => 'registration',
                'name' => 'Event registration',
                'description' => 'Visitor check-in with contact details and photo.',
                'requires_consent' => true,
                'consent_text' => self::CONSENT,
                'qualification_enabled' => false,
                'fields' => self::build([
                    ['nama', 'Nama', FieldType::ShortText, true, false],
                    ['no_handphone', 'No Handphone', FieldType::Phone, true, false],
                    ['domisili', 'Domisili', FieldType::ShortText, true, false],
                    ['sumber_info', 'Tahu event dari', FieldType::SingleChoice, false, true, ['Instagram', 'TikTok', 'Teman', 'Lewat lokasi']],
                    ['foto', 'Foto', FieldType::Image, false, false],
                ]),
            ],
        ];
    }

    /**
     * @param  array<int, array<int, mixed>>  $defs  [key, label, type, required, dashboard, options?, min?, max?]
     * @return array<int, array<string, mixed>>
     */
    private static function build(array $defs): array
    {
        return collect($defs)->values()->map(fn (array $def, int $index) => [
            'key' => $def[0],
            'label' => $def[1],
            'type' => $def[2]->value,
            'required' => $def[3],
            'sort_order' => $index + 1,
            'visible_to_client' => true,
            'show_on_dashboard' => $def[4],
            'options' => $def[5] ?? null,
            'min_value' => $def[6] ?? null,
            'max_value' => $def[7] ?? null,
        ])->all();
    }
}
