import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import SpgLayout from '@/layouts/spg-layout';
import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Field {
    key: string;
    label: string;
    type: string;
    required: boolean;
    options?: string[] | null;
    min_value?: number | null;
    max_value?: number | null;
}

interface Assignment {
    id: number;
    form_template: {
        requires_consent: boolean;
        consent_text: string | null;
        fields: Field[];
    };
}

export default function SpgFill({
    assignment,
    fillable,
    context,
}: {
    assignment: Assignment;
    fillable: boolean;
    context: { tanggal: string; area: string | null; lokasi: string | null; spg: string | null };
}) {
    const initialAnswers = Object.fromEntries(assignment.form_template.fields.map((field) => [field.key, field.type === 'multiple_choice' ? [] : field.type === 'yes_no' ? '' : '']));
    const { data, setData, processing, errors } = useForm<{
        answers: Record<string, string | string[] | boolean>;
        consent_accepted: boolean;
        add_another: boolean;
    }>({
        answers: initialAnswers as Record<string, string | string[] | boolean>,
        consent_accepted: false,
        add_another: false,
    });

    const submit = (event: FormEvent, addAnother: boolean) => {
        event.preventDefault();
        router.post(route('spg.tasks.store', assignment.id), {
            answers: data.answers,
            consent_accepted: data.consent_accepted,
            add_another: addAnother,
        }, { preserveScroll: true });
    };

    return (
        <SpgLayout title="Isi form">
            <Head title="Isi form" />
            <div className="rounded-lg bg-muted p-3 text-sm">
                <div>Tanggal: {context.tanggal}</div>
                <div>Area: {context.area ?? '-'}</div>
                <div>Lokasi: {context.lokasi ?? '-'}</div>
                <div>SPG: {context.spg ?? '-'}</div>
            </div>
            {!fillable && <p className="text-sm text-destructive">Assignment ini belum bisa diisi.</p>}
            <form className="space-y-4" onSubmit={(event) => submit(event, false)}>
                {assignment.form_template.fields.map((field) => (
                    <div key={field.key} className="grid gap-2">
                        <Label>
                            {field.label}
                            {field.required ? ' *' : ''}
                        </Label>
                        <FieldInput
                            field={field}
                            value={data.answers[field.key]}
                            onChange={(value) => setData('answers', { ...data.answers, [field.key]: value })}
                        />
                        <InputError message={(errors as Record<string, string>)[`answers.${field.key}`]} />
                    </div>
                ))}
                {assignment.form_template.requires_consent && (
                    <label className="flex items-start gap-2 text-sm">
                        <Checkbox checked={data.consent_accepted} onCheckedChange={(checked) => setData('consent_accepted', checked === true)} />
                        <span>{assignment.form_template.consent_text}</span>
                    </label>
                )}
                <InputError message={(errors as Record<string, string>).assignment} />
                <div className="grid gap-2">
                    <Button type="submit" className="h-12" disabled={processing || !fillable}>
                        Simpan
                    </Button>
                    <Button type="button" variant="outline" className="h-12" disabled={processing || !fillable} onClick={(event) => submit(event, true)}>
                        Simpan & tambah berikutnya
                    </Button>
                </div>
            </form>
        </SpgLayout>
    );
}

function FieldInput({
    field,
    value,
    onChange,
}: {
    field: Field;
    value: string | string[] | boolean;
    onChange: (value: string | string[] | boolean) => void;
}) {
    if (field.type === 'yes_no') {
        return (
            <div className="grid grid-cols-2 gap-2">
                <Button type="button" className="h-16 text-lg" variant={value === true || value === '1' ? 'default' : 'outline'} onClick={() => onChange(true)}>
                    Ya
                </Button>
                <Button type="button" className="h-16 text-lg" variant={value === false || value === '0' ? 'default' : 'outline'} onClick={() => onChange(false)}>
                    Tidak
                </Button>
            </div>
        );
    }

    if (field.type === 'long_text') {
        return <Textarea value={String(value ?? '')} onChange={(e) => onChange(e.target.value)} />;
    }

    if (field.type === 'single_choice') {
        return (
            <select className="border-input h-11 rounded-md border px-2" value={String(value ?? '')} onChange={(e) => onChange(e.target.value)}>
                <option value="">Pilih</option>
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
            <div className="space-y-2">
                {(field.options ?? []).map((option) => (
                    <label key={option} className="flex items-center gap-2 text-sm">
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

    return <Input className="h-11" type={inputType} value={String(value ?? '')} onChange={(e) => onChange(e.target.value)} />;
}
