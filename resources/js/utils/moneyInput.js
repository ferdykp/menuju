export function parseMoneyDraft(value) {
    const cleaned = String(value ?? '').trim().replace(/[^\d.,-]/g, '');
    if (!cleaned || !/[\d]/.test(cleaned)) return { value: '', display: '' };
    const negative = cleaned.startsWith('-');
    const raw = cleaned.replace(/-/g, '');
    const lastComma = raw.lastIndexOf(',');
    const lastDot = raw.lastIndexOf('.');
    let decimal = '';
    if (lastComma >= 0) decimal = ',';
    else if (lastDot >= 0 && raw.length - lastDot - 1 <= 2 && raw.indexOf('.') === lastDot) decimal = '.';
    const splitAt = decimal ? raw.lastIndexOf(decimal) : -1;
    const integerRaw = (splitAt >= 0 ? raw.slice(0, splitAt) : raw).replace(/[.,]/g, '');
    const integer = integerRaw.replace(/^0+(?=\d)/, '') || '0';
    const fraction = splitAt >= 0 ? raw.slice(splitAt + 1).replace(/\D/g, '').slice(0, 2) : '';
    const grouped = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(integer));
    const display = `${negative ? '-' : ''}${grouped}${decimal ? `,${fraction}` : ''}`;
    const numeric = `${negative ? '-' : ''}${integer}${decimal && fraction ? `.${fraction}` : ''}`;
    return { value: numeric, display };
}

export function formatMoneyModel(value) {
    if (value === '' || value === null || value === undefined) return '';
    const parsed = parseMoneyDraft(value);
    if (parsed.value === '') return '';
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(parsed.value));
}
