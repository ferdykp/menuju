<script setup>
import { useToast } from '../composables/toastBus';

const { toasts, remove } = useToast();
</script>

<template>
    <Teleport to="body">
        <TransitionGroup class="toast-viewport" name="toast" tag="div" aria-live="polite" aria-relevant="additions text">
            <article v-for="toast in toasts" :key="toast.id" class="toast-card" role="status" :style="{ '--toast-duration': `${toast.duration}ms` }">
                <span class="toast-card__icon" aria-hidden="true"><FaIcon :icon="['fas', 'circle-check']" /></span>
                <p class="toast-card__message">{{ toast.message }}</p>
                <button class="toast-card__close" type="button" aria-label="Tutup notifikasi" @click="remove(toast.id)">
                    <FaIcon :icon="['fas', 'xmark']" aria-hidden="true" />
                </button>
                <span class="toast-card__timer" aria-hidden="true"></span>
            </article>
        </TransitionGroup>
    </Teleport>
</template>
