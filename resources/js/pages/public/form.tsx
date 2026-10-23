import AppLogoIcon from '@/components/app-logo-icon';
import FormFieldInput, { type Answer, type DynamicField, emptyAnswer, isWideField } from '@/components/form-field-input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface PublicForm {
    name: string;
    requires_consent: boolean;
    consent_text: string | null;
    fields: DynamicField[];
}

/**
 * Public, no-login form that consumers open from a campaign location's link or QR code.
 */
export default function PublicFormPage({
    token,
    campaign,
    location,
    open,
    form,
}: {
    token: string;
    campaign: string;
    location: string;
    open: boolean;
    form: PublicForm | null;
}) {
    const { branding, flash } = usePage<SharedData & { flash: { submitted?: boolean } }>().props;
    const [showForm, setShowForm] = useState(!flash.submitted);

    return (
        <div className="bg-muted/40 flex min-h-svh flex-col items-center px-4 py-8 sm:py-12">
            <Head title={form?.name ?? campaign} />
            <div className="w-full max-w-xl space-y-6">
                <header className="flex flex-col items-center gap-4 text-center">
                    {/* EO brand: logo from Settings → Company, falling back to the app icon. */}
                    <div className="flex items-center gap-3">
                        {branding.logo_url ? (
                            <img src={branding.logo_url} alt="" className="size-12 rounded-lg object-contain" />
                        ) : (
                            <div className="bg-primary text-primary-foreground flex size-12 items-center justify-center rounded-lg">
                                <AppLogoIcon className="size-7 fill-current" />
                            </div>
                        )}
                        <span className="text-lg font-semibold">{branding.name}</span>
                    </div>
                    <div className="space-y-0.5">
                        <h1 className="text-xl font-semibold">{campaign}</h1>
                        <p className="text-muted-foreground text-sm">{location}</p>
                    </div>
                </header>

                <main className="bg-background rounded-xl border p-4 shadow-sm sm:p-6">
                    {!open || !form ? (
                        <p className="text-muted-foreground py-6 text-center">This form is not accepting responses right now.</p>
                    ) : showForm ? (
                        <EntryForm token={token} form={form} onDone={() => setShowForm(false)} />
                    ) : (
                        <div className="flex flex-col items-center gap-4 py-6 text-center">
                            <CheckCircle2 className="size-12 text-green-600" />
                            <div>
                                <h2 className="text-lg font-semibold">Thank you!</h2>
                                <p className="text-muted-foreground text-sm">Your response has been recorded.</p>
                            </div>
                            <Button variant="outline" onClick={() => setShowForm(true)}>
                                Submit another response
                            </Button>
                        </div>
                    )}
                </main>

                <p className="text-muted-foreground text-center text-xs">Powered by {branding.name}</p>
            </div>
        </div>
    );
}

function EntryForm({ token, form, onDone }: { token: string; form: PublicForm; onDone: () => void }) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        answers: Record<string, Answer>;
        consent_accepted: boolean;
        website: string;
    }>({
        answers: Object.fromEntries(form.fields.map((field) => [field.key, emptyAnswer(field)])),
        consent_accepted: false,
        website: '',
    });
    const fieldErrors = errors as Record<string, string>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(route('public-form.store', token), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onDone();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
        });
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <h2 className="text-lg font-semibold">{form.name}</h2>

            <div className="grid gap-5 sm:grid-cols-2 sm:gap-x-6">
                {form.fields.map((field) => (
                    <div key={field.key} className={cn('grid content-start gap-2', isWideField(field) && 'sm:col-span-2')}>
                        <Label htmlFor={`field-${field.key}`} id={`field-${field.key}-label`}>
                            {field.label}
                            {field.required && <span className="text-destructive"> *</span>}
                        </Label>
                        <FormFieldInput
                            field={field}
                            value={data.answers[field.key]}
                            // Functional update: quick successive answers must not overwrite each other.
                            onChange={(value) => setData((current) => ({ ...current, answers: { ...current.answers, [field.key]: value } }))}
                        />
                        <InputError message={fieldErrors[`answers.${field.key}`]} />
                    </div>
                ))}
            </div>

            {/* Honeypot: hidden from people, bots tend to fill every field. */}
            <input
                type="text"
                name="website"
                tabIndex={-1}
                autoComplete="off"
                aria-hidden="true"
                className="absolute -left-[9999px] size-px opacity-0"
                value={data.website}
                onChange={(e) => setData('website', e.target.value)}
            />

            {form.requires_consent && (
                <label className="flex items-start gap-3 text-sm">
                    <Checkbox
                        checked={data.consent_accepted}
                        onCheckedChange={(checked) => setData('consent_accepted', checked === true)}
                        className="mt-0.5"
                    />
                    <span>{form.consent_text}</span>
                </label>
            )}
            <InputError message={fieldErrors.consent_accepted ?? fieldErrors.form} />

            <Button type="submit" className="h-12 w-full" disabled={processing}>
                {processing && <LoaderCircle className="size-4 animate-spin" />}
                Submit
            </Button>
        </form>
    );
}
