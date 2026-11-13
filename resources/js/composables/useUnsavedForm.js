import { onBeforeUnmount } from 'vue';
import { registerUnsavedGuard } from '../unsavedChanges';
import { createUnsavedFormGuard } from '../utils/unsavedFormGuard';

export function useUnsavedForm({ isOpen, isDirty, confirm, onDiscard, title = 'Perubahan belum disimpan', message = 'Jika ditutup sekarang, perubahan ini akan hilang.' }) {
    const guard = createUnsavedFormGuard({
        isOpen: () => isOpen.value,
        isDirty: () => isDirty.value,
        confirm,
        onDiscard,
        title,
        message,
    });
    const unregister = registerUnsavedGuard(guard.confirmRouteLeave);
    onBeforeUnmount(unregister);

    return { requestClose: guard.requestClose };
}
