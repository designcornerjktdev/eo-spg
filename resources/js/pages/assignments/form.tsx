import InputError from '@/components/input-error';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface CampaignOption {
    id: number;
    name: string;
    locations: { id: number; name: string }[];
    form_templates: { id: number; name: string; version: number }[];
}

interface Assignment {
    id: number;
    campaign_id: number;
    location_id: number;
    form_template_id: number;
    user_id: number;
    starts_at: string;
    ends_at: string;
}

export default function AssignmentForm({
    assignment,
    campaigns,
    spgs,
    selectedCampaignId,
}: {
    assignment?: Assignment;
    campaigns: CampaignOption[];
    spgs: { id: number; name: string }[];
    selectedCampaignId?: number | null;
}) {
    const { data, setData, post, put, processing, errors } = useForm({
        campaign_id: (assignment?.campaign_id ?? selectedCampaignId ?? '').toString(),
        location_id: assignment?.location_id?.toString() ?? '',
        form_template_id: assignment?.form_template_id?.toString() ?? '',
        user_id: assignment?.user_id?.toString() ?? '',
        starts_at: assignment?.starts_at?.slice(0, 16) ?? '',
        ends_at: assignment?.ends_at?.slice(0, 16) ?? '',
    });

    const campaign = campaigns.find((item) => item.id.toString() === data.campaign_id);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (assignment) {
            put(route('assignments.update', assignment.id));
        } else {
            post(route('assignments.store'));
        }
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Assignment', href: '/assignments' }, { title: assignment ? 'Ubah' : 'Tambah', href: '#' }]}>
            <Head title={assignment ? 'Ubah assignment' : 'Tambah assignment'} />
            <PageHeader title={assignment ? 'Ubah assignment' : 'Tugaskan SPG'} description="Sistem menolak jadwal yang bentrok." />
            <form onSubmit={submit} className="grid max-w-xl gap-4">
                <SelectField label="Campaign" value={data.campaign_id} error={errors.campaign_id} onChange={(value) => setData('campaign_id', value)} options={campaigns.map((item) => ({ id: item.id, name: item.name }))} />
                <SelectField label="Lokasi" value={data.location_id} error={errors.location_id} onChange={(value) => setData('location_id', value)} options={campaign?.locations ?? []} />
                <SelectField
                    label="Form"
                    value={data.form_template_id}
                    error={errors.form_template_id}
                    onChange={(value) => setData('form_template_id', value)}
                    options={(campaign?.form_templates ?? []).map((form) => ({ id: form.id, name: `${form.name} v${form.version}` }))}
                />
                <SelectField label="SPG" value={data.user_id} error={errors.user_id} onChange={(value) => setData('user_id', value)} options={spgs} />
                <div className="grid gap-2">
                    <Label>Mulai</Label>
                    <Input type="datetime-local" value={data.starts_at} onChange={(e) => setData('starts_at', e.target.value)} />
                    <InputError message={errors.starts_at} />
                </div>
                <div className="grid gap-2">
                    <Label>Selesai</Label>
                    <Input type="datetime-local" value={data.ends_at} onChange={(e) => setData('ends_at', e.target.value)} />
                    <InputError message={errors.ends_at} />
                </div>
                <Button disabled={processing}>Simpan</Button>
            </form>
        </AppLayout>
    );
}

function SelectField({
    label,
    value,
    options,
    onChange,
    error,
}: {
    label: string;
    value: string;
    options: { id: number; name: string }[];
    onChange: (value: string) => void;
    error?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            <select className="border-input h-9 rounded-md border px-2" value={value} onChange={(e) => onChange(e.target.value)}>
                <option value="">Pilih</option>
                {options.map((option) => (
                    <option key={option.id} value={option.id}>
                        {option.name}
                    </option>
                ))}
            </select>
            <InputError message={error} />
        </div>
    );
}
