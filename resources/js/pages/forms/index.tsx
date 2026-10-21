import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';

interface TemplateRow {
    id: number;
    name: string;
    version: number;
    status: string;
    campaign: { name: string };
}

export default function FormsIndex({ templates }: { templates: { data: TemplateRow[]; links: { url: string | null; label: string; active: boolean }[] } }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Form', href: '/forms' }]}>
            <Head title="Form" />
            <PageHeader title="Form builder" description="Form terkunci setelah submission pertama." action={{ href: route('forms.create'), label: 'Buat form' }} />
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Nama</th>
                            <th className="px-3 py-2 text-left">Campaign</th>
                            <th className="px-3 py-2 text-left">Versi</th>
                            <th className="px-3 py-2 text-left">Status</th>
                            <th />
                        </tr>
                    </thead>
                    <tbody>
                        {templates.data.map((template) => (
                            <tr key={template.id} className="border-t">
                                <td className="px-3 py-2">{template.name}</td>
                                <td className="px-3 py-2">{template.campaign.name}</td>
                                <td className="px-3 py-2">{template.version}</td>
                                <td className="px-3 py-2">{template.status}</td>
                                <td className="px-3 py-2 text-right">
                                    <Button asChild size="sm" variant="outline">
                                        <Link href={route('forms.edit', template.id)}>Kelola</Link>
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <Pagination links={templates.links} />
        </AppLayout>
    );
}
