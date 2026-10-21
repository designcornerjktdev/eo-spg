import InputError from '@/components/input-error';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import SpgLayout from '@/layouts/spg-layout';
import { cn } from '@/lib/utils';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus } from 'lucide-react';
import { FormEvent, useState } from 'react';

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
    campaign: { name: string };
    form_template: {
        requires_consent: boolean;
        consent_text: string | null;
        fields: Field[];
    };
}

interface Submission {
    id: number;
    submitted_at: string;
    status: string;
    answers: Record<string, unknown>;
}

type Answer = string | string[] | boolean;

export default function SpgFill({
    assignment,
    fillable,
    context,
    submissions,
    todayCount,
}: {
    assignment: Assignment;
    fillable: boolean;
    context: { date: string; area: string | null; location: string | null; spg: string | null };
    submissions: { data: Submission[]; total: number; links: { url: string | null; label: string; active: boolean }[] };
    todayCount: number;
}) {
    const fields = assignment.form_template.fields;
    const [open, setOpen] = useState(false);

    return (
        <SpgLayout title={assignment.campaign.name}>
            <Head title={assignment.campaign.name} />

            <Link href={route('spg.tasks.index')} className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-sm">
                <ArrowLeft className="size-4" />
                Semua tugas
            </Link>

            <div className="bg-muted rounded-lg p-3 text-sm md:p-4">
                <dl className="grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-4">
                    <ContextItem label="Tanggal" value={context.date} />
                    <ContextItem label="Area" value={context.area} />
                    <ContextItem label="Lokasi" value={context.location} />
                    <ContextItem label="SPG" value={context.spg} />
                </dl>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 className="font-semibold">Data yang sudah diinput</h2>
                    <p className="text-muted-foreground text-sm">
                        Total {submissions.total} baris · hari ini {todayCount}. Data tidak bisa diedit setelah dikirim.
                    </p>
                </div>
                <Button className="h-11 md:h-10" disabled={!fillable} onClick={() => setOpen(true)}>
                    <Plus className="size-4" />
                    Tambah data
                </Button>
            </div>
            {!fillable && <p className="text-destructive text-sm">Assignment ini belum bisa diisi.</p>}

            {submissions.data.length === 0 ? (
                <div className="text-muted-foreground rounded-lg border border-dashed p-8 text-center text-sm">
                    Belum ada data. Tekan <span className="font-medium">Tambah data</span> untuk mulai input.
                </div>
            ) : (
                <>
                    {/* Tablet & laptop: tabel */}
                    <div className="hidden overflow-x-auto rounded-lg border md:block">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-3 py-2 font-medium whitespace-nowrap">Waktu</th>
                                    {fields.map((field) => (
                                        <th key={field.key} className="px-3 py-2 font-medium whitespace-nowrap">
                                            {field.label}
                                        </th>
                                    ))}
                                    <th className="px-3 py-2 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {submissions.data.map((submission) => (
                                    <tr
                                        key={submission.id}
                                        className={cn('border-t', submission.status === 'void' && 'text-muted-foreground line-through')}
                                    >
                                        <td className="px-3 py-2 whitespace-nowrap">{formatTime(submission.submitted_at)}</td>
                                        {fields.map((field) => (
                                            <td key={field.key} className="max-w-64 truncate px-3 py-2">
                                                {formatAnswer(submission.answers?.[field.key])}
                                            </td>
                                        ))}
                                        <td className="px-3 py-2">
                                            <StatusBadge status={submission.status} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Mobile: kartu */}
                    <div className="space-y-3 md:hidden">
                        {submissions.data.map((submission) => (
                            <div key={submission.id} className="rounded-lg border p-3 text-sm">
                                <div className="mb-2 flex items-center justify-between">
                                    <span className="text-muted-foreground text-xs">{formatTime(submission.submitted_at)}</span>
                                    <StatusBadge status={submission.status} />
                                </div>
                                <dl className="space-y-1">
                                    {fields.map((field) => (
                                        <div key={field.key} className="flex justify-between gap-3">
                                            <dt className="text-muted-foreground">{field.label}</dt>
                                            <dd className="text-right font-medium">{formatAnswer(submission.answers?.[field.key])}</dd>
                                        </div>
                                    ))}
                                </dl>
                            </div>
                        ))}
                    </div>
                </>
            )}
            <Pagination links={submissions.links} />

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="flex max-h-[95svh] max-w-[calc(100%-1rem)] flex-col gap-0 p-0 md:max-w-2xl">
                    <DialogHeader className="border-b p-4 text-left md:p-6">
                        <DialogTitle>Tambah data</DialogTitle>
                        <DialogDescription>
                            {context.location ?? '-'} · {context.date}
                        </DialogDescription>
                    </DialogHeader>
                    <EntryForm assignment={assignment} fillable={fillable} onDone={() => setOpen(false)} />
                </DialogContent>
            </Dialog>
        </SpgLayout>
    );
}

function EntryForm({ assignment, fillable, onDone }: { assignment: Assignment; fillable: boolean; onDone: () => void }) {
    const fields = assignment.form_template.fields;
    const initialAnswers = Object.fromEntries(fields.map((field) => [field.key, field.type === 'multiple_choice' ? [] : ''])) as Record<
        string,
        Answer
    >;
    const [addAnother, setAddAnother] = useState(false);
    const { data, setData, post, transform, reset, processing, errors } = useForm<{
        answers: Record<string, Answer>;
        consent_accepted: boolean;
    }>({
        answers: initialAnswers,
        consent_accepted: false,
    });

    const submit = (event: FormEvent, another: boolean) => {
        event.preventDefault();
        setAddAnother(another);
        transform((payload) => ({ ...payload, add_another: another }));
        post(route('spg.tasks.store', assignment.id), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                // "Simpan & tambah berikutnya" membiarkan pop-up tetap terbuka.
                if (!another) {
                    onDone();
                }
            },
        });
    };

    const fieldErrors = errors as Record<string, string>;

    return (
        <form className="flex min-h-0 flex-1 flex-col" onSubmit={(event) => submit(event, false)}>
            <div className="min-h-0 flex-1 space-y-4 overflow-y-auto p-4 md:p-6">
                <div className="grid gap-4 md:grid-cols-2 md:gap-x-6">
                    {fields.map((field) => (
                        <div key={field.key} className={cn('grid content-start gap-2', isWideField(field) && 'md:col-span-2')}>
                            <Label htmlFor={`field-${field.key}`}>
                                {field.label}
                                {field.required ? ' *' : ''}
                            </Label>
                            <FieldInput
                                field={field}
                                value={data.answers[field.key]}
                                onChange={(value) => setData('answers', { ...data.answers, [field.key]: value })}
                            />
                            <InputError message={fieldErrors[`answers.${field.key}`]} />
                        </div>
                    ))}
                </div>
                {assignment.form_template.requires_consent && (
                    <label className="flex items-start gap-2 text-sm">
                        <Checkbox checked={data.consent_accepted} onCheckedChange={(checked) => setData('consent_accepted', checked === true)} />
                        <span>{assignment.form_template.consent_text}</span>
                    </label>
                )}
                <InputError message={fieldErrors.consent_accepted ?? fieldErrors.assignment} />
            </div>
            <div className="grid gap-2 border-t p-4 md:flex md:flex-row-reverse md:justify-start md:px-6">
                <Button type="submit" className="h-12 md:h-10 md:px-6" disabled={processing || !fillable}>
                    {processing && !addAnother ? 'Menyimpan…' : 'Simpan'}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    className="h-12 md:h-10 md:px-6"
                    disabled={processing || !fillable}
                    onClick={(event) => submit(event, true)}
                >
                    {processing && addAnother ? 'Menyimpan…' : 'Simpan & tambah berikutnya'}
                </Button>
            </div>
        </form>
    );
}

function ContextItem({ label, value }: { label: string; value: string | null }) {
    return (
        <div>
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="font-medium">{value ?? '-'}</dd>
        </div>
    );
}

function StatusBadge({ status }: { status: string }) {
    return status === 'void' ? <Badge variant="destructive">Void</Badge> : <Badge variant="secondary">Terkirim</Badge>;
}

function formatTime(value: string) {
    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

function formatAnswer(value: unknown): string {
    if (value === null || value === undefined || value === '') return '-';
    if (typeof value === 'boolean') return value ? 'Ya' : 'Tidak';
    if (Array.isArray(value)) return value.length ? value.join(', ') : '-';
    return String(value);
}

// Field yang butuh ruang lebar mengambil dua kolom di tablet/laptop.
function isWideField(field: Field) {
    return field.type === 'long_text' || field.type === 'multiple_choice' || field.type === 'yes_no';
}

function FieldInput({ field, value, onChange }: { field: Field; value: Answer; onChange: (value: Answer) => void }) {
    const id = `field-${field.key}`;

    if (field.type === 'yes_no') {
        return (
            <div className="grid grid-cols-2 gap-2 md:max-w-md">
                <Button
                    type="button"
                    className="h-14 text-lg md:h-11 md:text-base"
                    variant={value === true || value === '1' ? 'default' : 'outline'}
                    onClick={() => onChange(true)}
                >
                    Ya
                </Button>
                <Button
                    type="button"
                    className="h-14 text-lg md:h-11 md:text-base"
                    variant={value === false || value === '0' ? 'default' : 'outline'}
                    onClick={() => onChange(false)}
                >
                    Tidak
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
                className="border-input bg-background h-11 rounded-md border px-2 md:h-10"
                value={String(value ?? '')}
                onChange={(e) => onChange(e.target.value)}
            >
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
            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                {(field.options ?? []).map((option) => (
                    <label key={option} className="flex min-h-11 cursor-pointer items-center gap-2 rounded-md border px-3 text-sm md:min-h-10">
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

    return <Input id={id} className="h-11 md:h-10" type={inputType} value={String(value ?? '')} onChange={(e) => onChange(e.target.value)} />;
}
