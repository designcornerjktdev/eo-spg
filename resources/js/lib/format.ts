// Shared display formatting: dates/times in the app timezone and human labels for enum codes from the server.

const TIME_ZONE = 'Asia/Jakarta';
const LOCALE = 'en-GB';

function toDate(value: string | null | undefined): Date | null {
    if (!value) return null;
    // A date without a time ("2026-10-21") is read as a local date, not UTC midnight.
    const date = /^\d{4}-\d{2}-\d{2}$/.test(value) ? new Date(`${value}T00:00:00+07:00`) : new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
}

/** 21 Oct 2026 */
export function formatDate(value: string | null | undefined): string {
    const date = toDate(value);
    return date ? date.toLocaleDateString(LOCALE, { day: 'numeric', month: 'short', year: 'numeric', timeZone: TIME_ZONE }) : (value ?? '-');
}

/** 21 Oct 2026, 08:05 */
export function formatDateTime(value: string | null | undefined): string {
    const date = toDate(value);
    return date
        ? date.toLocaleString(LOCALE, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: TIME_ZONE })
        : (value ?? '-');
}

/** 21 Oct 2026 – 4 Nov 2026 */
export function formatDateRange(start: string | null | undefined, end: string | null | undefined): string {
    return `${formatDate(start)} – ${formatDate(end)}`;
}

/** 21 Oct 2026, 08:05 – 17:05 (end time only when on the same day) */
export function formatDateTimeRange(start: string | null | undefined, end: string | null | undefined): string {
    const startDate = toDate(start);
    const endDate = toDate(end);
    if (startDate && endDate && formatDate(start) === formatDate(end)) {
        return `${formatDateTime(start)} – ${endDate.toLocaleTimeString(LOCALE, { hour: '2-digit', minute: '2-digit', timeZone: TIME_ZONE })}`;
    }
    return `${formatDateTime(start)} – ${formatDateTime(end)}`;
}

const labels = {
    campaignStatus: { draft: 'Draft', active: 'Active', closed: 'Closed' },
    formStatus: { draft: 'Draft', published: 'Published', locked: 'Locked' },
    submissionStatus: { submitted: 'Submitted', void: 'Voided' },
    role: { superadmin: 'Super admin', admin: 'Admin', client: 'Client' },
    fieldType: {
        short_text: 'Short text',
        long_text: 'Long text',
        number: 'Number',
        phone: 'Phone number',
        single_choice: 'Single choice',
        multiple_choice: 'Multiple choice',
        yes_no: 'Yes/No',
        image: 'Image upload',
        date: 'Date',
    },
    // Assignment and SPG-correction actions stay labelled so older audit entries remain readable.
    auditAction: {
        'campaign.created': 'Campaign created',
        'campaign.updated': 'Campaign updated',
        'campaign.activated': 'Campaign activated',
        'campaign.closed': 'Campaign closed',
        'campaign.deleted': 'Campaign deleted',
        'client.created': 'Client created',
        'client.updated': 'Client updated',
        'client.deleted': 'Client deleted',
        'product.created': 'Product created',
        'product.updated': 'Product updated',
        'product.deleted': 'Product deleted',
        'form.created': 'Form created',
        'form.updated': 'Form updated',
        'form.published': 'Form published',
        'form.versioned': 'New form version',
        'form.deleted': 'Form deleted',
        'assignment.created': 'Assignment created',
        'assignment.updated': 'Assignment updated',
        'assignment.cancelled': 'Assignment cancelled',
        'assignment.deleted': 'Assignment deleted',
        'submission.voided': 'Entry voided',
        'submission.corrected': 'Entry corrected by SPG',
        'user.created': 'Account created',
        'user.updated': 'Account updated',
        'user.deleted': 'Account deleted',
        'user.password_reset': 'Password reset',
        'settings.company_updated': 'Company settings updated',
    },
    auditableType: {
        'App\\Models\\Assignment': 'Assignment',
        'App\\Models\\Campaign': 'Campaign',
        'App\\Models\\Client': 'Client',
        'App\\Models\\FormSubmission': 'Entry',
        'App\\Models\\FormTemplate': 'Form',
        'App\\Models\\Product': 'Product',
        'App\\Models\\User': 'Account',
    },
} satisfies Record<string, Record<string, string>>;

export type LabelGroup = keyof typeof labels;

/** Human label for an enum code; unknown codes are shown as-is. */
export function label(group: LabelGroup, value: string | null | undefined): string {
    if (!value) return '-';
    return (labels[group] as Record<string, string>)[value] ?? value;
}

/** Dropdown options for one label group. */
export function labelOptions(group: LabelGroup): { value: string; label: string }[] {
    return Object.entries(labels[group]).map(([value, text]) => ({ value, label: text }));
}
