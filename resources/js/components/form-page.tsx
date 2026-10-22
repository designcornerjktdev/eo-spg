import { cn } from '@/lib/utils';

const widths = {
    md: 'max-w-2xl',
    lg: 'max-w-3xl',
    xl: 'max-w-4xl',
};

/**
 * Form page wrapper: title, form and delete section are centred at a comfortable reading width.
 */
export default function FormPage({ children, width = 'md' }: { children: React.ReactNode; width?: keyof typeof widths }) {
    return <div className={cn('mx-auto w-full space-y-6 py-2', widths[width])}>{children}</div>;
}
