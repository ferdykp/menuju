<script setup>
import { computed } from 'vue';

const props = defineProps({
    pagination: { type: Object, required: true },
});

const emit = defineEmits(['change']);
const currentPage = computed(() => Number(props.pagination.current_page || 1));
const lastPage = computed(() => Number(props.pagination.last_page || 1));
const perPage = computed(() => Number(props.pagination.per_page || 0));
const total = computed(() => Number(props.pagination.total || 0));
const from = computed(() => Number(props.pagination.from ?? (total.value ? ((currentPage.value - 1) * perPage.value) + 1 : 0)));
const to = computed(() => Number(props.pagination.to ?? Math.min(currentPage.value * perPage.value, total.value)));
</script>

<template>
    <nav v-if="lastPage > 1" class="pagination-controls" aria-label="Navigasi halaman daftar">
        <p aria-live="polite">Menampilkan {{ from }}–{{ to }} dari {{ total }} data</p>
        <div>
            <button type="button" :disabled="currentPage <= 1" @click="emit('change', currentPage - 1)">Sebelumnya</button>
            <span>Halaman {{ currentPage }} dari {{ lastPage }}</span>
            <button type="button" :disabled="currentPage >= lastPage" @click="emit('change', currentPage + 1)">Berikutnya</button>
        </div>
    </nav>
</template>

<style scoped>
.pagination-controls{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 17px;border-top:1px solid #e9eae3;color:#5f675c;font:13px/1.4 var(--font-body)}
.pagination-controls p{margin:0}
.pagination-controls>div{display:flex;align-items:center;gap:12px}
.pagination-controls button{min-height:36px;padding:0 12px;border:1px solid #d9ddd4;border-radius:8px;background:#fff;color:#40583a;font:600 13px var(--font-body);cursor:pointer}
.pagination-controls button:disabled{color:#969b93;background:#f6f6f2;cursor:not-allowed}
.pagination-controls button:focus-visible{outline:3px solid #3e5b3d55;outline-offset:2px}
@media(max-width:600px){.pagination-controls{align-items:flex-start;flex-direction:column}.pagination-controls>div{width:100%;justify-content:space-between;gap:8px}.pagination-controls button{padding:0 9px}}
</style>
