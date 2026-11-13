import test from 'node:test';
import assert from 'node:assert/strict';
import { createUnsavedFormGuard } from '../../resources/js/utils/unsavedFormGuard.js';

test('route navigation stays blocked when the user keeps editing a dirty form', async () => {
    let discardCount = 0;
    const guard = createUnsavedFormGuard({
        isOpen: () => true,
        isDirty: () => true,
        confirm: async () => false,
        onDiscard: () => { discardCount += 1; },
    });

    assert.equal(await guard.confirmRouteLeave(), false);
    assert.equal(discardCount, 0);
});

test('confirmed route navigation discards the form exactly once', async () => {
    let discardCount = 0;
    const guard = createUnsavedFormGuard({
        isOpen: () => true,
        isDirty: () => true,
        confirm: async () => true,
        onDiscard: () => { discardCount += 1; },
    });

    assert.equal(await guard.confirmRouteLeave(), true);
    assert.equal(discardCount, 1);
});

test('closing a clean or closed form does not prompt', async () => {
    let confirmationCount = 0;
    let discardCount = 0;
    const guard = createUnsavedFormGuard({
        isOpen: () => false,
        isDirty: () => true,
        confirm: async () => { confirmationCount += 1; return true; },
        onDiscard: () => { discardCount += 1; },
    });

    assert.equal(await guard.requestClose(), true);
    assert.equal(confirmationCount, 0);
    assert.equal(discardCount, 1);
});

test('closing a dirty form respects cancel and confirms discard', async () => {
    let shouldDiscard = false;
    let discardCount = 0;
    const guard = createUnsavedFormGuard({
        isOpen: () => true,
        isDirty: () => true,
        confirm: async () => shouldDiscard,
        onDiscard: () => { discardCount += 1; },
    });

    assert.equal(await guard.requestClose(), false);
    assert.equal(discardCount, 0);
    shouldDiscard = true;
    assert.equal(await guard.requestClose(), true);
    assert.equal(discardCount, 1);
});
