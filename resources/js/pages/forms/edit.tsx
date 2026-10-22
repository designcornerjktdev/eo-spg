import DangerZone from '@/components/danger-zone';
import FormPage from '@/components/form-page';
import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Field {
    [key: string]: string | number | boolean | string[] | null;
    key: string;
    label: string;
    type: string;
    required: boolean;
    visible_to_client: boolean;
    is_identity: boolean;
    show_on_dashboard: boolean;
    options: string[] | null;
    min_value: number | null;
    max_value: number | null;
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
    is_identity: false,
    show_on_dashboard: false,
    options: [],
    min_value: null,
    max_value: null,
});

export default function FormEdit({
    template,
    campaigns,
    selectedCampaignId,
    fieldTypes,
    locked = false,
    deleteBlocker = null,
}: {
    template: Template | null;
    campaigns: { id: number; name: string }[];
    selectedCampaignId: number | null;
    fieldTypes: { value: string; label: string }[];
    locked?: boolean;
    deleteBlocker?: string | null;
}) {
    const { data, setData, post, put, processing, errors } = useForm({
        campaign_id: (template?.campaign_id ?? selectedCampaignId ?? '').toString(),
        name: template?.name ?? 'Form Sampling',
        requires_consent: template?.requires_consent ?? false,
        consent_text: template?.consent_text ?? '',
        qualification_enabled: template?.qualification_enabled ?? false,
        fields: template?.fields?.length ? template.fields : [emptyField()],
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (template) {
            put(route('forms.update', template.id));
        } else {
            post(route('forms.store'));
        }
    };

    const updateField = (index: number, patch: Partial<Field>) => {
        const next = [...data.fields];
        next[index] = { ...next[index], ...patch } as Field;
        setData('fields', next);
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Form', href: '/forms' },
                { title: template ? template.name : 'Buat form', href: '#' },
            ]}
        >
            <Head title={template ? `Form ${template.name}` : 'Buat form'} />
            <FormPage width="xl">
                <PageHeader
                    title={template ? `${template.name} v${template.version}` : 'Buat form'}
                    description={locked ? 'Form terkunci. Buat versi baru untuk mengubah field.' : 'Delapan tipe field tanpa logika kondisional.'}
                />
                {template && (
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href={route('forms.preview', template.id)}>Pratinjau</Link>
                        </Button>
                        {template.status === 'draft' && (
                            <Button type="button" onClick={() => router.post(route('forms.publish', template.id))}>
                                Terbitkan
                            </Button>
                        )}
                        {locked && (
                            <Button type="button" variant="outline" onClick={() => router.post(route('forms.version', template.id))}>
                                Buat versi baru
                            </Button>
                        )}
                    </div>
                )}
                <form onSubmit={submit} className="grid gap-6 rounded-xl border p-4 sm:p-6">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label>Campaign</Label>
                            <select
                                className="border-input h-9 rounded-md border px-2"
                                value={data.campaign_id}
                                onChange={(e) => setData('campaign_id', e.target.value)}
                                disabled={locked}
                            >
                                <option value="">Pilih campaign</option>
                                {campaigns.map((campaign) => (
                                    <option key={campaign.id} value={campaign.id}>
                                        {campaign.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.campaign_id} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Nama form</Label>
                            <Input value={data.name} onChange={(e) => setData('name', e.target.value)} disabled={locked} />
                        </div>
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={data.requires_consent}
                            onCheckedChange={(checked) => setData('requires_consent', checked === true)}
                            disabled={locked}
                        />
                        Wajibkan consent
                    </label>
                    {data.requires_consent && (
                        <Textarea
                            value={data.consent_text}
                            onChange={(e) => setData('consent_text', e.target.value)}
                            disabled={locked}
                            placeholder="Teks consent"
                        />
                    )}
                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={data.qualification_enabled}
                            onCheckedChange={(checked) => setData('qualification_enabled', checked === true)}
                            disabled={locked}
                        />
                        Kualifikasi: semua jawaban Ya/Tidak harus Ya
                    </label>

                    <div className="space-y-4">
                        {data.fields.map((field, index) => (
                            <div key={index} className="grid gap-3 rounded-lg border p-4">
                                <div className="grid gap-3 md:grid-cols-3">
                                    <Input
                                        placeholder="Label"
                                        value={field.label}
                                        disabled={locked}
                                        onChange={(e) =>
                                            updateField(index, {
                                                label: e.target.value,
                                                key: field.key || e.target.value.toLowerCase().replace(/\s+/g, '_'),
                                            })
                                        }
                                    />
                                    <Input
                                        placeholder="Kunci unik"
                                        value={field.key}
                                        disabled={locked}
                                        onChange={(e) => updateField(index, { key: e.target.value })}
                                    />
                                    <select
                                        className="border-input h-9 rounded-md border px-2"
                                        value={field.type}
                                        disabled={locked}
                                        onChange={(e) => updateField(index, { type: e.target.value })}
                                    >
                                        {fieldTypes.map((type) => (
                                            <option key={type.value} value={type.value}>
                                                {type.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                {['single_choice', 'multiple_choice'].includes(field.type) && (
                                    <Input
                                        disabled={locked}
                                        placeholder="Opsi, pisahkan dengan koma"
                                        value={(field.options ?? []).join(', ')}
                                        onChange={(e) =>
                                            updateField(index, {
                                                options: e.target.value
                                                    .split(',')
                                                    .map((item) => item.trim())
                                                    .filter(Boolean),
                                            })
                                        }
                                    />
                                )}
                                <div className="flex flex-wrap gap-4 text-sm">
                                    <label className="flex items-center gap-2">
                                        <Checkbox
                                            checked={field.required}
                                            disabled={locked}
                                            onCheckedChange={(checked) => updateField(index, { required: checked === true })}
                                        />
                                        Wajib
                                    </label>
                                    <label className="flex items-center gap-2">
                                        <Checkbox
                                            checked={field.visible_to_client}
                                            disabled={locked}
                                            onCheckedChange={(checked) => updateField(index, { visible_to_client: checked === true })}
                                        />
                                        Terlihat client
                                    </label>
                                    <label className="flex items-center gap-2">
                                        <Checkbox
                                            checked={field.is_identity}
                                            disabled={locked}
                                            onCheckedChange={(checked) => updateField(index, { is_identity: checked === true })}
                                        />
                                        Identitas
                                    </label>
                                    <label className="flex items-center gap-2">
                                        <Checkbox
                                            checked={field.show_on_dashboard}
                                            disabled={locked}
                                            onCheckedChange={(checked) => updateField(index, { show_on_dashboard: checked === true })}
                                        />
                                        Dashboard
                                    </label>
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
                                        Hapus field
                                    </Button>
                                )}
                            </div>
                        ))}
                        {!locked && (
                            <Button type="button" variant="outline" onClick={() => setData('fields', [...data.fields, emptyField()])}>
                                Tambah field
                            </Button>
                        )}
                        <InputError message={errors.fields} />
                        <InputError message={(errors as Record<string, string>).form} />
                    </div>
                    {!locked && <Button disabled={processing}>Simpan form</Button>}
                </form>
                {template && (
                    <DangerZone
                        title="Hapus form"
                        description="Form masih draf dan belum dipakai di penugasan SPG."
                        confirmTitle={`Hapus form ${template.name} v${template.version}?`}
                        deleteUrl={route('forms.destroy', template.id)}
                        blocker={deleteBlocker}
                        errorKey="form"
                    />
                )}
            </FormPage>
        </AppLayout>
    );
}
