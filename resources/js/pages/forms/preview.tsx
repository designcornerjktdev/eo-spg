import PageHeader from '@/components/page-header';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Field {
    key: string;
    label: string;
    type: string;
    required: boolean;
    options?: string[] | null;
}

export default function FormPreview({
    template,
}: {
    template: { id: number; name: string; version: number; requires_consent: boolean; consent_text: string | null; fields: Field[] };
}) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Form', href: '/forms' }, { title: 'Pratinjau', href: '#' }]}>
            <Head title={`Pratinjau ${template.name}`} />
            <PageHeader title={`Pratinjau ${template.name} v${template.version}`} />
            <div className="mx-auto w-full max-w-md space-y-4 rounded-xl border p-4">
                {template.fields.map((field) => (
                    <div key={field.key} className="grid gap-2">
                        <Label>
                            {field.label}
                            {field.required ? ' *' : ''}
                        </Label>
                        {field.type === 'yes_no' ? (
                            <div className="grid grid-cols-2 gap-2">
                                <Button type="button" variant="outline" className="h-14">
                                    Ya
                                </Button>
                                <Button type="button" variant="outline" className="h-14">
                                    Tidak
                                </Button>
                            </div>
                        ) : field.type === 'long_text' ? (
                            <textarea className="border-input min-h-24 rounded-md border p-2" disabled />
                        ) : (
                            <Input disabled placeholder={field.type} />
                        )}
                    </div>
                ))}
                {template.requires_consent && <p className="text-sm">{template.consent_text}</p>}
            </div>
            <Button asChild variant="outline">
                <Link href={route('forms.edit', template.id)}>Kembali</Link>
            </Button>
        </AppLayout>
    );
}
