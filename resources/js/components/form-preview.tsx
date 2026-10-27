import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { label } from '@/lib/format';

export interface PreviewField {
    key: string;
    label: string;
    type: string;
    required: boolean;
    options?: string[] | null;
}

/**
 * Read-only rendering of a form as consumers see it on the public link.
 */
export default function FormPreview({
    fields,
    requiresConsent,
    consentText,
}: {
    fields: PreviewField[];
    requiresConsent: boolean;
    consentText: string | null;
}) {
    return (
        <div className="space-y-4 rounded-xl border p-4 sm:p-6">
            {fields.map((field, index) => (
                <div key={index} className="grid gap-2">
                    <Label>
                        {field.label || <span className="text-muted-foreground italic">Untitled field</span>}
                        {field.required ? ' *' : ''}
                    </Label>
                    {field.type === 'yes_no' ? (
                        <div className="grid grid-cols-2 gap-2">
                            <Button type="button" variant="outline" className="h-14">
                                Yes
                            </Button>
                            <Button type="button" variant="outline" className="h-14">
                                No
                            </Button>
                        </div>
                    ) : ['single_choice', 'multiple_choice'].includes(field.type) ? (
                        <div className="grid gap-2">
                            {(field.options ?? []).length === 0 && <p className="text-muted-foreground text-sm">No options yet</p>}
                            {(field.options ?? []).map((option) => (
                                <label key={option} className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm">
                                    {field.type === 'single_choice' ? (
                                        <span className="border-input size-4 rounded-full border" />
                                    ) : (
                                        <Checkbox disabled />
                                    )}
                                    {option}
                                </label>
                            ))}
                        </div>
                    ) : field.type === 'long_text' ? (
                        <textarea className="border-input min-h-24 rounded-md border p-2" disabled />
                    ) : (
                        <Input disabled placeholder={label('fieldType', field.type)} />
                    )}
                </div>
            ))}
            {requiresConsent && (
                <label className="flex items-start gap-2 text-sm">
                    <Checkbox disabled className="mt-0.5" />
                    <span>{consentText || <span className="text-muted-foreground italic">Consent text</span>}</span>
                </label>
            )}
        </div>
    );
}
