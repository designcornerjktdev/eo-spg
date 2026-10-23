import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

export interface DynamicField {
    key: string;
    label: string;
    type: string;
    required: boolean;
    options?: string[] | null;
    min_value?: number | null;
    max_value?: number | null;
}

export type Answer = string | string[] | boolean;

/** Fields that need more room span both columns on wider screens. */
export function isWideField(field: DynamicField): boolean {
    return field.type === 'long_text' || field.type === 'multiple_choice' || field.type === 'yes_no';
}

export function emptyAnswer(field: DynamicField): Answer {
    return field.type === 'multiple_choice' ? [] : '';
}

/**
 * Input for one dynamic form field, matching the eight field types of the form builder.
 */
export default function FormFieldInput({ field, value, onChange }: { field: DynamicField; value: Answer; onChange: (value: Answer) => void }) {
    const id = `field-${field.key}`;

    if (field.type === 'yes_no') {
        return (
            <div className="grid grid-cols-2 gap-2 sm:max-w-sm" role="group" aria-labelledby={`${id}-label`}>
                <Button type="button" className="h-12" variant={value === true ? 'default' : 'outline'} onClick={() => onChange(true)}>
                    Yes
                </Button>
                <Button type="button" className="h-12" variant={value === false ? 'default' : 'outline'} onClick={() => onChange(false)}>
                    No
                </Button>
            </div>
        );
    }

    if (field.type === 'long_text') {
        return <Textarea id={id} value={String(value ?? '')} onChange={(e) => onChange(e.target.value)} />;
    }

    if (field.type === 'single_choice') {
        return (
            <select
                id={id}
                className="border-input bg-background h-11 rounded-md border px-2"
                value={String(value ?? '')}
                onChange={(e) => onChange(e.target.value)}
            >
                <option value="">Select</option>
                {(field.options ?? []).map((option) => (
                    <option key={option} value={option}>
                        {option}
                    </option>
                ))}
            </select>
        );
    }

    if (field.type === 'multiple_choice') {
        const selected = Array.isArray(value) ? value : [];
        return (
            <div className="grid gap-2 sm:grid-cols-2" role="group" aria-labelledby={`${id}-label`}>
                {(field.options ?? []).map((option) => (
                    <label key={option} className="flex min-h-11 cursor-pointer items-center gap-2 rounded-md border px-3 text-sm">
                        <input
                            type="checkbox"
                            checked={selected.includes(option)}
                            onChange={(e) => onChange(e.target.checked ? [...selected, option] : selected.filter((item) => item !== option))}
                        />
                        {option}
                    </label>
                ))}
            </div>
        );
    }

    const inputType = field.type === 'number' ? 'number' : field.type === 'date' ? 'date' : field.type === 'phone' ? 'tel' : 'text';

    return (
        <Input
            id={id}
            className="h-11"
            type={inputType}
            inputMode={field.type === 'phone' ? 'tel' : field.type === 'number' ? 'numeric' : undefined}
            min={field.min_value ?? undefined}
            max={field.max_value ?? undefined}
            value={String(value ?? '')}
            onChange={(e) => onChange(e.target.value)}
        />
    );
}
