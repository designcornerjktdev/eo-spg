import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { Eye, EyeOff } from 'lucide-react';
import * as React from 'react';

/**
 * Password field with a show/hide toggle. Accepts the same props as Input (except `type`).
 */
const PasswordInput = React.forwardRef<HTMLInputElement, Omit<React.ComponentProps<'input'>, 'type'>>(({ className, ...props }, ref) => {
    const [visible, setVisible] = React.useState(false);

    return (
        <div className="relative">
            <Input ref={ref} type={visible ? 'text' : 'password'} className={cn('pr-10', className)} {...props} />
            <button
                type="button"
                // Keep focus in the field; tabIndex -1 keeps the tab order on the form fields.
                tabIndex={-1}
                onMouseDown={(e) => e.preventDefault()}
                onClick={() => setVisible((v) => !v)}
                className="text-muted-foreground hover:text-foreground absolute inset-y-0 right-0 flex w-10 items-center justify-center rounded-r-md disabled:opacity-50"
                aria-label={visible ? 'Hide password' : 'Show password'}
                aria-pressed={visible}
                disabled={props.disabled}
            >
                {visible ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
            </button>
        </div>
    );
});

PasswordInput.displayName = 'PasswordInput';

export { PasswordInput };
