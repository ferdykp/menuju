const SIDES = new Set(['bride', 'groom', 'shared']);
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export function parseCsvRecords(source) {
    const text = String(source ?? '').replace(/^\uFEFF/, '');
    const records = [];
    let fields = [];
    let field = '';
    let quoted = false;
    let afterQuote = false;
    let line = 1;
    let recordLine = 1;

    const finishField = () => {
        fields.push(field.trim());
        field = '';
        afterQuote = false;
    };
    const finishRecord = () => {
        finishField();
        if (fields.some((value) => value !== '')) records.push({ fields, line: recordLine });
        fields = [];
        recordLine = line + 1;
    };

    for (let index = 0; index < text.length; index += 1) {
        const character = text[index];
        const newline = character === '\n' || character === '\r';

        if (quoted) {
            if (character === '"' && text[index + 1] === '"') {
                field += '"';
                index += 1;
            } else if (character === '"') {
                quoted = false;
                afterQuote = true;
            } else {
                if (newline) {
                    if (character === '\r' && text[index + 1] === '\n') index += 1;
                    field += '\n';
                    line += 1;
                } else field += character;
            }
            continue;
        }

        if (afterQuote) {
            if (character === ',') finishField();
            else if (newline) {
                finishRecord();
                if (character === '\r' && text[index + 1] === '\n') index += 1;
                line += 1;
                recordLine = line;
            } else if (!/\s/.test(character)) throw new Error(`Format kutipan CSV tidak valid di baris ${line}.`);
            continue;
        }

        if (character === '"') {
            if (field.trim() !== '') throw new Error(`Format kutipan CSV tidak valid di baris ${line}.`);
            field = '';
            quoted = true;
        } else if (character === ',') finishField();
        else if (newline) {
            finishRecord();
            if (character === '\r' && text[index + 1] === '\n') index += 1;
            line += 1;
            recordLine = line;
        } else field += character;
    }

    if (quoted) throw new Error(`Tanda kutip belum ditutup mulai baris ${recordLine}.`);
    if (field !== '' || fields.length) finishRecord();
    return records;
}

function normalizeHeader(header) {
    return header.trim().toLowerCase().replace(/[\s-]+/g, '_');
}

export function parseGuestCsv(source) {
    const records = parseCsvRecords(source);
    if (records.length < 2) throw new Error('CSV perlu header dan setidaknya satu baris nama tamu.');

    const headers = records[0].fields.map(normalizeHeader);
    const column = (...names) => headers.findIndex((header) => names.includes(header));
    const nameColumn = column('name', 'nama');
    if (nameColumn < 0) throw new Error('Tambahkan kolom name (atau nama) pada header CSV.');

    const emailColumn = column('email');
    const phoneColumn = column('phone', 'telepon');
    const groupColumn = column('group_name', 'kelompok');
    const sideColumn = column('side', 'pihak');
    const dataRows = records.slice(1);
    if (dataRows.length > 500) throw new Error('Impor dibatasi maksimal 500 baris.');

    const eligible = [];
    const invalid = [];
    for (const record of dataRows) {
        const get = (index) => index < 0 ? '' : String(record.fields[index] ?? '').trim();
        const name = get(nameColumn);
        const email = get(emailColumn).toLowerCase() || null;
        const phone = get(phoneColumn) || null;
        const groupName = get(groupColumn) || null;
        const sideText = get(sideColumn).toLowerCase();
        const side = sideText || 'shared';
        const reasons = [];

        if (!name) reasons.push('Nama wajib diisi.');
        else if (name.length > 160) reasons.push('Nama maksimal 160 karakter.');
        if (email && (!EMAIL_PATTERN.test(email) || email.length > 255)) reasons.push('Email tidak valid.');
        if (phone && phone.length > 40) reasons.push('Nomor telepon maksimal 40 karakter.');
        if (groupName && groupName.length > 100) reasons.push('Kelompok maksimal 100 karakter.');
        if (!SIDES.has(side)) reasons.push('Pihak harus bride, groom, atau shared.');

        if (reasons.length) {
            invalid.push({ row: record.line, name: name || '(tanpa nama)', reason: reasons.join(' ') });
            continue;
        }

        eligible.push({
            name,
            email,
            phone,
            group_name: groupName,
            side,
            source_row: record.line,
        });
    }

    return { eligible, invalid };
}
