import { withBase } from '@/lib/base';
import { Link as InertiaLink } from '@inertiajs/react';
import { ComponentProps, forwardRef } from 'react';

type LinkProps = ComponentProps<typeof InertiaLink>;

// Inertia Link that adds the subfolder base path to app-relative string hrefs.
const Link = forwardRef<HTMLAnchorElement, LinkProps>(({ href, ...props }, ref) => (
    <InertiaLink ref={ref} href={typeof href === 'string' ? withBase(href) : href} {...props} />
));
Link.displayName = 'Link';

export default Link;
