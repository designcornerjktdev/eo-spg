import DangerZone from '@/components/danger-zone';
import ProductForm from './form';

interface Product {
    id: number;
    client_id: number;
    name: string;
    sku: string | null;
    description: string | null;
}

export default function ProductEdit({
    product,
    clients,
    deleteBlocker,
}: {
    product: Product;
    clients: { id: number; name: string }[];
    deleteBlocker: string | null;
}) {
    return (
        <ProductForm
            product={product}
            clients={clients}
            footer={
                <DangerZone
                    title="Delete product"
                    description="This product is not used by any campaign."
                    confirmTitle={`Delete product ${product.name}?`}
                    deleteUrl={route('products.destroy', product.id)}
                    blocker={deleteBlocker}
                    errorKey="product"
                />
            }
        />
    );
}
