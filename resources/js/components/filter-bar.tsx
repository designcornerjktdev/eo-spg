import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export interface FilterSelect {
    name: string;
    label: string;
    options: { value: string | number; label: string }[];
}

/**
 * Search + dropdown filters for list pages. Values are sent as a query string to `url`.
 */
export default function FilterBar({
    url,
    filters,
    searchPlaceholder,
    selects = [],
}: {
    url: string;
    filters: Record<string, string | number | null | undefined>;
    searchPlaceholder: string;
    selects?: FilterSelect[];
}) {
    const [search, setSearch] = useState(String(filters.search ?? ''));

    const apply = (next: Record<string, string>) => {
        router.get(url, { ...filters, ...next }, { preserveState: true, replace: true });
    };

    return (
        <form
            className="flex flex-wrap gap-2"
            onSubmit={(e) => {
                e.preventDefault();
                apply({ search });
            }}
        >
            <Input className="w-full sm:w-64" placeholder={searchPlaceholder} value={search} onChange={(e) => setSearch(e.target.value)} />
            {selects.map((select) => (
                <select
                    key={select.name}
                    aria-label={select.label}
                    className="border-input bg-background h-9 rounded-md border px-2 text-sm"
                    value={String(filters[select.name] ?? '')}
                    onChange={(e) => apply({ [select.name]: e.target.value })}
                >
                    <option value="">{select.label}</option>
                    {select.options.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            ))}
            <Button type="submit" variant="outline">
                Search
            </Button>
        </form>
    );
}
