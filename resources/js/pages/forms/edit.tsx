import DangerZone from '@/components/danger-zone';
import FormPage from '@/components/form-page';
import FormPreviewCard from '@/components/form-preview';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { SimpleSelect } from '@/components/simple-select';
import { TagInput } from '@/components/tag-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Head, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, GripVertical } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface Field {
    [key: string]: string | number | boolean | string[] | null;
    key: string;
    label: string;
    type: string;
    required: boolean;
    visible_to_client: boolean;
    show_on_dashboard: boolean;
    options: string[] | null;
    min_value: number | null;
    max_value: number | null;
}

interface Preset {
    id: string;
    name: string;
    description: string;
    requires_consent: boolean;
    consent_text: string;
    qualification_enabled: boolean;
    fields: Field[];
}

interface Template {
    id: number;
    campaign_id: number;
    name: string;
    version: number;
    status: string;
    requires_consent: boolean;
    consent_text: string | null;
    qualification_enabled: boolean;
    fields: Field[];
}

const emptyField = (): Field => ({
    key: '',
    label: '',
    type: 'short_text',
    required: true,
    visible_to_client: true,
    show_on_dashboard: false,
    options: [],
    min_value: null,
    max_value: null,
});

const slugify = (text: string) =>
    text
        .toLowerCase()
        .normalize('NFKD')
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '')
        .slice(0, 70) || 'field';

// Saved fields keep their key (answers are stored under it); new fields get a unique key from their label.
const withGeneratedKeys = (fields: Field[], savedKeys: Set<string>): Field[] => {
    const used = new Set(fields.filter((field) => savedKeys.has(field.key)).map((field) => field.key));
    return fields.map((field) => {
        if (savedKeys.has(field.key)) return field;
        const base = slugify(field.label);
        let key = base;
        for (let n = 2; used.has(key); n++) key = `${base}_${n}`;
        used.add(key);
        return { ...field, key };
    });
};

// Field types that get a dashboard chart (mirrors FieldType::canShowOnDashboard()).
const DASHBOARD_TYPES = ['number', 'single_choice', 'multiple_choice'];

export default function FormEdit({
    template,
    campaigns,
    selectedCampaignId,
    fieldTypes,
    presets = [],
    locked = false,
    deleteBlocker = null,
}: {
    presets?: Preset[];
    template: Template | null;
    campaigns: { id: number; name: string }[];
    selectedCampaignId: number | null;
    fieldTypes: { value: string; label: string }[];
    locked?: boolean;
    deleteBlocker?: string | null;
}) {
    const savedKeys = new Set((template?.fields ?? []).map((field) => field.key));
    const { data, setData, post, put, transform, processing, errors } = useForm({
        campaign_id: (template?.campaign_id ?? selectedCampaignId ?? '').toString(),
        name: template?.name ?? 'Form Sampling',
        requires_consent: template?.requires_consent ?? false,
        consent_text: template?.consent_text ?? '',
        qualification_enabled: template?.qualification_enabled ?? false,
        fields: template?.fields?.length ? template.fields : [emptyField()],
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        transform((form) => ({
            ...form,
            // Saved fields still carry their old sort_order; the list position is the order now.
            fields: withGeneratedKeys(form.fields, savedKeys).map((field, index) => ({ ...field, sort_order: index + 1 })),
        }));
        if (template) {
            put(route('forms.update', template.id));
        } else {
            post(route('forms.store'));
        }
    };

    const [presetId, setPresetId] = useState('');

    const applyPreset = (id: string) => {
        setPresetId(id);
        const preset = presets.find((item) => item.id === id);
        setData({
            ...data,
            name: preset?.name ?? 'Form Sampling',
            requires_consent: preset?.requires_consent ?? false,
            consent_text: preset?.consent_text ?? '',
            qualification_enabled: preset?.qualification_enabled ?? false,
            fields: preset ? preset.fields.map((field) => ({ ...emptyField(), ...field })) : [emptyField()],
        });
    };

    const moveField = (from: number, to: number) => {
        if (to < 0 || to >= data.fields.length || from === to) return;
        const next = [...data.fields];
        const [moved] = next.splice(from, 1);
        next.splice(to, 0, moved);
        setData('fields', next);
    };

    // Dragging starts only from the grip, so text in the inputs can still be selected.
    const [dragArmed, setDragArmed] = useState<number | null>(null);
    const [dragFrom, setDragFrom] = useState<number | null>(null);
    const [dragOver, setDragOver] = useState<number | null>(null);

    const updateField = (index: number, patch: Partial<Field>) => {
        const next = [...data.fields];
        next[index] = { ...next[index], ...patch } as Field;
        setData('fields', next);
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Form', href: '/forms' },
                { title: template ? template.name : 'Create form', href: '#' },
            ]}
        >
            <Head title={template ? `Form ${template.name}` : 'Create form'} />
            <FormPage width="2xl">
                <PageHeader
                    title={template ? `${template.name} v${template.version}` : 'Create form'}
                    description={
                        locked ? 'This form is locked. Create a new version to change its fields.' : 'Eight field types, no conditional logic.'
                    }
                />
                {template && (
                    <div className="flex flex-wrap gap-2">
                        {template.status === 'draft' && (
                            <Button type="button" onClick={() => router.post(route('forms.publish', template.id))}>
                                Publish
                            </Button>
                        )}
                        {locked && (
                            <Button type="button" variant="outline" onClick={() => router.post(route('forms.version', template.id))}>
                                Create new version
                            </Button>
                        )}
                    </div>
                )}
                <div className="grid items-start gap-6 lg:grid-cols-2">
                    <form onSubmit={submit} className="grid gap-6 rounded-xl border p-4 sm:p-6">
                        {!template && presets.length > 0 && (
                            <div className="grid gap-2">
                                <Label>Template</Label>
                                <SimpleSelect
                                    value={presetId}
                                    onChange={applyPreset}
                                    emptyLabel="Blank form"
                                    options={presets.map((preset) => ({ value: preset.id, label: preset.name }))}
                                />
                                <p className="text-muted-foreground text-sm">
                                    {presets.find((preset) => preset.id === presetId)?.description ??
                                        'Pick a template to prefill the fields. Choosing one replaces the fields below.'}
                                </p>
                            </div>
                        )}
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label>Campaign</Label>
                                <SimpleSelect
                                    value={String(data.campaign_id ?? '')}
                                    onChange={(campaignId) => setData('campaign_id', campaignId)}
                                    disabled={locked}
                                    placeholder="Select a campaign"
                                    options={campaigns.map((campaign) => ({ value: String(campaign.id), label: campaign.name }))}
                                />
                                <InputError message={errors.campaign_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Form name</Label>
                                <Input value={data.name} onChange={(e) => setData('name', e.target.value)} disabled={locked} />
                            </div>
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={data.requires_consent}
                                onCheckedChange={(checked) => setData('requires_consent', checked === true)}
                                disabled={locked}
                            />
                            Require consent
                        </label>
                        {data.requires_consent && (
                            <Textarea
                                value={data.consent_text}
                                onChange={(e) => setData('consent_text', e.target.value)}
                                disabled={locked}
                                placeholder="Consent text"
                            />
                        )}
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={data.qualification_enabled}
                                onCheckedChange={(checked) => setData('qualification_enabled', checked === true)}
                                disabled={locked}
                            />
                            Qualification: every Yes/No answer must be Yes
                        </label>

                        <div className="space-y-4">
                            {data.fields.map((field, index) => (
                                <div
                                    key={index}
                                    draggable={!locked && dragArmed === index}
                                    onDragStart={(e) => {
                                        e.dataTransfer.effectAllowed = 'move';
                                        setDragFrom(index);
                                    }}
                                    onDragOver={(e) => {
                                        if (dragFrom === null) return;
                                        e.preventDefault();
                                        setDragOver(index);
                                    }}
                                    onDrop={(e) => {
                                        e.preventDefault();
                                        if (dragFrom !== null) moveField(dragFrom, index);
                                    }}
                                    onDragEnd={() => {
                                        setDragFrom(null);
                                        setDragOver(null);
                                        setDragArmed(null);
                                    }}
                                    className={cn(
                                        'grid gap-3 rounded-lg border p-4 transition-colors',
                                        dragFrom === index && 'opacity-50',
                                        dragOver === index && dragFrom !== index && 'border-primary bg-muted/40',
                                    )}
                                >
                                    <div className="flex items-center gap-2">
                                        {!locked && (
                                            <button
                                                type="button"
                                                aria-label="Drag to reorder"
                                                className="text-muted-foreground hover:text-foreground cursor-grab active:cursor-grabbing"
                                                onPointerDown={() => setDragArmed(index)}
                                                onPointerUp={() => setDragArmed(null)}
                                            >
                                                <GripVertical className="size-4" />
                                            </button>
                                        )}
                                        <span className="text-muted-foreground text-xs font-medium">Field {index + 1}</span>
                                        {!locked && (
                                            <div className="ml-auto flex gap-1">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-7"
                                                    aria-label="Move up"
                                                    disabled={index === 0}
                                                    onClick={() => moveField(index, index - 1)}
                                                >
                                                    <ArrowUp className="size-4" />
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-7"
                                                    aria-label="Move down"
                                                    disabled={index === data.fields.length - 1}
                                                    onClick={() => moveField(index, index + 1)}
                                                >
                                                    <ArrowDown className="size-4" />
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                    <div className="grid gap-3 md:grid-cols-[2fr_1fr]">
                                        <Input
                                            placeholder="Label"
                                            value={field.label}
                                            disabled={locked}
                                            onChange={(e) => updateField(index, { label: e.target.value })}
                                        />
                                        <SimpleSelect
                                            value={field.type}
                                            disabled={locked}
                                            onChange={(type) =>
                                                updateField(index, {
                                                    type,
                                                    // Only number/choice fields can be charted; drop the flag for other types.
                                                    ...(DASHBOARD_TYPES.includes(type) ? {} : { show_on_dashboard: false }),
                                                })
                                            }
                                            options={fieldTypes}
                                        />
                                    </div>
                                    {['single_choice', 'multiple_choice'].includes(field.type) && (
                                        <TagInput
                                            disabled={locked}
                                            placeholder="Add an option, then press Enter"
                                            value={field.options ?? []}
                                            onChange={(options) => updateField(index, { options })}
                                        />
                                    )}
                                    <div className="flex flex-wrap gap-4 text-sm">
                                        <label className="flex items-center gap-2">
                                            <Checkbox
                                                checked={field.required}
                                                disabled={locked}
                                                onCheckedChange={(checked) => updateField(index, { required: checked === true })}
                                            />
                                            Required
                                        </label>
                                        <label className="flex items-center gap-2">
                                            <Checkbox
                                                checked={field.visible_to_client}
                                                disabled={locked}
                                                onCheckedChange={(checked) => updateField(index, { visible_to_client: checked === true })}
                                            />
                                            Visible to client
                                        </label>
                                        {/* Yes/No and free-text answers have no dashboard chart; Yes/No feeds qualification instead. */}
                                        {DASHBOARD_TYPES.includes(field.type) && (
                                            <label className="flex items-center gap-2">
                                                <Checkbox
                                                    checked={field.show_on_dashboard}
                                                    disabled={locked}
                                                    onCheckedChange={(checked) => updateField(index, { show_on_dashboard: checked === true })}
                                                />
                                                Dashboard
                                            </label>
                                        )}
                                    </div>
                                    {!locked && data.fields.length > 1 && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                setData(
                                                    'fields',
                                                    data.fields.filter((_, i) => i !== index),
                                                )
                                            }
                                        >
                                            Remove field
                                        </Button>
                                    )}
                                </div>
                            ))}
                            {!locked && (
                                <Button type="button" variant="outline" onClick={() => setData('fields', [...data.fields, emptyField()])}>
                                    Add field
                                </Button>
                            )}
                            <InputError message={errors.fields} />
                            <InputError message={(errors as Record<string, string>).form} />
                        </div>
                        {!locked && <Button disabled={processing}>Save form</Button>}
                    </form>
                    <aside className="space-y-2 lg:sticky lg:top-4 lg:max-h-[calc(100svh-2rem)] lg:overflow-y-auto">
                        <h2 className="text-muted-foreground text-sm font-medium">Preview</h2>
                        <FormPreviewCard fields={data.fields} requiresConsent={data.requires_consent} consentText={data.consent_text} />
                    </aside>
                </div>
                {template && (
                    <DangerZone
                        title="Delete form"
                        description="This form is a draft and has no entries yet."
                        confirmTitle={`Delete form ${template.name} v${template.version}?`}
                        deleteUrl={route('forms.destroy', template.id)}
                        blocker={deleteBlocker}
                        errorKey="form"
                    />
                )}
            </FormPage>
        </AppLayout>
    );
}
