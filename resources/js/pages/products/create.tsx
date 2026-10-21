import ProductForm from './form';

export default function ProductCreate({ clients }: { clients: { id: number; name: string }[] }) {
    return <ProductForm clients={clients} />;
}
