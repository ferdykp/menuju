export function createUnsavedFormGuard({
    isOpen,
    isDirty,
    confirm,
    onDiscard,
    title = 'Perubahan belum disimpan',
    message = 'Jika ditutup sekarang, perubahan ini akan hilang.',
}) {
    const needsConfirmation = () => isOpen() && isDirty();
    const askToDiscard = () => confirm({ title, message, confirmLabel: 'Buang perubahan', destructive: true });

    async function confirmRouteLeave() {
        if (!needsConfirmation()) return true;
        const discard = await askToDiscard();
        if (discard) onDiscard();
        return discard;
    }

    async function requestClose() {
        if (needsConfirmation() && !await askToDiscard()) return false;
        onDiscard();
        return true;
    }

    return { confirmRouteLeave, requestClose };
}
