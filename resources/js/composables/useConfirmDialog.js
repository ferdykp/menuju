import { ref } from 'vue';

const defaultOptions = () => ({
    title: 'Konfirmasi tindakan',
    message: 'Lanjutkan tindakan ini?',
    confirmLabel: 'Lanjutkan',
    cancelLabel: 'Batal',
    destructive: true,
});

export function useConfirmDialog() {
    const open = ref(false);
    const options = ref(defaultOptions());
    let resolveRequest = null;

    function confirm(config = {}) {
        const defaults = defaultOptions();
        options.value = {
            title: config.title ?? defaults.title,
            message: config.message ?? defaults.message,
            confirmLabel: config.confirmLabel ?? defaults.confirmLabel,
            cancelLabel: config.cancelLabel ?? defaults.cancelLabel,
            destructive: config.destructive ?? defaults.destructive,
        };
        open.value = true;
        return new Promise((resolve) => { resolveRequest = resolve; });
    }

    function finish(result) {
        open.value = false;
        options.value = defaultOptions();
        resolveRequest?.(result);
        resolveRequest = null;
    }

    return { open, options, confirm, finish };
}
