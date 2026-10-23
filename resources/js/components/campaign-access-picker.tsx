import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { label } from '@/lib/format';

export interface CampaignOption {
    id: number;
    name: string;
    status: string;
}

/**
 * Checklist of campaigns a client account may view (dashboard and entries, read-only).
 */
export default function CampaignAccessPicker({
    campaigns,
    value,
    onChange,
    error,
}: {
    campaigns: CampaignOption[];
    value: number[];
    onChange: (ids: number[]) => void;
    error?: string;
}) {
    const toggle = (id: number, checked: boolean) => onChange(checked ? [...value, id] : value.filter((item) => item !== id));

    return (
        <div className="grid gap-2">
            <Label>Campaign access</Label>
            <p className="text-muted-foreground text-xs">This account can only view the dashboard and entries of the campaigns you tick.</p>
            {campaigns.length === 0 ? (
                <p className="text-muted-foreground rounded-md border border-dashed p-3 text-sm">No campaigns yet.</p>
            ) : (
                <div className="max-h-64 divide-y overflow-y-auto rounded-md border">
                    {campaigns.map((campaign) => (
                        <label key={campaign.id} className="hover:bg-muted/50 flex cursor-pointer items-center gap-3 px-3 py-2 text-sm">
                            <input type="checkbox" checked={value.includes(campaign.id)} onChange={(e) => toggle(campaign.id, e.target.checked)} />
                            <span className="flex-1">{campaign.name}</span>
                            <Badge variant={campaign.status === 'active' ? 'default' : 'secondary'}>{label('campaignStatus', campaign.status)}</Badge>
                        </label>
                    ))}
                </div>
            )}
            <InputError message={error} />
        </div>
    );
}
