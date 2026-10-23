import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import Link from '@/components/link';
import { type LucideIcon } from 'lucide-react';

/**
 * Icon-only table action button; the label shows as a tooltip on hover and is read by screen readers.
 */
export default function IconAction({
    icon: Icon,
    label,
    href,
    onClick,
    destructive = false,
}: {
    icon: LucideIcon;
    label: string;
    href?: string;
    onClick?: () => void;
    destructive?: boolean;
}) {
    const className = cn('size-8', destructive && 'text-destructive hover:text-destructive');

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                {href ? (
                    <Button size="icon" variant="ghost" className={className} asChild>
                        <Link href={href} aria-label={label}>
                            <Icon className="size-4" />
                        </Link>
                    </Button>
                ) : (
                    <Button size="icon" variant="ghost" className={className} aria-label={label} onClick={onClick}>
                        <Icon className="size-4" />
                    </Button>
                )}
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}
