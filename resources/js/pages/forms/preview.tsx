import FormPage from '@/components/form-page';
import FormPreviewCard, { PreviewField as Field } from '@/components/form-preview';
import Link from '@/components/link';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function FormPreview({
    template,
}: {
    template: { id: number; name: string; version: number; requires_consent: boolean; consent_text: string | null; fields: Field[] };
}) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Form', href: '/forms' },
                { title: 'Preview', href: '#' },
            ]}
        >
            <Head title={`Preview ${template.name}`} />
            <FormPage>
                <PageHeader
                    title={`Preview ${template.name} v${template.version}`}
                    description="How the form looks to consumers on the public link."
                />
                <FormPreviewCard fields={template.fields} requiresConsent={template.requires_consent} consentText={template.consent_text} />
                <div className="flex justify-end gap-2">
                    <Button asChild variant="outline">
                        <Link href={route('forms.index')}>Back to list</Link>
                    </Button>
                    <Button asChild>
                        <Link href={route('forms.edit', template.id)}>Manage form</Link>
                    </Button>
                </div>
            </FormPage>
        </AppLayout>
    );
}
