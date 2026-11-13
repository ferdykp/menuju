<template>
    <DialogAccessibilityManager />
    <ToastViewport />
    <RouterView v-slot="{ Component, route }">
        <Transition name="route" appear>
            <component :is="Component" :key="route.name === 'app' ? 'app-shell' : route.fullPath" />
        </Transition>
    </RouterView>
</template>

<script setup>
import DialogAccessibilityManager from './components/DialogAccessibilityManager.vue';
import ToastViewport from './components/ToastViewport.vue';
</script>
