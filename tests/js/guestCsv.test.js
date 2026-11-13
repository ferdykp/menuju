import test from 'node:test';
import assert from 'node:assert/strict';
import { parseCsvRecords, parseGuestCsv } from '../../resources/js/utils/guestCsv.js';

test('CSV parser handles quoted commas, escaped quotes, CRLF and multiline fields', () => {
    const records = parseCsvRecords('name,email,group_name\r\n"Ayu, Bima",ayu@example.test,"Keluarga ""besar"""\r\n"Nadia\nPutri",nadia@example.test,Teman');

    assert.deepEqual(records, [
        { fields: ['name', 'email', 'group_name'], line: 1 },
        { fields: ['Ayu, Bima', 'ayu@example.test', 'Keluarga "besar"'], line: 2 },
        { fields: ['Nadia\nPutri', 'nadia@example.test', 'Teman'], line: 3 },
    ]);
});

test('guest CSV normalizes Indonesian headers and reports invalid source rows', () => {
    const result = parseGuestCsv('\uFEFFNama,Email,Telepon,Kelompok,Pihak\n"Rina, Putri",RINA@example.test,08123,Keluarga,bride\n,tidak-valid,,Teman,shared\nBima,bima@example.test,,Keluarga,shared');

    assert.deepEqual(result.eligible[0], {
        name: 'Rina, Putri', email: 'rina@example.test', phone: '08123', group_name: 'Keluarga', side: 'bride', source_row: 2,
    });
    assert.equal(result.invalid.length, 1);
    assert.equal(result.invalid[0].row, 3);
    assert.match(result.invalid[0].reason, /Nama wajib/);
    assert.equal(result.eligible[1].source_row, 4);
});

test('guest CSV rejects unterminated quotes and more than 500 data rows', () => {
    assert.throws(() => parseGuestCsv('name,email\n"Ayu,ayu@example.test'), /belum ditutup/);
    const manyRows = ['name', ...Array.from({ length: 501 }, (_, index) => `Tamu ${index + 1}`)].join('\n');
    assert.throws(() => parseGuestCsv(manyRows), /maksimal 500/);
});
