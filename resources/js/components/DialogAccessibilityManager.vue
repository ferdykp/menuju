<script setup>
import { onBeforeUnmount, onMounted } from 'vue';

let observer;
let activeDialog = null;
let returnFocus = null;
let bodyOverflow = '';
let frame = 0;
let lastTrigger = null;
const focusBeforeDialog = new WeakMap();

function onPointerDown(event) {
    const trigger = event.target.closest?.('button, a[href], input:not([type="hidden"]), select, textarea, [role="button"]');
    if (trigger && !trigger.closest('[role="dialog"], [role="alertdialog"]')) lastTrigger = trigger;
}

function visibleDialogs() {
    return [...document.querySelectorAll('[role="dialog"][aria-modal="true"], [role="alertdialog"][aria-modal="true"]')]
        .filter((element) => {
            const style = window.getComputedStyle(element);
            const rect = element.getBoundingClientRect();
            return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
        });
}

function focusables(dialog) {
    return [...dialog.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')]
        .filter((element) => !element.closest('[aria-hidden="true"]') && element.getClientRects().length > 0);
}

function syncDialog() {
    const nextDialog = visibleDialogs().at(-1) ?? null;
    if (nextDialog === activeDialog) return;

    if (!activeDialog && nextDialog) {
        returnFocus = document.activeElement === document.body ? lastTrigger : document.activeElement;
        bodyOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
    }

    const previousDialog = activeDialog;
    activeDialog = nextDialog;

    if (nextDialog) {
        if (previousDialog) focusBeforeDialog.set(nextDialog, document.activeElement);
        requestAnimationFrame(() => {
            if (activeDialog !== nextDialog) return;
            const target = focusables(nextDialog)[0] ?? nextDialog;
            target.focus?.({ preventScroll: true });
        });
    } else {
        document.body.style.overflow = bodyOverflow;
        const target = previousDialog ? focusBeforeDialog.get(previousDialog) : returnFocus;
        if (target?.isConnected) requestAnimationFrame(() => target.focus?.({ preventScroll: true }));
        else if (lastTrigger?.isConnected) requestAnimationFrame(() => lastTrigger.focus?.({ preventScroll: true }));
        returnFocus = null;
    }
}

function onKeydown(event) {
    const dialog = activeDialog;
    if (!dialog || dialog.classList.contains('confirm-card')) return;

    if (event.key === 'Escape') {
        const cancel = [...dialog.querySelectorAll('button:not([disabled])')].find((button) =>
            /tutup|batal|kembali|lanjut mengedit/i.test(`${button.getAttribute('aria-label') ?? ''} ${button.innerText ?? ''}`),
        );
        if (cancel) {
            event.preventDefault();
            event.stopPropagation();
            cancel.click();
        }
        return;
    }

    if (event.key !== 'Tab') return;
    const items = focusables(dialog);
    if (!items.length) {
        event.preventDefault();
        dialog.focus?.();
        return;
    }
    const first = items[0];
    const last = items.at(-1);
    if (!dialog.contains(document.activeElement)) {
        event.preventDefault();
        (event.shiftKey ? last : first).focus();
    } else if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

onMounted(() => {
    observer = new MutationObserver(() => {
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(syncDialog);
    });
    observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['aria-modal', 'style', 'class'] });
    document.addEventListener('keydown', onKeydown, true);
    document.addEventListener('pointerdown', onPointerDown, true);
    syncDialog();
});

onBeforeUnmount(() => {
    observer?.disconnect();
    cancelAnimationFrame(frame);
    document.removeEventListener('keydown', onKeydown, true);
    document.removeEventListener('pointerdown', onPointerDown, true);
    document.body.style.overflow = bodyOverflow;
});
</script>

<template><span hidden aria-hidden="true"></span></template>
