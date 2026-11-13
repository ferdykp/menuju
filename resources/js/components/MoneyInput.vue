<script setup>
import { nextTick, ref, useAttrs, watch } from 'vue';
import { formatMoneyModel, parseMoneyDraft } from '../utils/moneyInput';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    min: { type: [String, Number], default: null },
    max: { type: [String, Number], default: null },
    required: { type: Boolean, default: false },
    describedby: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue', 'blur', 'focus']);
const attrs = useAttrs();
const input = ref(null);
const focused = ref(false);
const display = ref(formatMoneyModel(props.modelValue));

function updateValidity(value) {
    const field = input.value;
    if (!field) return;
    const number = value === '' ? NaN : Number(value);
    let message = '';
    if (props.required && value === '') message = 'Nominal wajib diisi.';
    else if (value !== '' && Number.isNaN(number)) message = 'Masukkan nominal yang valid.';
    else if (value !== '' && props.min !== null && number < Number(props.min)) message = `Nominal minimal ${formatMoneyModel(props.min)}.`;
    else if (value !== '' && props.max !== null && number > Number(props.max)) message = `Nominal maksimal ${formatMoneyModel(props.max)}.`;
    field.setCustomValidity(message);
}

function onInput(event) {
    const field = event.target;
    const caret = field.selectionStart ?? field.value.length;
    const digitsBeforeCaret = field.value.slice(0, caret).replace(/\D/g, '').length;
    const parsed = parseMoneyDraft(field.value);
    display.value = parsed.display;
    emit('update:modelValue', parsed.value);
    updateValidity(parsed.value);
    nextTick(() => {
        const current = input.value;
        if (!current) return;
        let position = 0;
        let digits = 0;
        while (position < current.value.length && digits < digitsBeforeCaret) {
            if (/\d/.test(current.value[position])) digits += 1;
            position += 1;
        }
        current.setSelectionRange(position, position);
    });
}

function onFocus(event) {
    focused.value = true;
    emit('focus', event);
}

function onBlur(event) {
    focused.value = false;
    display.value = formatMoneyModel(props.modelValue);
    updateValidity(String(props.modelValue ?? ''));
    emit('blur', event);
}

watch(() => props.modelValue, (value) => {
    if (!focused.value) display.value = formatMoneyModel(value);
    updateValidity(String(value ?? ''));
}, { immediate: true });
</script>

<template>
    <span class="money-field">
        <span class="money-field__prefix" aria-hidden="true">Rp</span>
        <input
            v-bind="attrs"
            ref="input"
            type="text"
            inputmode="decimal"
            autocomplete="off"
            :value="display"
            :required="required"
            :min="min ?? undefined"
            :max="max ?? undefined"
            :aria-describedby="describedby || attrs['aria-describedby'] || undefined"
            @input="onInput"
            @focus="onFocus"
            @blur="onBlur"
        />
    </span>
</template>

<style scoped>
.money-field{display:flex;align-items:center;width:100%;min-width:0;border:1px solid #bdc3b8;border-radius:8px;background:#fff;overflow:hidden}.money-field:focus-within{border-color:#3e5b3d;box-shadow:0 0 0 3px #3e5b3d29}.money-field__prefix{align-self:stretch;display:flex;align-items:center;padding:0 0 0 11px;color:#72796f;font-size:12px}.money-field input{min-width:0;width:100%;border:0!important;outline:0!important;box-shadow:none!important;background:transparent!important;padding:11px 10px!important;font:13px var(--font-body)!important;font-variant-numeric:tabular-nums}.money-field input:focus{outline:0!important;box-shadow:none!important}
.money-field__prefix { font-size: 14px; }
.money-field input { font-size: 16px !important; }
</style>
