<script setup>
import { computed, onBeforeUnmount, onMounted, onUpdated, ref, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    modelModifiers: { type: Object, default: () => ({}) },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    name: { type: String, default: undefined },
    id: { type: String, default: undefined },
});
const emit = defineEmits(['update:modelValue', 'change']);
const attrs = useAttrs();
const root = ref(null);
const nativeSelect = ref(null);
const trigger = ref(null);
const options = ref([]);
const open = ref(false);
const openAbove = ref(false);
const activeIndex = ref(0);
const invalid = ref(false);
const optionListId = `menuju-select-${Math.random().toString(36).slice(2, 10)}`;
let optionsSignature = '';
let observer = null;
let form = null;
let typeahead = '';
let typeaheadTimer = null;

const currentValue = computed(() => String(props.modelValue ?? ''));
const selectedIndex = computed(() => options.value.findIndex((option) => option.value === currentValue.value));
const selectedOption = computed(() => options.value[selectedIndex.value] ?? options.value.find((option) => option.value === '') ?? null);
const activeOptionId = computed(() => open.value ? `${optionListId}-option-${activeIndex.value}` : undefined);

function refreshOptions() {
    const select = nativeSelect.value;
    if (!select) return;
    const next = [...select.options].map((option) => {
        const rawValue = option._value ?? option.value;
        return {
            value: String(rawValue ?? ''),
            rawValue,
            label: option.label || option.textContent?.trim() || String(rawValue ?? ''),
            disabled: option.disabled,
        };
    });
    const signature = JSON.stringify(next.map(({ value, label, disabled }) => [value, label, disabled]));
    if (signature === optionsSignature) return;
    optionsSignature = signature;
    options.value = next;
    if (activeIndex.value >= next.length) activeIndex.value = Math.max(0, next.length - 1);
}

function close() { open.value = false; }

function toggle() {
    if (props.disabled) return;
    if (open.value) return close();
    refreshOptions();
    const bounds = trigger.value?.getBoundingClientRect();
    openAbove.value = Boolean(bounds && window.innerHeight - bounds.bottom < 270 && bounds.top > 270);
    activeIndex.value = selectedIndex.value >= 0 ? selectedIndex.value : Math.max(0, options.value.findIndex((option) => !option.disabled));
    open.value = true;
}

function moveActive(direction) {
    if (!options.value.length) return;
    let next = activeIndex.value;
    for (let i = 0; i < options.value.length; i += 1) {
        next = (next + direction + options.value.length) % options.value.length;
        if (!options.value[next].disabled) { activeIndex.value = next; return; }
    }
}

function choose(index) {
    const option = options.value[index];
    if (!option || option.disabled) return;
    const value = props.modelModifiers.number && option.value !== '' ? Number(option.value) : option.rawValue;
    invalid.value = false;
    emit('update:modelValue', value);
    emit('change', { target: { value } });
    close();
    trigger.value?.focus({ preventScroll: true });
}

function onKeydown(event) {
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        if (!open.value) toggle();
        else moveActive(event.key === 'ArrowDown' ? 1 : -1);
        return;
    }
    if (event.key === 'Home' && open.value) { event.preventDefault(); activeIndex.value = 0; return; }
    if (event.key === 'End' && open.value) { event.preventDefault(); activeIndex.value = Math.max(0, options.value.length - 1); return; }
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        if (open.value) choose(activeIndex.value);
        else toggle();
        return;
    }
    if (event.key === 'Escape' && open.value) { event.preventDefault(); close(); return; }
    if (event.key === 'Tab') close();
    if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
        typeahead += event.key.toLocaleLowerCase();
        window.clearTimeout(typeaheadTimer);
        typeaheadTimer = window.setTimeout(() => { typeahead = ''; }, 650);
        const match = options.value.findIndex((option) => !option.disabled && option.label.toLocaleLowerCase().startsWith(typeahead));
        if (match >= 0) { activeIndex.value = match; if (!open.value) choose(match); }
    }
}

function onDocumentPointer(event) {
    if (open.value && root.value && !root.value.contains(event.target)) close();
}

function onFormSubmit(event) {
    if (!props.required || currentValue.value !== '') { invalid.value = false; return; }
    event.preventDefault();
    event.stopImmediatePropagation();
    invalid.value = true;
    trigger.value?.focus({ preventScroll: true });
}

onMounted(() => {
    refreshOptions();
    document.addEventListener('pointerdown', onDocumentPointer);
    form = root.value?.closest('form');
    form?.addEventListener('submit', onFormSubmit, true);
    if (nativeSelect.value && 'MutationObserver' in window) {
        observer = new MutationObserver(refreshOptions);
        observer.observe(nativeSelect.value, { childList: true, subtree: true, attributes: true, attributeFilter: ['value', 'label', 'disabled'] });
    }
});

onUpdated(refreshOptions);
onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onDocumentPointer);
    form?.removeEventListener('submit', onFormSubmit, true);
    observer?.disconnect();
    window.clearTimeout(typeaheadTimer);
});
</script>

<template>
    <div ref="root" class="motion-select" :class="[{ 'motion-select--open': open, 'motion-select--invalid': invalid }, attrs.class]">
        <select
            ref="nativeSelect"
            class="motion-select__native"
            :value="currentValue"
            :name="name"
            :id="id"
            :disabled="disabled"
            aria-hidden="true"
            tabindex="-1"
            @change="$emit('update:modelValue', $event.target.value); $emit('change', $event)"
        ><slot /></select>
        <button
            :id="id ? `${id}-trigger` : undefined"
            ref="trigger"
            v-bind="attrs"
            type="button"
            class="motion-select__trigger"
            role="combobox"
            aria-haspopup="listbox"
            :aria-expanded="open"
            :aria-controls="optionListId"
            :aria-activedescendant="activeOptionId"
            :aria-required="required || undefined"
            :aria-invalid="invalid || attrs['aria-invalid'] === 'true' ? 'true' : undefined"
            :disabled="disabled"
            @click="toggle"
            @keydown="onKeydown"
        >
            <span class="motion-select__value">{{ selectedOption?.label || 'Pilih salah satu' }}</span>
            <FaIcon :icon="['fas', 'chevron-down']" class="motion-select__chevron" aria-hidden="true" />
        </button>
        <Transition name="select-menu">
            <div v-if="open" :id="optionListId" class="motion-select__menu" :class="{ 'motion-select__menu--above': openAbove }" role="listbox" :aria-label="attrs['aria-label'] || selectedOption?.label || 'Pilihan'">
                <div
                    v-for="(option, index) in options"
                    :id="`${optionListId}-option-${index}`"
                    :key="`${index}-${option.value}`"
                    class="motion-select__option"
                    :class="{ 'motion-select__option--active': index === activeIndex, 'motion-select__option--selected': index === selectedIndex, 'motion-select__option--disabled': option.disabled }"
                    role="option"
                    :aria-selected="index === selectedIndex"
                    :aria-disabled="option.disabled || undefined"
                    @pointerenter="!option.disabled && (activeIndex = index)"
                    @mousedown.prevent
                    @click="choose(index)"
                >{{ option.label }}</div>
            </div>
        </Transition>
        <small v-if="invalid" class="motion-select__error" role="alert">Pilih salah satu opsi sebelum melanjutkan.</small>
    </div>
</template>
