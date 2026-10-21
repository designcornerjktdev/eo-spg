import CampaignForm from './form';

export default function CampaignEdit({
    campaign,
    clients,
    products,
}: {
    campaign: Parameters<typeof CampaignForm>[0]['campaign'];
    clients: { id: number; name: string }[];
    products: { id: number; name: string; client_id: number }[];
}) {
    return <CampaignForm campaign={campaign} clients={clients} products={products} />;
}
