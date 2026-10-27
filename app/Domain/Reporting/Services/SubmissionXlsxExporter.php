<?php

namespace App\Domain\Reporting\Services;

use App\Domain\Submissions\Services\SubmissionRecorder;
use App\Enums\FieldType;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Excel export with photo answers shown as thumbnails, each followed by a column linking to the full image.
 */
class SubmissionXlsxExporter
{
    /** Thumbnail height in pixels; rows with a photo are sized to fit it. */
    private const THUMB_PX = 96;

    public function __construct(private readonly SubmissionTable $table) {}

    /**
     * @param  Collection<int, FormSubmission>  $submissions
     */
    public function download(User $viewer, Collection $submissions, string $filename = 'submissions.xlsx'): StreamedResponse
    {
        $submissions->loadMissing(['formTemplate.fields', 'location', 'campaign']);
        $headers = $this->withLinkColumns($this->table->headers($viewer, $submissions), $submissions);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Entries');
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $thumbnails = [];
        $photoColumns = [];

        foreach ($submissions->values() as $index => $submission) {
            $rowNumber = $index + 2;
            // Text cells first; photo cells stay blank under their thumbnail.
            $sheet->fromArray($this->table->row($viewer, $submission, $headers), null, 'A'.$rowNumber, true);

            foreach ($this->photoCells($submission, $headers) as $column => $fieldKey) {
                $coordinate = Coordinate::stringFromColumnIndex($column + 1).$rowNumber;
                $photoColumns[$column] = true;

                // The link column right of the photo holds the full-size URL; it opens for a signed-in
                // account that can view the entry.
                $url = $submission->fileUrl($fieldKey);
                $linkCoordinate = Coordinate::stringFromColumnIndex($column + 2).$rowNumber;
                $sheet->setCellValue($linkCoordinate, $url);
                $sheet->getCell($linkCoordinate)->getHyperlink()->setUrl($url);
                $sheet->getStyle($linkCoordinate)->getFont()->setUnderline(true)->getColor()->setRGB('0563C1');

                $thumbnail = $this->thumbnail($submission->answers[$fieldKey]);
                if ($thumbnail === null) {
                    continue;
                }
                $thumbnails[] = $thumbnail;

                $drawing = new Drawing;
                $drawing->setHyperlink(new Hyperlink($url));
                $drawing->setPath($thumbnail);
                $drawing->setHeight(self::THUMB_PX);
                $drawing->setCoordinates($coordinate);
                $drawing->setOffsetX(4);
                $drawing->setOffsetY(4);
                $drawing->setWorksheet($sheet);

                // Points are 3/4 of a pixel; add the offsets as padding.
                $sheet->getRowDimension($rowNumber)->setRowHeight((self::THUMB_PX + 8) * 0.75);
            }
        }

        foreach (range(1, count($headers)) as $column) {
            $dimension = $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column));
            isset($photoColumns[$column - 1]) ? $dimension->setWidth(22) : $dimension->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet, $thumbnails) {
            try {
                (new Xlsx($spreadsheet))->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
                array_map('unlink', $thumbnails);
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Adds a "<label> (link)" column right after each photo column.
     *
     * @param  array<int, string>  $headers
     * @param  Collection<int, FormSubmission>  $submissions
     * @return array<int, string>
     */
    private function withLinkColumns(array $headers, Collection $submissions): array
    {
        $photoLabels = $submissions
            ->flatMap(fn (FormSubmission $submission) => $submission->formTemplate->fields)
            ->filter(fn ($field) => $field->type === FieldType::Image)
            ->flatMap(fn ($field) => [$field->label, $field->key])
            ->unique()
            ->all();

        $result = [];
        foreach ($headers as $header) {
            $result[] = $header;
            if (in_array($header, $photoLabels, true)) {
                $result[] = $header.' (link)';
            }
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<int, string> zero-based column index => image field key with a stored photo
     */
    private function photoCells(FormSubmission $submission, array $headers): array
    {
        $fields = $submission->formTemplate->fields;
        $cells = [];

        foreach ($headers as $column => $header) {
            $field = $fields->firstWhere('label', $header) ?? $fields->firstWhere('key', $header);
            $path = $field ? ($submission->answers[$field->key] ?? null) : null;

            if ($field?->type === FieldType::Image && is_string($path) && $path !== '') {
                $cells[$column] = $field->key;
            }
        }

        return $cells;
    }

    /**
     * Writes a small JPEG copy of a stored photo to a temp file. Excel cannot show WebP, and full-size
     * photos would make the workbook huge.
     */
    private function thumbnail(string $path): ?string
    {
        $disk = Storage::disk(SubmissionRecorder::IMAGE_DISK);
        $image = $disk->exists($path) ? @imagecreatefromstring((string) $disk->get($path)) : false;

        if ($image === false) {
            return null;
        }

        $scaled = imagescale($image, (int) round(imagesx($image) * (self::THUMB_PX * 2) / imagesy($image)), self::THUMB_PX * 2);
        imagedestroy($image);
        if ($scaled === false) {
            return null;
        }

        $file = tempnam(sys_get_temp_dir(), 'thumb').'.jpg';
        imagejpeg($scaled, $file, 80);
        imagedestroy($scaled);

        return $file;
    }
}
