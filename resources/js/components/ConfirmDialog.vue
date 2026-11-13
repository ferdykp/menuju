<script setup>
import { nextTick, ref, watch, onBeforeUnmount } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Konfirmasi tindakan' },
    message: { type: String, default: 'Lanjutkan tindakan ini?' },
    confirmLabel: { type: String, default: 'Lanjutkan' },
    cancelLabel: { type: String, default: 'Batal' },
    destructive: { type: Boolean, default: true },
});
const emit = defineEmits(['confirm', 'cancel']);
const cancelButton = ref(null);
const dialog = ref(null);
let previousFocus = null;

watch(() => props.open, async (open) => {
    if (open) {
        previousFocus = document.activeElement;
        await nextTick();
        cancelButton.value?.focus();
    } else {
        previousFocus?.focus?.();
    }
});

function onKeydown(event) {
    if (event.key === 'Escape') {
        event.preventDefault();
        emit('cancel');
        return;
    }
    if (event.key !== 'Tab') return;
    const items = [...dialog.value.querySelectorAll('button:not([disabled])')];
    if (!items.length) return;
    const first = items[0]; const last = items.at(-1);
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
}

onBeforeUnmount(() => { previousFocus = null; });
</script>

<template>
    <Teleport to="body">
        <Transition name="confirm" appear>
            <div v-if="open" class="confirm-layer" @click.self="$emit('cancel')">
                <section ref="dialog" class="confirm-card" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-message" tabindex="-1" @keydown="onKeydown">
                    <p class="confirm-card__eyebrow">KONFIRMASI</p>
                    <h2 id="confirm-title">{{ title }}</h2>
                    <p id="confirm-message">{{ message }}</p>
                    <footer>
                        <button ref="cancelButton" class="button button--quiet" type="button" @click="$emit('cancel')">{{ cancelLabel }}</button>
                        <button class="button" :class="destructive ? 'button--destructive' : 'button--primary'" type="button" @click="$emit('confirm')">{{ confirmLabel }}</button>
                    </footer>
                </section>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.confirm-layer{position:fixed;inset:0;z-index:200;display:grid;place-items:center;padding:20px;background:rgb(29 34 28 / 48%);backdrop-filter:blur(2px)}.confirm-card{width:min(100%,440px);border:1px solid #e8e8e0;border-radius:16px;background:#fff;padding:25px;box-shadow:0 22px 70px #1b211b38;outline:none}.confirm-card__eyebrow{margin:0 0 9px;font-size:9px;font-weight:700;letter-spacing:1.7px;color:#74816d}.confirm-card h2{margin:0 0 9px;font:500 29px/1.1 var(--font-display);color:#30362e}.confirm-card>p:last-of-type{margin:0;color:#70766c;font-size:13px;line-height:1.65}.confirm-card footer{display:flex;justify-content:flex-end;gap:9px;margin-top:24px}.confirm-card :deep(.button){min-height:42px}@media(max-width:520px){.confirm-layer{align-items:end;padding:12px}.confirm-card{border-radius:16px 16px 10px 10px;padding:22px}.confirm-card footer{flex-direction:column-reverse}.confirm-card :deep(.button){width:100%}}
</style>
