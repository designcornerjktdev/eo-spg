import { X } from 'lucide-react';
import { KeyboardEvent, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface TagInputProps {
    value: string[];
    onChange: (value: string[]) => void;
    placeholder?: string;
    disabled?: boolean;
}

/**
 * List of string tags: type and press Enter (or the Add button) to add, click × to remove. Duplicates are ignored.
 */
export function TagInput({ value, onChange, placeholder = 'Add an option', disabled }: TagInputProps) {
    const [draft, setDraft] = useState('');

    const add = () => {
        const tag = draft.trim();
        if (tag && !value.includes(tag)) onChange([...value, tag]);
        setDraft('');
    };

    const handleKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Enter') {
            // Enter would otherwise submit the surrounding form.
            event.preventDefault();
            add();
        } else if (event.key === 'Backspace' && draft === '' && value.length > 0) {
            onChange(value.slice(0, -1));
        }
    };

    return (
        <div className="grid gap-2">
            {value.length > 0 && (
                <div className="flex flex-wrap gap-2">
                    {value.map((tag) => (
                        <Badge key={tag} variant="secondary" className="gap-1 pr-1">
                            {tag}
                            {!disabled && (
                                <button
                                    type="button"
                                    aria-label={`Remove ${tag}`}
                                    className="hover:bg-muted-foreground/20 rounded-sm p-0.5"
                                    onClick={() => onChange(value.filter((item) => item !== tag))}
                                >
                                    <X className="size-3" />
                                </button>
                            )}
                        </Badge>
                    ))}
                </div>
            )}
            {!disabled && (
                <div className="flex gap-2">
                    <Input value={draft} placeholder={placeholder} onChange={(e) => setDraft(e.target.value)} onKeyDown={handleKeyDown} />
                    <Button type="button" variant="outline" onClick={add} disabled={!draft.trim()}>
                        Add
                    </Button>
                </div>
            )}
        </div>
    );
}
