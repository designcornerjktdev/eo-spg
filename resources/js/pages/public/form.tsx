import AppLogoIcon from '@/components/app-logo-icon';
import FormFieldInput, { type Answer, type DynamicField, emptyAnswer, isWideField } from '@/components/form-field-input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { type DevicePosition, type PositionStatus, useDevicePosition } from '@/hooks/use-device-position';
import { distanceInMeters, formatDistance, MAX_ACCURACY_CREDIT_METERS } from '@/lib/geo';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle, MapPin, MapPinOff } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface Geofence {
    latitude: number;
    longitude: number;
    radius: number;
}

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
    geofence,
    form,
}: {
    token: string;
    campaign: string;
    location: string;
    open: boolean;
    geofence: Geofence | null;
    form: PublicForm | null;
}) {
    const { branding, flash } = usePage<SharedData & { flash: { submitted?: boolean } }>().props;
    const [showForm, setShowForm] = useState(!flash.submitted);
    const accepting = open && form !== null;
    // Locate the visitor as soon as the page opens so an off-site visitor is told before filling anything in.
    const device = useDevicePosition(accepting && geofence !== null);
    const distance = geofence && device.position ? distanceFrom(geofence, device.position) : null;
    const onSite = !geofence || (device.status === 'ready' && distance !== null && distance.outside === 0);

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
                    {!accepting || !form ? (
                        <p className="text-muted-foreground py-6 text-center">This form is not accepting responses right now.</p>
                    ) : showForm ? (
                        <>
                            {geofence && !onSite && (
                                <LocationGate
                                    status={device.status}
                                    distance={distance?.meters ?? null}
                                    location={location}
                                    onRetry={device.refresh}
                                />
                            )}
                            {/* Kept mounted while hidden so answers survive a failed re-check at submit time. */}
                            <div className={cn(!onSite && 'hidden')}>
                                <EntryForm
                                    token={token}
                                    form={form}
                                    location={location}
                                    device={geofence ? device : null}
                                    onDone={() => setShowForm(false)}
                                />
                            </div>
                        </>
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

function EntryForm({
    token,
    form,
    location,
    device,
    onDone,
}: {
    token: string;
    form: PublicForm;
    location: string;
    device: ReturnType<typeof useDevicePosition> | null;
    onDone: () => void;
}) {
    const [locating, setLocating] = useState(false);
    // Keys of photo fields still being optimized; submitting now would send them empty.
    const [busyFields, setBusyFields] = useState<string[]>([]);
    const preparing = busyFields.length > 0;
    const { data, setData, post, transform, processing, errors, reset } = useForm<{
        answers: Record<string, Answer>;
        consent_accepted: boolean;
        website: string;
    }>({
        answers: Object.fromEntries(form.fields.map((field) => [field.key, emptyAnswer(field)])),
        consent_accepted: false,
        website: '',
    });
    const fieldErrors = errors as Record<string, string>;

    const submit = async (event: FormEvent) => {
        event.preventDefault();

        // Re-read the position right before sending so a stale fix cannot be reused.
        let position = null;
        if (device) {
            setLocating(true);
            position = await device.refresh();
            setLocating(false);
            if (!position) return;
        }

        transform((payload) => ({ ...payload, position }));
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
                            onBusyChange={(busy) => setBusyFields((keys) => (busy ? [...keys, field.key] : keys.filter((key) => key !== field.key)))}
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

            {device?.position && (
                <p className="flex items-center gap-2 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                    <MapPin className="size-4 shrink-0" />
                    You're at {location} (±{Math.round(device.position.accuracy)} m). We'll check again when you submit.
                </p>
            )}
            <InputError message={fieldErrors.position} />

            <Button
                type="submit"
                className="h-12 w-full"
                disabled={processing || locating || preparing || (device !== null && device.status !== 'ready')}
            >
                {(processing || locating || preparing) && <LoaderCircle className="size-4 animate-spin" />}
                {locating ? 'Checking your location…' : preparing ? 'Preparing photo…' : 'Submit'}
            </Button>
        </form>
    );
}

/** Same rule as the server's Geofence: GPS accuracy is credited up to a cap. */
function distanceFrom(geofence: Geofence, position: DevicePosition) {
    const meters = Math.round(distanceInMeters(geofence.latitude, geofence.longitude, position.latitude, position.longitude));
    const credit = Math.min(position.accuracy, MAX_ACCURACY_CREDIT_METERS);

    return { meters, outside: Math.max(0, meters - credit - geofence.radius) };
}

function LocationGate({
    status,
    distance,
    location,
    onRetry,
}: {
    status: PositionStatus;
    distance: number | null;
    location: string;
    onRetry: () => void;
}) {
    if (status === 'idle' || status === 'locating') {
        return (
            <div className="flex flex-col items-center gap-3 py-8 text-center">
                <LoaderCircle className="text-muted-foreground size-8 animate-spin" />
                <p className="text-muted-foreground text-sm">Checking that you're at {location}…</p>
            </div>
        );
    }

    const message =
        status === 'ready'
            ? `Sorry, this form can only be filled in at ${location}. You appear to be ${formatDistance(distance ?? 0)} away.`
            : status === 'denied'
              ? `This form can only be filled in at ${location}. Allow location access in your browser settings, then try again.`
              : "We couldn't get your location. Turn on location services and try again.";

    return (
        <div className="flex flex-col items-center gap-4 py-6 text-center">
            <MapPinOff className="size-12 text-amber-600" />
            <div className="space-y-1">
                <h2 className="text-lg font-semibold">{status === 'ready' ? 'Outside the event area' : 'Location needed'}</h2>
                <p className="text-muted-foreground text-sm">{message}</p>
            </div>
            <Button variant="outline" onClick={onRetry}>
                Try again
            </Button>
        </div>
    );
}
