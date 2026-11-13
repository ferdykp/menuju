export function formatDateOnly(value, locale = 'id-ID') {
    if (!value) return 'Tanpa tanggal';
    const datePart = String(value).slice(0, 10);
    const date = new Date(`${datePart}T12:00:00.000Z`);
    if (Number.isNaN(date.getTime())) return 'Tanggal tidak valid';
    return date.toLocaleDateString(locale, {
        timeZone: 'UTC',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
