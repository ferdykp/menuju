import test from 'node:test';
import assert from 'node:assert/strict';
import { formatRupiah } from '../../resources/js/utils/money.js';
import { formatDateOnly } from '../../resources/js/utils/dateOnly.js';
import { formatMoneyModel, parseMoneyDraft } from '../../resources/js/utils/moneyInput.js';

test('MoneyInput parses Indonesian grouping and decimal separators', () => {
    assert.deepEqual(parseMoneyDraft('Rp 1.234.567'), { value: '1234567', display: '1.234.567' });
    assert.deepEqual(parseMoneyDraft('1.234,50'), { value: '1234.50', display: '1.234,50' });
    assert.deepEqual(parseMoneyDraft(''), { value: '', display: '' });
});

test('money formatting uses Indonesian digits and whole-Rupiah currency rounding', () => {
    assert.equal(formatMoneyModel(1234567.5), '1.234.567,5');
    assert.match(formatRupiah(1234567.5), /1\.234\.568/);
});

test('date-only labels stay on the requested calendar day across local time zones', () => {
    const originalTimezone = process.env.TZ;
    try {
        process.env.TZ = 'America/Los_Angeles';
        const west = formatDateOnly('2026-11-13');
        process.env.TZ = 'Asia/Jakarta';
        const east = formatDateOnly('2026-11-13');
        assert.equal(west, east);
        assert.match(west, /13/);
        assert.equal(formatDateOnly(null), 'Tanpa tanggal');
    } finally {
        if (originalTimezone === undefined) delete process.env.TZ;
        else process.env.TZ = originalTimezone;
    }
});
