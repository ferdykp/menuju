<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { formatRupiah } from '../utils/money';

const props = defineProps({
    value: { type: [Number, String], default: 0 },
    currency: { type: Boolean, default: false },
    suffix: { type: String, default: '' },
    duration: { type: Number, default: 480 },
});

const target = computed(() => Number(props.value) || 0);
const displayed = ref(0);
let frame = 0;
let startTime = 0;
let startValue = 0;
let reducedMotion = false;

const numberFormatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
const formatted = computed(() => `${props.currency ? formatRupiah(Math.round(displayed.value)) : numberFormatter.format(Math.round(displayed.value))}${props.suffix}`);

function animateTo(value, from = displayed.value) {
    window.cancelAnimationFrame(frame);
    if (reducedMotion || props.duration <= 0 || from === value) {
        displayed.value = value;
        return;
    }
    startValue = from;
    startTime = 0;
    const tick = (time) => {
        if (!startTime) startTime = time;
        const progress = Math.min(1, (time - startTime) / props.duration);
        const eased = 1 - (1 - progress) ** 3;
        displayed.value = startValue + (value - startValue) * eased;
        if (progress < 1) frame = window.requestAnimationFrame(tick);
        else displayed.value = value;
    };
    frame = window.requestAnimationFrame(tick);
}

onMounted(() => {
    reducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
    animateTo(target.value, 0);
});

watch(target, (value) => animateTo(value));
onBeforeUnmount(() => window.cancelAnimationFrame(frame));
</script>

<template><span class="animated-number" aria-live="off">{{ formatted }}</span></template>
