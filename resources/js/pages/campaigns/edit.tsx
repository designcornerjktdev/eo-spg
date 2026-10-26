import DangerZone from '@/components/danger-zone';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import CampaignForm from './form';

export default function CampaignEdit({
    campaign,
    deleteBlocker,
}: {
    campaign: NonNullable<Parameters<typeof CampaignForm>[0]['campaign']>;
    deleteBlocker: string | null;
}) {
    // Only the superadmin may delete campaigns.
    const canDelete = usePage<SharedData>().props.auth.user?.role === 'superadmin';

    return (
        <CampaignForm
            campaign={campaign}
            footer={
                canDelete && (
                    <DangerZone
                        title="Delete campaign"
                        description="This campaign is a draft with no entries yet."
                        confirmTitle={`Delete campaign ${campaign.name}?`}
                        deleteUrl={route('campaigns.destroy', campaign.id)}
                        blocker={deleteBlocker}
                        errorKey="campaign"
                    />
                )
            }
        />
    );
}
