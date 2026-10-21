import CampaignForm from './form';

export default function CampaignCreate({
    clients,
    products,
}: {
    clients: { id: number; name: string }[];
    products: { id: number; name: string; client_id: number }[];
}) {
    return <CampaignForm clients={clients} products={products} />;
}
