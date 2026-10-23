import DangerZone from '@/components/danger-zone';
import CampaignForm from './form';

export default function CampaignEdit({
    campaign,
    deleteBlocker,
}: {
    campaign: NonNullable<Parameters<typeof CampaignForm>[0]['campaign']>;
    deleteBlocker: string | null;
}) {
    return (
        <CampaignForm
            campaign={campaign}
            footer={
                <DangerZone
                    title="Delete campaign"
                    description="This campaign is a draft with no entries yet."
                    confirmTitle={`Delete campaign ${campaign.name}?`}
                    deleteUrl={route('campaigns.destroy', campaign.id)}
                    blocker={deleteBlocker}
                    errorKey="campaign"
                />
            }
        />
    );
}
