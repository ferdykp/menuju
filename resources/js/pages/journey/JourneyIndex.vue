<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import PaginationControls from '../../components/PaginationControls.vue';
import RecordDetailHero from '../../components/RecordDetailHero.vue';
import { useConfirmDialog } from '../../composables/useConfirmDialog';
import { useUnsavedForm } from '../../composables/useUnsavedForm';

const props = defineProps({ workspaceId: { type: [String, Number], required: true }, weddingDate: { type: String, default: null } });
const { open: confirmOpen, options: confirmOptions, confirm: askConfirm, finish: finishConfirm } = useConfirmDialog();
const milestones = ref([]);
const pagination = ref(null);
const currentPage = ref(1);
const milestoneSummary = ref({ total: 0, completed: 0 });
const loading = ref(true);
const error = ref('');
const editorOpen = ref(false);
const selected = ref(null);
const formTarget = ref(null);
const saving = ref(false);
const form = ref(blankForm());
const formSnapshot = ref('');
const formDirty = computed(() => editorOpen.value && JSON.stringify(form.value) !== formSnapshot.value);
const formGuard = useUnsavedForm({ isOpen: editorOpen, isDirty: formDirty, confirm: askConfirm, onDiscard: () => { editorOpen.value = false; }, title: 'Perubahan milestone belum disimpan' });
const formError = ref('');

const completeCount = computed(() => milestoneSummary.value.completed);
const progress = computed(() => milestoneSummary.value.total ? Math.round((completeCount.value / milestoneSummary.value.total) * 100) : 0);

function blankForm() { return { title: '', description: '', category: 'Persiapan', target_date: '', status: 'upcoming' }; }
function asUtcDate(value) {
    if (!value) return null;
    const [year, month, day] = String(value).slice(0, 10).split('-').map(Number);
    return new Date(Date.UTC(year, month - 1, day));
}
function dateLabel(value) { return value ? asUtcDate(value).toLocaleDateString('id-ID', { timeZone: 'UTC', day: 'numeric', month: 'long', year: 'numeric' }) : 'Tanggal belum ditentukan'; }
function dateDistance(value) {
    if (!value) return '';
    const todayParts = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Jakarta', year: 'numeric', month: 'numeric', day: 'numeric' })
        .formatToParts(new Date()).reduce((result, part) => ({ ...result, [part.type]: Number(part.value) }), {});
    const todayUtc = Date.UTC(todayParts.year, todayParts.month - 1, todayParts.day);
    const difference = Math.ceil((asUtcDate(value) - todayUtc) / 86400000);
    return difference < 0 ? `${Math.abs(difference)} hari lalu` : difference === 0 ? 'Hari ini' : `${difference} hari lagi`;
}
function openCreate() { selected.value = null; formTarget.value = null; form.value = blankForm(); formSnapshot.value = JSON.stringify(form.value); formError.value = ''; editorOpen.value = true; }
function openEdit(item) { formTarget.value = item; form.value = { title: item.title, description: item.description ?? '', category: item.category ?? '', target_date: item.target_date ?? '', status: item.status }; formSnapshot.value = JSON.stringify(form.value); formError.value = ''; editorOpen.value = true; }

async function load(page = currentPage.value) {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await axios.get(`/api/workspaces/${props.workspaceId}/milestones`, { params: { page, per_page: 20 } });
        if (page > data.pagination.last_page && data.pagination.last_page > 0) return await load(data.pagination.last_page);
        milestones.value = data.milestones;
        pagination.value = data.pagination;
        currentPage.value = data.pagination.current_page;
        milestoneSummary.value = data.summary;
        if (selected.value) selected.value = milestones.value.find((item) => item.id === selected.value.id) ?? selected.value;
    } catch {
        error.value = 'Perjalanan belum berhasil dimuat. Coba lagi.';
    } finally {
        loading.value = false;
    }
}
async function save() {
    saving.value = true; formError.value = '';
    try {
        const payload = { ...form.value, target_date: form.value.target_date || null };
        if (formTarget.value?.id) await axios.patch(`/api/workspaces/${props.workspaceId}/milestones/${formTarget.value.id}`, payload);
        else await axios.post(`/api/workspaces/${props.workspaceId}/milestones`, payload);
        editorOpen.value = false;
        if (!formTarget.value?.id) currentPage.value = 1;
        await load();
    } catch (failure) { formError.value = failure.response?.data?.message ?? 'Perubahan belum tersimpan. Periksa isian lalu coba lagi.'; }
    finally { saving.value = false; }
}

async function setStatus(item, status) {
    try { await axios.patch(`/api/workspaces/${props.workspaceId}/milestones/${item.id}`, { status }); await load(); if (selected.value?.id === item.id) selected.value = milestones.value.find((row) => row.id === item.id); }
    catch { error.value = 'Status milestone belum berhasil diperbarui.'; }
}

async function remove(item) {
    if (!await askConfirm({ title: `Hapus milestone “${item.title}”?`, message: 'Milestone dihapus dari perjalanan. Tugas yang terhubung tetap tersimpan.', confirmLabel: 'Hapus milestone' })) return;
    try { await axios.delete(`/api/workspaces/${props.workspaceId}/milestones/${item.id}`); selected.value = null; await load(); }
    catch { error.value = 'Milestone belum berhasil dihapus. Coba lagi.'; }
}

onMounted(load);
</script>

<template>
    <section class="journey-page" aria-labelledby="journey-title">
        <div v-if="loading" class="home-skeleton" aria-label="Memuat perjalanan"><div class="skeleton-line skeleton-line--short"></div><div class="skeleton-line skeleton-line--title"></div><div class="skeleton-block"></div></div>
        <div v-else-if="error && milestones.length === 0" class="page-error" role="alert"><p>{{ error }}</p><button class="button button--quiet" @click="load"><FaIcon :icon="['fas', 'rotate']" /> Coba lagi</button></div>
        <template v-else>
            <header class="journey-header">
                <div><p class="eyebrow"><span class="eyebrow__dot"></span> PERJALANAN MENUJU HARI ISTIMEWA</p><h1 id="journey-title">Setiap langkah membawa kalian lebih dekat.</h1><p>Milestone membantu melihat gambaran besar, sementara tugas menjaga langkah hari ini tetap jelas.</p></div>
                <button class="button button--primary" type="button" @click="openCreate"><FaIcon :icon="['fas', 'plus']" /> Tambah milestone</button>
            </header>

            <section class="journey-summary" aria-label="Ringkasan perjalanan">
                <div><span>PROGRES PERJALANAN</span><strong><AnimatedNumber :value="progress" suffix="%" /></strong><small><AnimatedNumber :value="completeCount" /> dari {{ milestoneSummary.total }} milestone selesai</small></div>
                <div class="progress-track" role="progressbar" aria-label="Progres perjalanan" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="progress" :aria-valuetext="`${progress}% selesai; ${completeCount} dari ${milestoneSummary.total} milestone`"><span :style="{ width: `${progress}%` }"></span></div>
                <div class="journey-date"><FaIcon :icon="['fas', 'calendar-days']" /><span>Hari istimewa kalian</span><strong>{{ dateLabel(weddingDate) }}</strong></div>
            </section>

            <div v-if="error" class="journey-inline-error" role="alert">{{ error }}</div>
            <div v-if="!milestoneSummary.total" class="journey-empty">
                <span class="journey-empty__icon"><FaIcon :icon="['fas', 'heart']" /></span><p class="eyebrow">MULAI DARI GAMBARAN BESAR</p><h2>Belum ada milestone dalam perjalanan kalian.</h2><p>Buat tahap penting seperti menentukan konsep, memilih venue, atau menyelesaikan persiapan akhir.</p><button class="button button--primary" @click="openCreate"><FaIcon :icon="['fas', 'plus']" /> Buat milestone pertama</button>
            </div>

            <section v-else class="journey-timeline" aria-label="Timeline milestone">
                <div class="journey-timeline__intro"><div><p class="eyebrow">GARIS WAKTU PERSIAPAN</p><h2>Langkah besar kalian</h2></div><span>{{ milestoneSummary.total }} milestone</span></div>
                <TransitionGroup name="list" tag="div" class="journey-timeline__items">
                <article v-for="(item, index) in milestones" :key="item.id" class="milestone-row" :class="{ 'milestone-row--done': item.status === 'done', 'milestone-row--active': item.status === 'in_progress' }">
                    <div class="milestone-rail"><span class="milestone-rail__dot"><FaIcon v-if="item.status === 'done'" :icon="['fas', 'check']" /></span><span v-if="index < milestones.length - 1" class="milestone-rail__line"></span></div>
                    <button class="milestone-card" type="button" @click="selected = item">
                        <span class="milestone-card__meta"><span>{{ item.category || 'Persiapan' }}</span><span>{{ item.target_date ? `${dateLabel(item.target_date)} · ${dateDistance(item.target_date)}` : 'Tanpa target tanggal' }}</span></span>
                        <strong>{{ item.title }}</strong><p v-if="item.description">{{ item.description }}</p>
                        <span class="milestone-card__footer"><span>{{ item.completed_tasks_count }} / {{ item.tasks_count }} tugas selesai</span><span>{{ item.status === 'done' ? 'Selesai' : item.status === 'in_progress' ? 'Sedang berjalan' : 'Akan datang' }} <FaIcon :icon="['fas', 'arrow-right']" /></span></span>
                    </button>
                </article>
                </TransitionGroup>
                <PaginationControls :pagination="pagination" @change="load" />
            </section>
        </template>

        <Transition name="overlay" appear>
        <div v-if="editorOpen" class="journey-overlay" @click.self="formGuard.requestClose()">
            <section class="journey-dialog" role="dialog" aria-modal="true" aria-labelledby="milestone-form-title">
                <header><div><p class="eyebrow">PERJALANAN KALIAN</p><h2 id="milestone-form-title">{{ formTarget ? 'Ubah milestone' : 'Tambah milestone' }}</h2></div><button class="icon-button" aria-label="Tutup form" @click="formGuard.requestClose()"><FaIcon :icon="['fas', 'xmark']" /></button></header>
                <form @submit.prevent="save"><label>Nama milestone<input v-model.trim="form.title" required maxlength="160" placeholder="Contoh: Mengunci venue" /></label><label>Deskripsi <span>Opsional</span><textarea v-model.trim="form.description" rows="3" placeholder="Apa yang ingin dicapai pada tahap ini?"></textarea></label><div class="journey-form-grid"><label>Kategori<input v-model.trim="form.category" maxlength="80" placeholder="Persiapan" /></label><label>Target tanggal<input v-model="form.target_date" type="date" /></label></div><label>Status<MotionSelect v-model="form.status"><option value="upcoming">Akan datang</option><option value="in_progress">Sedang berjalan</option><option value="done">Selesai</option></MotionSelect></label><p v-if="formError" class="journey-inline-error" role="alert">{{ formError }}</p><footer><button class="button button--quiet" type="button" @click="formGuard.requestClose()">Batal</button><button class="button button--primary" type="submit" :disabled="saving">{{ saving ? 'Menyimpan…' : formTarget ? 'Simpan perubahan' : 'Simpan milestone' }}</button></footer></form>
            </section>
        </div>
        </Transition>

        <Transition name="overlay" appear>
        <div v-if="selected && !editorOpen" class="journey-overlay" @click.self="selected = null">
            <section class="journey-dialog journey-dialog--detail" role="dialog" aria-modal="true" aria-labelledby="milestone-detail-title">
                <RecordDetailHero id="milestone-detail-title" heading-tag="h2" eyebrow="DETAIL MILESTONE" :title="selected.title" :subtitle="selected.description || 'Tahap penting dalam perjalanan persiapan kalian.'">
                    <template #meta><span class="record-detail-chip">{{ selected.category || 'Persiapan' }}</span><span class="record-detail-chip" :class="selected.status === 'done' ? '' : selected.status === 'in_progress' ? 'record-detail-chip--warning' : 'record-detail-chip--muted'">{{ selected.status === 'done' ? 'Selesai' : selected.status === 'in_progress' ? 'Sedang berjalan' : 'Akan datang' }}</span><span class="record-detail-chip record-detail-chip--muted">{{ dateLabel(selected.target_date) }}</span></template>
                    <template #actions><button class="icon-button" aria-label="Tutup detail" @click="selected = null"><FaIcon :icon="['fas', 'xmark']" /></button></template>
                </RecordDetailHero>
                <div class="journey-detail-tasks"><h3>Tugas terkait <span>{{ selected.tasks?.length ?? 0 }}</span></h3><p v-if="!selected.tasks?.length">Belum ada tugas yang terhubung dengan milestone ini.</p><div v-for="task in selected.tasks" :key="task.id" class="journey-task"><FaIcon :icon="['fas', task.status === 'completed' ? 'circle-check' : 'clock']" /><span>{{ task.title }}</span><small>{{ task.status === 'completed' ? 'Selesai' : task.due_at ? dateLabel(task.due_at) : 'Belum selesai' }}</small></div></div>
                <footer><button class="button button--quiet journey-delete" @click="remove(selected)">Hapus</button><button class="button button--quiet" @click="openEdit(selected); selected = null">Ubah milestone</button><button v-if="selected.status !== 'done'" class="button button--primary" @click="setStatus(selected, 'done')">Tandai selesai</button><button v-else class="button button--primary" @click="setStatus(selected, 'upcoming')">Buka kembali</button></footer>
            </section>
        </div>
        </Transition>
        <ConfirmDialog :open="confirmOpen" :title="confirmOptions.title" :message="confirmOptions.message" :confirm-label="confirmOptions.confirmLabel" @cancel="finishConfirm(false)" @confirm="finishConfirm(true)" />
    </section>
</template>

<style scoped>
.journey-page{max-width:1180px;margin:auto;padding:40px clamp(20px,4vw,60px) 72px;color:var(--text-primary)}.journey-header{display:flex;align-items:flex-end;justify-content:space-between;gap:28px;margin:4px 0 28px}.journey-header h1{font-family:var(--font-display);font-size:clamp(38px,4vw,54px);font-weight:500;line-height:1.02;letter-spacing:-1px;margin:20px 0 12px;max-width:720px}.journey-header>div>p:last-child{color:var(--text-secondary);font-size:14px;max-width:650px;line-height:1.7;margin:0}.journey-summary{background:var(--surface);border:1px solid var(--champagne);border-radius:18px;padding:22px 26px;display:grid;grid-template-columns:180px minmax(120px,1fr) 250px;align-items:center;gap:24px;box-shadow:0 5px 24px color-mix(in srgb, var(--text-primary) 3%, transparent)}.journey-summary>div:first-child{display:flex;flex-direction:column;gap:4px}.journey-summary>div:first-child span,.journey-date>span,.journey-detail-date span{font-size:10px;letter-spacing:1.25px;color:var(--text-secondary);font-weight:700}.journey-summary strong{font-size:24px;color:var(--accent)}.journey-summary small{font-size:12px;color:var(--text-secondary)}.journey-summary .progress-track{margin:0}.journey-date{border-left:1px solid #e9e8e2;padding-left:22px;display:grid;grid-template-columns:22px 1fr;gap:2px 8px;align-items:center}.journey-date svg{grid-row:span 2;color:var(--accent)}.journey-date strong{font-size:13px;font-weight:600}.journey-timeline{margin-top:44px}.journey-timeline__intro{display:flex;align-items:flex-end;justify-content:space-between;margin:0 0 22px}.journey-timeline__intro h2{font-family:var(--font-display);font-size:30px;font-weight:500;margin:8px 0 0}.journey-timeline__intro>span{color:var(--text-secondary);font-size:12px}.milestone-row{display:grid;grid-template-columns:38px minmax(0,1fr);gap:14px;min-height:140px}.milestone-rail{display:flex;flex-direction:column;align-items:center}.milestone-rail__dot{width:30px;height:30px;border:1px solid #c8d0c1;border-radius:50%;display:grid;place-items:center;color:var(--accent);background:var(--surface-soft);font-size:12px;z-index:1}.milestone-row--active .milestone-rail__dot{background:var(--accent);color:var(--surface);border-color:var(--accent);box-shadow:0 0 0 5px color-mix(in srgb, var(--accent) 9%, transparent)}.milestone-row--done .milestone-rail__dot{background:var(--accent);color:white}.milestone-rail__line{width:1px;background:#d9ded5;flex:1;margin:5px 0}.milestone-card{width:100%;text-align:left;background:var(--surface);border:1px solid var(--champagne);border-radius:16px;padding:18px 22px;margin-bottom:16px;cursor:pointer;color:inherit;transition:box-shadow .15s,border-color .15s}.milestone-card:hover{border-color:#bdc8b7;box-shadow:0 8px 24px color-mix(in srgb, var(--text-primary) 4%, transparent)}.milestone-card__meta,.milestone-card__footer{display:flex;align-items:center;justify-content:space-between;gap:14px}.milestone-card__meta{font-size:11px;color:#757b72}.milestone-card__meta span:first-child{color:var(--accent);font-weight:700;letter-spacing:.3px}.milestone-card>strong{display:block;font-family:var(--font-display);font-size:26px;font-weight:500;margin:12px 0 4px}.milestone-card>p{font-size:13px;color:var(--text-secondary);line-height:1.6;margin:0 0 12px;max-width:780px}.milestone-card__footer{border-top:1px solid #f0f0eb;margin-top:13px;padding-top:12px;font-size:11px;color:var(--text-secondary)}.milestone-card__footer>span:last-child{color:var(--accent);font-weight:600}.milestone-card__footer svg{margin-left:5px}.journey-empty{margin-top:42px;padding:46px 24px;text-align:center;background:var(--surface);border:1px solid var(--champagne);border-radius:20px}.journey-empty__icon{display:grid;place-items:center;width:52px;height:52px;border-radius:50%;background:var(--surface-soft);color:var(--accent);margin:0 auto 18px}.journey-empty h2{font-family:var(--font-display);font-size:32px;font-weight:500;margin:10px 0}.journey-empty>p:not(.eyebrow){color:var(--text-secondary);font-size:13px;max-width:500px;margin:0 auto 22px;line-height:1.7}.journey-inline-error{padding:12px 14px;border-radius:10px;background:#fff0ee;color:#9c3733;font-size:13px}.journey-overlay{position:fixed;inset:0;background:#20231fc0;z-index:100;display:flex;justify-content:flex-end}.journey-dialog{background:var(--surface);width:min(520px,100%);height:100%;overflow:auto;padding:30px clamp(20px,3vw,36px);box-shadow:-12px 0 50px #14171030}.journey-dialog>header{display:flex;align-items:flex-start;justify-content:space-between;border-bottom:1px solid var(--champagne);padding-bottom:20px}.journey-dialog h2{font-family:var(--font-display);font-size:34px;font-weight:500;margin:6px 0}.journey-dialog form{padding-top:24px;display:flex;flex-direction:column;gap:17px}.journey-dialog label{display:flex;flex-direction:column;gap:7px;font-size:12px;font-weight:600;color:var(--text-secondary)}.journey-dialog label span{font-size:10px;font-weight:400;color:#888}.journey-dialog input,.journey-dialog textarea,.journey-dialog select{font:inherit;font-size:13px;color:var(--text-primary);border:1px solid var(--border);background:var(--surface);border-radius:9px;padding:12px 13px;outline:none}.journey-dialog input:focus,.journey-dialog textarea:focus,.journey-dialog select:focus{border-color:var(--accent);box-shadow:0 0 0 3px #74856c1c}.journey-dialog textarea{resize:vertical}.journey-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.journey-dialog footer{display:flex;justify-content:flex-end;gap:8px;margin-top:10px}.journey-dialog--detail{width:min(600px,100%)}.journey-detail-copy{font-size:14px;line-height:1.7;color:var(--text-secondary);margin:24px 0}.journey-detail-date{padding:15px;background:var(--surface-soft);border-radius:10px;display:flex;flex-direction:column;gap:4px}.journey-detail-date strong{font-size:14px}.journey-detail-tasks{margin:26px 0}.journey-detail-tasks h3{display:flex;justify-content:space-between;font-size:14px;margin-bottom:14px}.journey-detail-tasks h3 span{font-size:11px;color:var(--text-secondary)}.journey-detail-tasks>p{font-size:12px;color:var(--text-secondary)}.journey-task{display:flex;align-items:center;gap:10px;padding:13px 0;border-bottom:1px solid #ecece5;font-size:12px}.journey-task svg{color:var(--accent)}.journey-task span{flex:1}.journey-task small{color:var(--text-secondary)}.journey-dialog--detail footer{border-top:1px solid var(--champagne);padding-top:18px}.journey-delete{margin-right:auto;color:#a33c34!important}
@media(max-width:760px){.journey-page{padding:26px 18px 50px}.journey-header{align-items:flex-start;flex-direction:column}.journey-header h1{font-size:40px}.journey-summary{grid-template-columns:1fr 1fr;gap:16px;padding:18px}.journey-summary .progress-track{grid-column:span 2;grid-row:2}.journey-date{padding-left:14px}.milestone-row{grid-template-columns:25px 1fr;gap:9px}.milestone-rail__dot{width:24px;height:24px}.milestone-card{padding:15px}.milestone-card__meta{align-items:flex-start;flex-direction:column;gap:5px}.milestone-card>strong{font-size:23px}.milestone-card__footer{align-items:flex-start;flex-direction:column}.journey-form-grid{grid-template-columns:1fr}.journey-dialog--detail footer{flex-wrap:wrap}.journey-dialog--detail footer .button{font-size:11px;padding:10px}.journey-dialog--detail footer .journey-delete{width:100%;justify-content:flex-start}}
.journey-summary > div:first-child > span,
.journey-date > span { font-size: 12px; }
.journey-summary > div:first-child > strong { font-size: 28px; line-height: 1.15; font-variant-numeric: tabular-nums; }
.journey-summary > div:first-child > small { font-size: 14px; }
.journey-date > strong { font-size: 15px; }
</style>
