import ProductForm from './form';

interface Product {
    id: number;
    client_id: number;
    name: string;
    sku: string | null;
    description: string | null;
}

export default function ProductEdit({ product, clients }: { product: Product; clients: { id: number; name: string }[] }) {
    return <ProductForm product={product} clients={clients} />;
}
