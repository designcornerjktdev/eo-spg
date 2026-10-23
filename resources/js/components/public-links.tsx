import IconAction from '@/components/icon-action';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Check, Copy, ExternalLink, QrCode } from 'lucide-react';
import QRCode from 'qrcode';
import { useEffect, useState } from 'react';

export interface LinkLocation {
    id: number;
    name: string;
    area: string | null;
    public_token: string;
    submissions_count: number;
}

/**
 * One public form link per campaign location, with copy, open and printable QR code.
 */
export default function PublicLinks({ locations, accepting, campaignName }: { locations: LinkLocation[]; accepting: boolean; campaignName: string }) {
    const [qrFor, setQrFor] = useState<LinkLocation | null>(null);

    if (locations.length === 0) {
        return <p className="text-muted-foreground text-sm">Add a location to get a public link.</p>;
    }

    return (
        <>
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50">
                        <tr>
                            <th className="px-3 py-2 text-left">Location</th>
                            <th className="px-3 py-2 text-left">Link</th>
                            <th className="px-3 py-2 text-left">Entries</th>
                            <th className="px-3 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {locations.map((location) => {
                            const url = route('public-form.show', location.public_token);
                            return (
                                <tr key={location.id} className="border-t">
                                    <td className="px-3 py-2">
                                        {location.name}
                                        {location.area && <span className="text-muted-foreground"> · {location.area}</span>}
                                    </td>
                                    <td className="max-w-72 truncate px-3 py-2 font-mono text-xs" title={url}>
                                        {url}
                                    </td>
                                    <td className="px-3 py-2">{location.submissions_count}</td>
                                    <td className="px-3 py-1">
                                        <div className="flex gap-1">
                                            <CopyButton text={url} />
                                            <IconAction icon={QrCode} label="Show QR code" onClick={() => setQrFor(location)} />
                                            <a
                                                href={url}
                                                target="_blank"
                                                rel="noreferrer"
                                                aria-label="Open form"
                                                title="Open form"
                                                className="hover:bg-accent inline-flex size-8 items-center justify-center rounded-md"
                                            >
                                                <ExternalLink className="size-4" />
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
            <p className="text-muted-foreground mt-2 text-xs">
                {accepting ? <Badge variant="default">Open</Badge> : <Badge variant="outline">Closed</Badge>}{' '}
                {accepting
                    ? 'Links accept responses while the campaign is active.'
                    : 'Links open once the campaign is active and has a published form.'}
            </p>

            <QrDialog location={qrFor} campaignName={campaignName} onClose={() => setQrFor(null)} />
        </>
    );
}

function CopyButton({ text }: { text: string }) {
    const [copied, setCopied] = useState(false);

    return (
        <IconAction
            icon={copied ? Check : Copy}
            label={copied ? 'Copied' : 'Copy link'}
            onClick={async () => {
                await navigator.clipboard.writeText(text);
                setCopied(true);
                setTimeout(() => setCopied(false), 1500);
            }}
        />
    );
}

function QrDialog({ location, campaignName, onClose }: { location: LinkLocation | null; campaignName: string; onClose: () => void }) {
    const [dataUrl, setDataUrl] = useState<string | null>(null);
    const url = location ? route('public-form.show', location.public_token) : '';

    useEffect(() => {
        setDataUrl(null);
        if (url) QRCode.toDataURL(url, { width: 512, margin: 2 }).then(setDataUrl);
    }, [url]);

    const filename = `${campaignName}-${location?.name ?? 'qr'}.png`.replace(/[^\w.-]+/g, '-').toLowerCase();

    return (
        <Dialog open={!!location} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-[calc(100%-1rem)] md:max-w-sm">
                <DialogHeader className="text-left">
                    <DialogTitle>{location?.name}</DialogTitle>
                    <DialogDescription>Print this QR code at the location so consumers can open the form.</DialogDescription>
                </DialogHeader>
                <div className="flex justify-center">
                    {dataUrl ? (
                        <img src={dataUrl} alt={`QR code for ${location?.name}`} className="size-64" />
                    ) : (
                        <div className="bg-muted size-64 animate-pulse rounded" />
                    )}
                </div>
                <Button asChild disabled={!dataUrl}>
                    <a href={dataUrl ?? '#'} download={filename}>
                        Download PNG
                    </a>
                </Button>
            </DialogContent>
        </Dialog>
    );
}
