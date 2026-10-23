import DangerZone from '@/components/danger-zone';
import CampaignForm from './form';

export default function CampaignEdit({
    campaign,
    clients,
    products,
    deleteBlocker,
}: {
    campaign: NonNullable<Parameters<typeof CampaignForm>[0]['campaign']>;
    clients: { id: number; name: string }[];
    products: { id: number; name: string; client_id: number }[];
    deleteBlocker: string | null;
}) {
    return (
        <CampaignForm
            campaign={campaign}
            clients={clients}
            products={products}
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
