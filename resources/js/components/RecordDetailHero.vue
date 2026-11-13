<script setup>
defineProps({
    id: { type: String, required: true },
    title: { type: String, required: true },
    eyebrow: { type: String, default: 'DETAIL' },
    subtitle: { type: String, default: '' },
    headingTag: { type: String, default: 'h1' },
});
</script>

<template>
    <section class="record-detail-hero" :aria-labelledby="id">
        <div class="record-detail-hero__copy">
            <p v-if="eyebrow" class="record-detail-hero__eyebrow"><span></span>{{ eyebrow }}</p>
            <component :is="headingTag" :id="id" class="record-detail-hero__title">{{ title }}</component>
            <p v-if="subtitle" class="record-detail-hero__subtitle">{{ subtitle }}</p>
            <div v-if="$slots.meta" class="record-detail-hero__meta"><slot name="meta" /></div>
        </div>
        <div v-if="$slots.actions" class="record-detail-hero__actions"><slot name="actions" /></div>
    </section>
</template>

<style scoped>
.record-detail-hero{position:relative;isolation:isolate;display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:24px;overflow:hidden;margin-bottom:19px;padding:clamp(22px,3vw,32px);border:1px solid var(--champagne);border-radius:19px;background:linear-gradient(115deg,var(--surface) 0%,var(--surface) 66%,var(--canvas) 100%);box-shadow:0 12px 30px rgb(38 48 35 / 6%)}
.record-detail-hero::before{position:absolute;z-index:-1;inset:0 auto 0 0;width:5px;background:linear-gradient(180deg,var(--accent),var(--dusty-rose));content:""}
.record-detail-hero__copy{min-width:0}.record-detail-hero__eyebrow{display:flex;align-items:center;gap:9px;margin:0 0 11px;color:var(--accent);font-size:10px;font-weight:800;letter-spacing:1.5px;line-height:1.4;text-transform:uppercase}.record-detail-hero__eyebrow span{flex:0 0 7px;width:7px;height:7px;border-radius:50%;background:var(--accent);box-shadow:0 0 0 4px color-mix(in srgb,var(--dusty-rose) 18%,var(--surface))}
.record-detail-hero__title{margin:0;color:var(--text-primary);font:600 clamp(34px,4.2vw,48px)/1.04 var(--font-display);letter-spacing:-.35px;text-wrap:balance;overflow-wrap:anywhere}.record-detail-hero__subtitle{margin:9px 0 0;color:var(--text-secondary);font-size:14px;font-weight:500;line-height:1.55;overflow-wrap:anywhere}
.record-detail-hero__meta{display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin-top:15px}.record-detail-hero__actions{display:flex;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:9px;max-width:360px}.record-detail-hero__actions :deep(.button){min-height:44px;white-space:nowrap}
.record-detail-hero__meta :deep(.record-detail-chip){display:inline-flex;align-items:center;gap:6px;min-height:29px;padding:6px 10px;border:1px solid color-mix(in srgb, var(--accent) 8%, var(--surface));border-radius:999px;background:var(--surface-soft);color:var(--accent);font-size:11px;font-weight:700;line-height:1.25}.record-detail-hero__meta :deep(.record-detail-chip--muted){border-color:#e3e5de;background:#f3f4ef;color:var(--text-secondary)}.record-detail-hero__meta :deep(.record-detail-chip--warning){border-color:#ecd8c6;background:#fbf0e8;color:#86513b}.record-detail-hero__meta :deep(.record-detail-chip--danger){border-color:#ecd1c9;background:#f9ebe7;color:#874437}
@media(max-width:760px){.record-detail-hero{grid-template-columns:minmax(0,1fr);align-items:stretch;gap:18px;margin-bottom:15px;padding:21px 18px 19px}.record-detail-hero__title{font-size:clamp(32px,9vw,40px)}.record-detail-hero__subtitle{font-size:13px}.record-detail-hero__actions{justify-content:flex-start;max-width:none}.record-detail-hero__actions :deep(.button){flex:1 1 auto}}
</style>
