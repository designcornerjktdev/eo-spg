<?php

namespace App\Domain\Submissions\Services;

use App\Enums\FieldType;
use App\Models\CampaignLocation;
use App\Models\FormField;
use App\Models\FormTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Imports entries from an Excel/CSV sheet into one campaign location, using the campaign's current form.
 * Columns match a field by label or key; an optional "submitted_at" column sets the entry time.
 * All rows are checked first and nothing is saved if any row is invalid.
 */
class SubmissionImporter
{
    public const MAX_ROWS = 2000;

    private const SUBMITTED_AT = 'submitted_at';

    /** Row errors shown to the user; the rest are summarised. */
    private const MAX_ERRORS = 20;

    public function __construct(private readonly SubmissionRecorder $recorder) {}

    /**
     * @return int number of entries created
     */
    public function import(CampaignLocation $location, UploadedFile $file): int
    {
        $template = $this->template($location);
        $rows = $this->readRows($file);
        $columns = $this->mapColumns(array_shift($rows) ?? [], $template->fields);

        $rows = array_values(array_filter($rows, fn (array $row) => collect($row)->filter(fn ($cell) => filled($cell))->isNotEmpty()));
        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'The file has no entry rows below the header.']);
        }
        if (count($rows) > self::MAX_ROWS) {
            throw ValidationException::withMessages(['file' => 'Import at most '.self::MAX_ROWS.' rows at a time.']);
        }

        $entries = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            // +2: one for the header row, one because sheets count from 1.
            $line = $index + 2;

            try {
                $entry = $this->entry($row, $columns, $template->fields);
                $this->recorder->validateImport($template, $entry['answers']);
                $entries[] = $entry;
            } catch (ValidationException $exception) {
                $errors[] = 'Row '.$line.': '.implode(' ', $exception->validator->errors()->all());
            }
        }

        if ($errors !== []) {
            $shown = array_slice($errors, 0, self::MAX_ERRORS);
            if (count($errors) > self::MAX_ERRORS) {
                $shown[] = 'And '.(count($errors) - self::MAX_ERRORS).' more rows with errors.';
            }

            // One message: Inertia only passes the first error per field to the page.
            throw ValidationException::withMessages(['file' => implode("\n", $shown)]);
        }

        DB::transaction(function () use ($entries, $location, $template) {
            foreach ($entries as $entry) {
                $this->recorder->import($location, $template, $entry['answers'], $entry['submitted_at']);
            }
        });

        return count($entries);
    }

    /**
     * Empty sheet with one column per form field (photos excluded) plus submitted_at.
     */
    public function templateDownload(CampaignLocation $location): StreamedResponse
    {
        $template = $this->template($location);
        $headers = $template->fields
            ->reject(fn (FormField $field) => $field->type === FieldType::Image)
            ->map(fn (FormField $field) => $field->label)
            ->prepend(self::SUBMITTED_AT)
            ->values()
            ->all();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Entries');
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function template(CampaignLocation $location): FormTemplate
    {
        $template = $location->campaign?->currentForm();

        if (! $template) {
            throw ValidationException::withMessages(['location_id' => 'This campaign has no published form yet.']);
        }

        return $template->load('fields');
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readRows(UploadedFile $file): array
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (\Throwable) {
            throw ValidationException::withMessages(['file' => 'The file could not be read. Use .xlsx or .csv.']);
        }

        // Raw values, not formatted text, so dates arrive as Excel serial numbers.
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false);
        $spreadsheet->disconnectWorksheets();

        return $rows;
    }

    /**
     * @param  array<int, mixed>  $header
     * @param  Collection<int, FormField>  $fields
     * @return array<int, string> column index => field key or "submitted_at"
     */
    private function mapColumns(array $header, Collection $fields): array
    {
        $byName = [self::SUBMITTED_AT => self::SUBMITTED_AT];
        foreach ($fields as $field) {
            if ($field->type === FieldType::Image) {
                continue;
            }
            $byName[$this->normalizeHeader($field->label)] = $field->key;
            $byName[$this->normalizeHeader($field->key)] = $field->key;
        }

        $columns = [];
        foreach ($header as $index => $name) {
            $key = $byName[$this->normalizeHeader((string) $name)] ?? null;
            if ($key !== null) {
                $columns[$index] = $key;
            }
        }

        if (array_diff($columns, [self::SUBMITTED_AT]) === []) {
            throw ValidationException::withMessages(['file' => 'No column matches a field of this form. Download the template to see the expected headers.']);
        }

        return $columns;
    }

    private function normalizeHeader(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? ''));
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<int, string>  $columns
     * @param  Collection<int, FormField>  $fields
     * @return array{answers: array<string, mixed>, submitted_at: CarbonImmutable}
     */
    private function entry(array $row, array $columns, Collection $fields): array
    {
        $fieldsByKey = $fields->keyBy('key');
        $answers = [];
        $submittedAt = CarbonImmutable::now();

        foreach ($columns as $index => $key) {
            $cell = $row[$index] ?? null;

            if ($key === self::SUBMITTED_AT) {
                if (filled($cell)) {
                    $submittedAt = $this->date($cell) ?? throw ValidationException::withMessages(['submitted_at' => 'submitted_at is not a valid date.']);
                }

                continue;
            }

            $value = $this->cast($fieldsByKey[$key], $cell);
            if ($value !== null) {
                $answers[$key] = $value;
            }
        }

        return ['answers' => $answers, 'submitted_at' => $submittedAt];
    }

    /**
     * Turns a cell into the shape the form validator expects; null means "no answer".
     */
    private function cast(FormField $field, mixed $cell): mixed
    {
        if (is_string($cell)) {
            $cell = trim($cell);
        }
        if ($cell === null || $cell === '') {
            return null;
        }

        return match ($field->type) {
            FieldType::YesNo => match (mb_strtolower((string) $cell)) {
                '1', 'yes', 'y', 'ya', 'true' => true,
                '0', 'no', 'n', 'tidak', 'false' => false,
                default => $cell,
            },
            FieldType::MultipleChoice => array_values(array_filter(array_map('trim', preg_split('/[|,]/', (string) $cell) ?: []))),
            FieldType::Date => $this->date($cell)?->toDateString() ?? $cell,
            FieldType::Number => is_numeric($cell) ? $cell + 0 : $cell,
            // Phone numbers typed into Excel lose their leading zero and become numbers.
            FieldType::Phone => is_numeric($cell) && ! str_starts_with((string) $cell, '0') && ! str_starts_with((string) $cell, '62') ? '0'.$cell : (string) $cell,
            default => (string) $cell,
        };
    }

    private function date(mixed $cell): ?CarbonImmutable
    {
        try {
            return is_numeric($cell)
                ? CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $cell))
                : CarbonImmutable::parse((string) $cell);
        } catch (\Throwable) {
            return null;
        }
    }
}
