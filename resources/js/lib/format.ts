// Format tampilan bersama: tanggal/waktu zona aplikasi dan label bahasa Indonesia untuk kode enum dari server.

const TIME_ZONE = 'Asia/Jakarta';

function toDate(value: string | null | undefined): Date | null {
    if (!value) return null;
    // Tanggal tanpa jam ("2026-10-21") dibaca sebagai tanggal lokal, bukan UTC tengah malam.
    const date = /^\d{4}-\d{2}-\d{2}$/.test(value) ? new Date(`${value}T00:00:00+07:00`) : new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
}

/** 21 Okt 2026 */
export function formatDate(value: string | null | undefined): string {
    const date = toDate(value);
    return date ? date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: TIME_ZONE }) : (value ?? '-');
}

/** 21 Okt 2026, 08.05 */
export function formatDateTime(value: string | null | undefined): string {
    const date = toDate(value);
    return date
        ? date.toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: TIME_ZONE })
        : (value ?? '-');
}

/** 21 Okt 2026 – 4 Nov 2026 */
export function formatDateRange(start: string | null | undefined, end: string | null | undefined): string {
    return `${formatDate(start)} – ${formatDate(end)}`;
}

/** 21 Okt 2026, 08.05 – 17.05 (jam akhir saja bila di hari yang sama) */
export function formatDateTimeRange(start: string | null | undefined, end: string | null | undefined): string {
    const startDate = toDate(start);
    const endDate = toDate(end);
    if (startDate && endDate && formatDate(start) === formatDate(end)) {
        return `${formatDateTime(start)} – ${endDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: TIME_ZONE })}`;
    }
    return `${formatDateTime(start)} – ${formatDateTime(end)}`;
}

const labels = {
    campaignStatus: { draft: 'Draf', active: 'Aktif', closed: 'Ditutup' },
    formStatus: { draft: 'Draf', published: 'Terbit', locked: 'Terkunci' },
    assignmentStatus: { scheduled: 'Terjadwal', active: 'Berjalan', done: 'Selesai', cancelled: 'Dibatalkan' },
    submissionStatus: { submitted: 'Terkirim', void: 'Dibatalkan' },
    role: { superadmin: 'Admin EO', client: 'Client', spg: 'SPG' },
    fieldType: {
        short_text: 'Teks pendek',
        long_text: 'Teks panjang',
        number: 'Angka',
        phone: 'Nomor telepon',
        single_choice: 'Pilihan tunggal',
        multiple_choice: 'Pilihan ganda',
        yes_no: 'Ya/Tidak',
        date: 'Tanggal',
    },
    auditAction: {
        'campaign.created': 'Campaign dibuat',
        'campaign.updated': 'Campaign diubah',
        'campaign.activated': 'Campaign diaktifkan',
        'campaign.closed': 'Campaign ditutup',
        'campaign.deleted': 'Campaign dihapus',
        'client.created': 'Client dibuat',
        'client.updated': 'Client diubah',
        'client.deleted': 'Client dihapus',
        'product.created': 'Produk dibuat',
        'product.updated': 'Produk diubah',
        'product.deleted': 'Produk dihapus',
        'form.created': 'Form dibuat',
        'form.updated': 'Form diubah',
        'form.published': 'Form diterbitkan',
        'form.versioned': 'Versi form baru',
        'form.deleted': 'Form dihapus',
        'assignment.created': 'Penugasan dibuat',
        'assignment.updated': 'Penugasan diubah',
        'assignment.cancelled': 'Penugasan dibatalkan',
        'assignment.deleted': 'Penugasan dihapus',
        'submission.voided': 'Data dibatalkan',
        'submission.corrected': 'Data dikoreksi SPG',
        'user.created': 'Akun dibuat',
        'user.updated': 'Akun diubah',
        'user.deleted': 'Akun dihapus',
        'user.password_reset': 'Password direset',
    },
    auditableType: {
        'App\\Models\\Assignment': 'Penugasan',
        'App\\Models\\Campaign': 'Campaign',
        'App\\Models\\Client': 'Client',
        'App\\Models\\FormSubmission': 'Data',
        'App\\Models\\FormTemplate': 'Form',
        'App\\Models\\Product': 'Produk',
        'App\\Models\\User': 'Akun',
    },
} satisfies Record<string, Record<string, string>>;

export type LabelGroup = keyof typeof labels;

/** Label Indonesia untuk kode enum; kode yang belum dikenal ditampilkan apa adanya. */
export function label(group: LabelGroup, value: string | null | undefined): string {
    if (!value) return '-';
    return (labels[group] as Record<string, string>)[value] ?? value;
}

/** Pilihan dropdown dari satu grup label. */
export function labelOptions(group: LabelGroup): { value: string; label: string }[] {
    return Object.entries(labels[group]).map(([value, text]) => ({ value, label: text }));
}
