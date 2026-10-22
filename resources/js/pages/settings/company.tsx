import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type Branding, type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { ImageIcon } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Company settings',
        href: '/settings/company',
    },
];

export default function Company({ company }: { company: Branding }) {
    const { data, setData, post, processing, errors, recentlySuccessful, reset } = useForm<{
        company_name: string;
        logo: File | null;
        remove_logo: boolean;
    }>({
        company_name: company.name,
        logo: null,
        remove_logo: false,
    });

    // Local preview of a newly picked file before it is uploaded.
    const [preview, setPreview] = useState<string | null>(null);
    useEffect(() => {
        if (!data.logo) {
            setPreview(null);
            return;
        }
        const url = URL.createObjectURL(data.logo);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [data.logo]);

    const shownLogo = preview ?? (data.remove_logo ? null : company.logo_url);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('company.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => reset('logo', 'remove_logo'),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Company settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Company" description="Name and logo shown in the sidebar, the sign-in page and the browser tab" />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="company_name">Company name</Label>
                            <Input
                                id="company_name"
                                value={data.company_name}
                                onChange={(e) => setData('company_name', e.target.value)}
                                required
                                maxLength={100}
                            />
                            <InputError message={errors.company_name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="logo">Logo</Label>
                            <div className="flex items-center gap-4">
                                <div className="bg-muted flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border">
                                    {shownLogo ? (
                                        <img src={shownLogo} alt="Logo preview" className="size-full object-contain" />
                                    ) : (
                                        <ImageIcon className="text-muted-foreground size-8" />
                                    )}
                                </div>
                                <div className="grid gap-2">
                                    <Input
                                        id="logo"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        onChange={(e) => {
                                            setData('logo', e.target.files?.[0] ?? null);
                                            setData('remove_logo', false);
                                        }}
                                    />
                                    <p className="text-muted-foreground text-xs">PNG, JPG or WebP, max 2 MB. A square image works best.</p>
                                </div>
                            </div>
                            <InputError message={errors.logo} />
                            {company.logo_url && !data.logo && (
                                <label className="flex items-center gap-2 text-sm">
                                    <Checkbox checked={data.remove_logo} onCheckedChange={(checked) => setData('remove_logo', checked === true)} />
                                    Remove current logo
                                </label>
                            )}
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Save</Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">Saved</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
