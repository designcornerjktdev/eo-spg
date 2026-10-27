import IconAction from '@/components/icon-action';
import PageHeader from '@/components/page-header';
import Pagination from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { label } from '@/lib/format';
import { Head } from '@inertiajs/react';
import { Pencil } from 'lucide-react';

interface TemplateRow {
    id: number;
    name: string;
    version: number;
    status: string;
    campaign: { name: string };
}

export default function FormsIndex({
    templates,
}: {
    templates: { data: TemplateRow[]; links: { url: string | null; label: string; active: boolean }[] };
}) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Form', href: '/forms' }]}>
            <Head title="Form" />
            <PageHeader
                title="Form builder"
                description="Forms lock after the first entry. Create a new version to change one."
                action={{ href: route('forms.create'), label: 'Create form' }}
            />
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Name</th>
                            <th className="px-3 py-2 text-left">Campaign</th>
                            <th className="px-3 py-2 text-left">Version</th>
                            <th className="px-3 py-2 text-left">Status</th>
                            <th className="px-3 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {templates.data.map((template) => (
                            <tr key={template.id} className="border-t">
                                <td className="px-3 py-2">{template.name}</td>
                                <td className="px-3 py-2">{template.campaign.name}</td>
                                <td className="px-3 py-2">v{template.version}</td>
                                <td className="px-3 py-2">
                                    <Badge variant={template.status === 'draft' ? 'outline' : 'secondary'}>
                                        {label('formStatus', template.status)}
                                    </Badge>
                                </td>
                                <td className="px-3 py-1">
                                    <div className="flex gap-1">
                                        <IconAction icon={Pencil} label="Manage form" href={route('forms.edit', template.id)} />
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {templates.data.length === 0 && (
                            <tr>
                                <td colSpan={5} className="text-muted-foreground px-3 py-8 text-center">
                                    No forms yet.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
            <Pagination links={templates.links} />
        </AppLayout>
    );
}
