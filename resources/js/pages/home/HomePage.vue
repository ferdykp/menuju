<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps({ workspaceId: { type: [String, Number], required: true }, workspace: { type: Object, required: true } });
const loading = ref(true);
const error = ref('');
const data = ref(null);

const coupleNames = computed(() => [props.workspace.partner_one_name, props.workspace.partner_two_name].filter(Boolean).join(' & '));
const daysLabel = computed(() => {
    const days = data.value?.workspace.days_until_wedding;
    if (days === null || days === undefined) return 'Tanggal pernikahan belum ditentukan';
    if (days < 0) return 'Tanggal pernikahan telah lewat';
    if (days === 0) return 'Hari istimewa kalian tiba hari ini';
    return `Hari pernikahan tinggal ${days} hari lagi`;
});

async function load() {
    loading.value = true;
    error.value = '';
    try {
        const response = await axios.get(`/api/workspaces/${props.workspaceId}/home`);
        data.value = response.data;
    } catch {
        error.value = 'Beranda belum berhasil dimuat. Coba lagi.';
    } finally {
        loading.value = false;
    }
}

function formatDate(value) {
    if (!value) return 'Belum ada tenggat';
    return new Date(`${value}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'long' });
}

onMounted(load);
</script>

<template>
    <section class="home-page" aria-labelledby="home-title">
        <div v-if="loading" class="home-skeleton" aria-label="Memuat beranda">
            <div class="skeleton-line skeleton-line--short"></div><div class="skeleton-line skeleton-line--title"></div><div class="skeleton-line"></div>
            <div class="skeleton-row"><div class="skeleton-block"></div><div class="skeleton-block"></div></div>
        </div>
        <div v-else-if="error" class="page-error" role="alert">
            <p>{{ error }}</p><button class="button button--quiet" type="button" @click="load"><FaIcon :icon="['fas', 'rotate']" aria-hidden="true" /> Coba lagi</button>
        </div>
        <template v-else>
            <section class="home-hero">
                <div class="home-hero__copy">
                    <div class="eyebrow"><span class="eyebrow__dot"></span> AGENDA HARIAN PASANGAN</div>
                    <h1 id="home-title">Selamat datang, {{ coupleNames || data.workspace.name }}.</h1>
                    <p>{{ daysLabel }}<span v-if="data.tasks.due_this_week.length">. Ada {{ data.tasks.due_this_week.length }} tugas yang menanti perhatian kalian minggu ini.</span><span v-else>.</span></p>
                </div>
                <div class="home-hero__actions">
                    <RouterLink class="button button--primary" to="/app/tasks"><FaIcon :icon="['fas', 'circle-check']" aria-hidden="true" /> Lihat Checklist</RouterLink>
                    <RouterLink class="button button--quiet" to="/app/tasks?create=1"><FaIcon :icon="['fas', 'plus']" aria-hidden="true" /> Tambah tugas</RouterLink>
                </div>
            </section>

            <div v-if="data.tasks.total === 0" class="home-empty">
                <div class="home-empty__mark"><FaIcon :icon="['fas', 'leaf']" aria-hidden="true" /></div>
                <div>
                    <p class="eyebrow">SATU LANGKAH KECIL</p>
                    <h2>Belum ada tugas. Mulai dari yang paling dekat.</h2>
                    <p>Tambahkan satu hal yang perlu kalian siapkan. Kalian bisa membagi tugas dan tenggatnya nanti.</p>
                    <RouterLink class="text-link" to="/app/tasks?create=1">Buat tugas pertama <FaIcon :icon="['fas', 'arrow-right']" aria-hidden="true" /></RouterLink>
                </div>
            </div>

            <template v-else>
                <section class="home-metrics" aria-label="Progres persiapan">
                    <article class="home-metric">
                        <span class="home-metric__label">PROGRES CHECKLIST</span>
                        <h2>Kesiapan rencana</h2>
                        <div class="home-metric__value"><strong>{{ data.tasks.completed }} <small>dari {{ data.tasks.total }} selesai</small></strong><b>{{ data.tasks.completion_percent }}%</b></div>
                        <div class="progress-track"><span :style="{ width: `${data.tasks.completion_percent}%` }"></span></div>
                        <p>{{ data.tasks.open }} tugas masih perlu perhatian</p>
                    </article>
                    <article class="home-next">
                        <div class="eyebrow"><span class="eyebrow__dot"></span> LANGKAH PENTING BERIKUTNYA</div>
                        <div class="home-next__heading"><h2>Minggu ini</h2><RouterLink class="text-link" to="/app/tasks">Lihat semua <FaIcon :icon="['fas', 'arrow-right']" aria-hidden="true" /></RouterLink></div>
                        <div v-if="data.tasks.due_this_week.length" class="home-task-list">
                            <RouterLink v-for="task in data.tasks.due_this_week" :key="task.id" class="home-task" to="/app/tasks">
                                <span class="home-task__marker"></span>
                                <span class="home-task__copy"><strong>{{ task.title }}</strong><small>{{ task.category }} · {{ task.assignee?.name ?? 'Belum ditugaskan' }}</small></span>
                                <span class="home-task__date">{{ formatDate(task.due_at) }}</span>
                            </RouterLink>
                        </div>
                        <div v-else class="home-task-empty">Belum ada tugas dengan tenggat minggu ini. Kalian bisa menambahkan satu dari checklist.</div>
                    </article>
                </section>
                <section v-if="data.tasks.open === 0" class="home-congrats"><FaIcon :icon="['fas', 'circle-check']" aria-hidden="true" /><span>Semua tugas selesai. Luangkan waktu untuk menikmati progres kalian.</span></section>
            </template>
        </template>
    </section>
</template>
