import InputError from '@/components/input-error';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import SpgLayout from '@/layouts/spg-layout';
import { cn } from '@/lib/utils';
import { Head, router, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
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

interface AssignmentOption {
    id: number;
    campaign: string | null;
    location: string | null;
    fillable: boolean;
}

interface Submission {
    id: number;
    submitted_at: string;
    status: string;
    answers: Record<string, unknown>;
    consent_accepted: boolean;
    void_reason: string | null;
    can_correct: boolean;
}

type Answer = string | string[] | boolean;

export default function SpgIndex({
    assignments,
    assignment,
    fillable,
    context,
    submissions,
    todayCount,
}: {
    assignments: AssignmentOption[];
    assignment: Assignment | null;
    fillable?: boolean;
    context?: { date: string; area: string | null; location: string | null; spg: string | null };
    submissions?: { data: Submission[]; total: number; from: number | null; links: { url: string | null; label: string; active: boolean }[] };
    todayCount?: number;
}) {
    // open + correcting=null berarti tambah data baru; correcting terisi berarti perbaiki baris itu.
    const [open, setOpen] = useState(false);
    const [correcting, setCorrecting] = useState<Submission | null>(null);
    const [cancelling, setCancelling] = useState<Submission | null>(null);

    if (!assignment || !context || !submissions) {
        return (
            <SpgLayout>
                <Head title="Data" />
                <div className="text-muted-foreground rounded-lg border border-dashed p-8 text-center text-sm">Belum ada tugas untuk akun ini.</div>
            </SpgLayout>
        );
    }

    const fields = assignment.form_template.fields;
    const rowOffset = submissions.from ?? 1;

    return (
        <SpgLayout>
            <Head title={assignment.campaign.name} />

            <div className="flex flex-wrap items-end justify-between gap-3">
                <div className="min-w-0 space-y-1">
                    {assignments.length > 1 ? (
                        <select
                            aria-label="Pilih tugas"
                            className="border-input bg-background h-10 max-w-full rounded-md border px-2 font-semibold"
                            value={assignment.id}
                            onChange={(e) => router.get(route('spg.tasks.index'), { assignment: e.target.value })}
                        >
                            {assignments.map((option) => (
                                <option key={option.id} value={option.id}>
                                    {option.campaign} · {option.location}
                                    {option.fillable ? '' : ' (tutup)'}
                                </option>
                            ))}
                        </select>
                    ) : (
                        <h1 className="text-lg font-semibold">{assignment.campaign.name}</h1>
                    )}
                    <p className="text-muted-foreground text-sm">
                        {[context.location, context.area, context.date].filter(Boolean).join(' · ')} · {submissions.total} baris, hari ini{' '}
                        {todayCount}
                    </p>
                </div>
                <Button
                    className="h-11 md:h-10"
                    disabled={!fillable}
                    onClick={() => {
                        setCorrecting(null);
                        setOpen(true);
                    }}
                >
                    <Plus className="size-4" />
                    Tambah data
                </Button>
            </div>
            {!fillable && <p className="text-destructive text-sm">Tugas ini sedang tidak bisa diisi.</p>}

            {/* Tabel ala spreadsheet; scroll ke samping kalau kolomnya banyak. */}
            <div className="overflow-x-auto rounded-md border">
                <table className="w-full border-collapse text-sm">
                    <thead className="bg-muted">
                        <tr>
                            <th className="text-muted-foreground w-10 border-r border-b px-2 py-2 text-center font-medium">#</th>
                            <th className="border-r border-b px-3 py-2 text-left font-medium whitespace-nowrap">Waktu</th>
                            {fields.map((field) => (
                                <th key={field.key} className="border-r border-b px-3 py-2 text-left font-medium whitespace-nowrap last:border-r-0">
                                    {field.label}
                                </th>
                            ))}
                            <th className="border-b px-3 py-2 text-left font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {submissions.data.map((submission, index) => (
                            <tr
                                key={submission.id}
                                className={cn('hover:bg-muted/40', submission.status === 'void' && 'text-muted-foreground [&>td.cell]:line-through')}
                            >
                                <td className="bg-muted/50 text-muted-foreground border-r border-b px-2 py-1.5 text-center">{rowOffset + index}</td>
                                <td className="cell border-r border-b px-3 py-1.5 whitespace-nowrap">{formatTime(submission.submitted_at)}</td>
                                {fields.map((field) => (
                                    <td key={field.key} className="cell max-w-64 truncate border-r border-b px-3 py-1.5">
                                        {formatAnswer(submission.answers?.[field.key])}
                                    </td>
                                ))}
                                <td className="border-b px-2 py-1 whitespace-nowrap">
                                    {submission.status === 'void' ? (
                                        <span className="text-xs" title={submission.void_reason ?? undefined}>
                                            Batal{submission.void_reason ? `: ${submission.void_reason}` : ''}
                                        </span>
                                    ) : submission.can_correct ? (
                                        <div className="flex gap-1">
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="h-8"
                                                onClick={() => {
                                                    setCorrecting(submission);
                                                    setOpen(true);
                                                }}
                                            >
                                                Perbaiki
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="text-destructive h-8"
                                                onClick={() => setCancelling(submission)}
                                            >
                                                Batalkan
                                            </Button>
                                        </div>
                                    ) : (
                                        <span className="text-muted-foreground text-xs">-</span>
                                    )}
                                </td>
                            </tr>
                        ))}
                        {submissions.data.length === 0 && (
                            <tr>
                                <td colSpan={fields.length + 3} className="text-muted-foreground px-3 py-8 text-center">
                                    Belum ada data. Tekan <span className="font-medium">Tambah data</span> untuk mulai input.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            <Pagination links={submissions.links} />

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="flex max-h-[95svh] max-w-[calc(100%-1rem)] flex-col gap-0 p-0 md:max-w-2xl">
                    <DialogHeader className="border-b p-4 text-left md:p-6">
                        <DialogTitle>{correcting ? 'Perbaiki data' : 'Tambah data'}</DialogTitle>
                        <DialogDescription>
                            {correcting
                                ? 'Data lama tetap tersimpan dan ditandai batal, lalu diganti baris baru ini.'
                                : `${context.location ?? '-'} · ${context.date}`}
                        </DialogDescription>
                    </DialogHeader>
                    <EntryForm
                        key={correcting?.id ?? 'new'}
                        assignment={assignment}
                        fillable={!!fillable || !!correcting}
                        correcting={correcting}
                        onDone={() => setOpen(false)}
                    />
                </DialogContent>
            </Dialog>

            <CancelDialog submission={cancelling} onClose={() => setCancelling(null)} />
        </SpgLayout>
    );
}

function EntryForm({
    assignment,
    fillable,
    correcting,
    onDone,
}: {
    assignment: Assignment;
    fillable: boolean;
    correcting: Submission | null;
    onDone: () => void;
}) {
    const fields = assignment.form_template.fields;
    const initialAnswers = Object.fromEntries(
        fields.map((field) => [field.key, toAnswer(correcting?.answers?.[field.key], field.type === 'multiple_choice' ? [] : '')]),
    ) as Record<string, Answer>;
    const [addAnother, setAddAnother] = useState(false);
    const { data, setData, post, transform, reset, processing, errors } = useForm<{
        answers: Record<string, Answer>;
        consent_accepted: boolean;
    }>({
        answers: initialAnswers,
        consent_accepted: correcting?.consent_accepted ?? false,
    });

    const submit = (event: FormEvent, another: boolean) => {
        event.preventDefault();
        setAddAnother(another);
        transform((payload) => ({ ...payload, add_another: another }));
        post(correcting ? route('spg.submissions.correct', correcting.id) : route('spg.tasks.store', assignment.id), {
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
                <InputError message={fieldErrors.consent_accepted ?? fieldErrors.assignment ?? fieldErrors.submission} />
            </div>
            <div className="grid gap-2 border-t p-4 md:flex md:flex-row-reverse md:justify-start md:px-6">
                <Button type="submit" className="h-12 md:h-10 md:px-6" disabled={processing || !fillable}>
                    {processing && !addAnother ? 'Menyimpan…' : correcting ? 'Simpan perbaikan' : 'Simpan'}
                </Button>
                {!correcting && (
                    <Button
                        type="button"
                        variant="outline"
                        className="h-12 md:h-10 md:px-6"
                        disabled={processing || !fillable}
                        onClick={(event) => submit(event, true)}
                    >
                        {processing && addAnother ? 'Menyimpan…' : 'Simpan & tambah berikutnya'}
                    </Button>
                )}
            </div>
        </form>
    );
}

function CancelDialog({ submission, onClose }: { submission: Submission | null; onClose: () => void }) {
    const { data, setData, post, processing, errors, reset } = useForm({ reason: '' });
    const formErrors = errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (!submission) return;
        post(route('spg.submissions.cancel', submission.id), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <Dialog open={!!submission} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-[calc(100%-1rem)] md:max-w-md">
                <DialogHeader className="text-left">
                    <DialogTitle>Batalkan data?</DialogTitle>
                    <DialogDescription>Data tidak dihapus, hanya ditandai batal dan tidak dihitung di laporan.</DialogDescription>
                </DialogHeader>
                <form className="space-y-4" onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label htmlFor="cancel-reason">Alasan *</Label>
                        <Textarea
                            id="cancel-reason"
                            value={data.reason}
                            placeholder="Misal: data dobel"
                            onChange={(e) => setData('reason', e.target.value)}
                        />
                        <InputError message={formErrors.reason ?? formErrors.submission} />
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Kembali
                        </Button>
                        <Button type="submit" variant="destructive" disabled={processing}>
                            Batalkan data
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

// Ubah jawaban tersimpan (angka, boolean, array) ke bentuk yang dipakai input form.
function toAnswer(value: unknown, fallback: Answer): Answer {
    if (value === null || value === undefined) return fallback;
    if (typeof value === 'boolean' || Array.isArray(value)) return value as Answer;
    return String(value);
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
