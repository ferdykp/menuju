const guards = new Set();

export function registerUnsavedGuard(guard) {
    guards.add(guard);
    return () => guards.delete(guard);
}

export async function confirmUnsavedChanges() {
    for (const guard of [...guards]) {
        if (!await guard()) return false;
    }
    return true;
}
