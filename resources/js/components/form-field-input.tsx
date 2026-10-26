import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { ImagePlus, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export interface DynamicField {
    key: string;
    label: string;
    type: string;
    required: boolean;
    options?: string[] | null;
    min_value?: number | null;
    max_value?: number | null;
}

export type Answer = string | string[] | boolean | File;

/** Fields that need more room span both columns on wider screens. */
export function isWideField(field: DynamicField): boolean {
    return field.type === 'long_text' || field.type === 'multiple_choice' || field.type === 'yes_no' || field.type === 'image';
}

export function emptyAnswer(field: DynamicField): Answer {
    return field.type === 'multiple_choice' ? [] : '';
}

/**
 * Input for one dynamic form field, matching the field types of the form builder.
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

    if (field.type === 'image') {
        return <ImageInput id={id} value={value instanceof File ? value : null} onChange={onChange} />;
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

/** Photo picker with a preview; on phones it offers the camera or the gallery. */
function ImageInput({ id, value, onChange }: { id: string; value: File | null; onChange: (value: Answer) => void }) {
    const input = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(null);

    useEffect(() => {
        if (!value) {
            setPreview(null);
            if (input.current) input.current.value = '';
            return;
        }
        const url = URL.createObjectURL(value);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [value]);

    return (
        <div className="space-y-2">
            <input ref={input} id={id} type="file" accept="image/*" className="sr-only" onChange={(e) => onChange(e.target.files?.[0] ?? '')} />
            {preview ? (
                <div className="relative w-fit">
                    <img src={preview} alt="" className="max-h-56 rounded-md border object-contain" />
                    <Button
                        type="button"
                        size="icon"
                        variant="secondary"
                        className="absolute top-2 right-2 size-8"
                        aria-label="Remove photo"
                        onClick={() => onChange('')}
                    >
                        <X className="size-4" />
                    </Button>
                </div>
            ) : (
                <label
                    htmlFor={id}
                    className="text-muted-foreground hover:bg-muted/50 flex h-28 cursor-pointer flex-col items-center justify-center gap-1 rounded-md border border-dashed text-sm"
                >
                    <ImagePlus className="size-6" />
                    Take or choose a photo
                </label>
            )}
        </div>
    );
}
