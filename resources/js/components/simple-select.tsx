import { useState } from 'react';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';

// Radix forbids '' as an item value, so the "empty" choice (e.g. "All") is mapped to this sentinel.
const EMPTY = '__empty__';

export interface SimpleSelectOption {
    value: string;
    label: string;
}

interface SimpleSelectProps {
    options: SimpleSelectOption[];
    value?: string;
    defaultValue?: string;
    onChange?: (value: string) => void;
    /** Label of the option that stands for an empty value (e.g. "All"); omit for no such option. */
    emptyLabel?: string;
    placeholder?: string;
    /** Submits the value with a surrounding <form> through a hidden input. */
    name?: string;
    id?: string;
    disabled?: boolean;
    className?: string;
    'aria-label'?: string;
}

/**
 * shadcn Select with a native-select-like API: string values, '' for "none", optional form name.
 */
export function SimpleSelect({
    options,
    value,
    defaultValue,
    onChange,
    emptyLabel,
    placeholder,
    name,
    id,
    disabled,
    className,
    'aria-label': ariaLabel,
}: SimpleSelectProps) {
    const [internal, setInternal] = useState(defaultValue ?? '');
    const current = value ?? internal;

    const handleChange = (next: string) => {
        const real = next === EMPTY ? '' : next;
        setInternal(real);
        onChange?.(real);
    };

    // With no empty option, Radix shows the placeholder for ''.
    const radixValue = current === '' && emptyLabel !== undefined ? EMPTY : current;

    return (
        <>
            <Select value={radixValue} onValueChange={handleChange} disabled={disabled}>
                <SelectTrigger id={id} aria-label={ariaLabel} className={cn('h-9', className)}>
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {emptyLabel !== undefined && <SelectItem value={EMPTY}>{emptyLabel}</SelectItem>}
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            {name && <input type="hidden" name={name} value={current} />}
        </>
    );
}
