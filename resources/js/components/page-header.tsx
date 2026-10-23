import Link from '@/components/link';
import { Button } from '@/components/ui/button';
import Heading from '@/components/heading';

interface Action {
    href: string;
    label: string;
}

export default function PageHeader({ title, description, action }: { title: string; description?: string; action?: Action }) {
    return (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading title={title} description={description} />
            {action && (
                <Button asChild>
                    <Link href={action.href}>{action.label}</Link>
                </Button>
            )}
        </div>
    );
}
