<script setup>
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PaginationControls from '../../components/PaginationControls.vue';
import RecordDetailHero from '../../components/RecordDetailHero.vue';
import { registerUnsavedGuard as addUnsavedGuard } from '../../unsavedChanges';

const props = defineProps({ workspaceId: { type: [String, Number], required: true } });
const route = useRoute();
const router = useRouter();
let unregisterUnsavedGuard = null;
let resolveRouteGuard = null;
const tasks = ref([]);
const members = ref([]);
const milestones = ref([]);
const events = ref([]);
const pagination = ref(null);
const loading = ref(true);
const error = ref('');
const busy = ref(false);
const filters = reactive({ q: '', status: '', category: '', assignee_id: '', event_id: String(route.query.event_id ?? '') });
const form = reactive({ title: '', description: '', category: '', milestone_id: '', event_id: '', assignee_id: '', due_at: '', priority: 'normal', status: 'planned' });
const searchTimer = ref(null);
const formErrors = ref({});
const selectedTask = ref(null);
const showForm = ref(false);
const showDeleteConfirm = ref(false);
const showDiscardConfirm = ref(false);
const editingTaskId = ref(null);
const originalForm = ref('');

const completion = computed(() => pagination.value?.summary ?? { total: 0, completed: 0, open: 0, percent: 0 });
const isDirty = computed(() => JSON.stringify(form) !== originalForm.value);
const categories = computed(() => [...new Set(tasks.value.map((task) => task.category).filter(Boolean))].sort());

function resetForm(task = null) {
    Object.assign(form, {
        title: task?.title ?? '',
        description: task?.description ?? '',
        category: task?.category ?? '',
        milestone_id: task?.milestone?.id ? String(task.milestone.id) : '',
        event_id: task?.event?.id ? String(task.event.id) : (filters.event_id !== 'none' ? filters.event_id : ''),
        assignee_id: task?.assignee?.id ? String(task.assignee.id) : '',
        due_at: task?.due_at ?? '',
        priority: task?.priority ?? 'normal',
        status: task?.status ?? 'planned',
    });
    originalForm.value = JSON.stringify(form);
    formErrors.value = {};
}

async function load(page = 1, { preserveContent = false } = {}) {
    if (!preserveContent) loading.value = true;
    error.value = '';
    try {
        const params = { page, per_page: 20 };
        for (const [key, value] of Object.entries(filters)) if (value) params[key] = value;
        const response = await axios.get(`/api/workspaces/${props.workspaceId}/tasks`, { params });
        if (page > response.data.meta.last_page && response.data.meta.last_page >= 1) {
            return await load(response.data.meta.last_page, { preserveContent });
        }
        tasks.value = response.data.data;
        pagination.value = response.data.meta;
    } catch {
        error.value = 'Tugas belum berhasil dimuat. Coba lagi.';
    } finally {
        if (!preserveContent) loading.value = false;
    }
}

async function loadMembers() {
    try {
        const { data } = await axios.get(`/api/workspaces/${props.workspaceId}/members`);
        members.value = data.members;
    } catch {
        members.value = [];
    }
}

async function loadMilestones() {
    try { milestones.value = (await axios.get(`/api/workspaces/${props.workspaceId}/milestones`)).data.milestones; }
    catch { milestones.value = []; }
}

async function loadEvents() {
    try { events.value = (await axios.get(`/api/workspaces/${props.workspaceId}/events`, { params: { for_select: true } })).data.events; }
    catch { events.value = []; }
}

function scheduleSearch() {
    window.clearTimeout(searchTimer.value);
    searchTimer.value = window.setTimeout(() => load(1), 250);
}

function openCreate() {
    editingTaskId.value = null;
    selectedTask.value = null;
    resetForm();
    showForm.value = true;
}

function openEdit(task) {
    editingTaskId.value = task.id;
    selectedTask.value = null;
    resetForm(task);
    showForm.value = true;
}

function closeForm() {
    if (isDirty.value) {
        showDiscardConfirm.value = true;
        return;
    }
    showForm.value = false;
}

function keepEditing() {
    showDiscardConfirm.value = false;
    resolveRouteGuard?.(false);
    resolveRouteGuard = null;
}

function discardForm() {
    showDiscardConfirm.value = false;
    showForm.value = false;
    resolveRouteGuard?.(true);
    resolveRouteGuard = null;
}

function confirmRouteLeave() {
    if (!showForm.value || !isDirty.value) return true;
    showDiscardConfirm.value = true;
    return new Promise((resolve) => { resolveRouteGuard = resolve; });
}

async function saveTask() {
    busy.value = true;
    formErrors.value = {};
    const payload = {
        ...form,
        assignee_id: form.assignee_id || null,
        milestone_id: form.milestone_id || null,
        event_id: form.event_id || null,
        due_at: form.due_at || null,
    };
    try {
        if (editingTaskId.value) await axios.patch(`/api/tasks/${editingTaskId.value}`, payload);
        else await axios.post(`/api/workspaces/${props.workspaceId}/tasks`, payload);
        showForm.value = false;
        await load(pagination.value?.current_page ?? 1);
    } catch (failure) {
        formErrors.value = failure.response?.data?.errors ?? { form: [failure.response?.data?.message ?? 'Tugas belum berhasil disimpan. Coba lagi.'] };
    } finally {
        busy.value = false;
    }
}

async function openDetail(task) {
    try {
        const { data } = await axios.get(`/api/tasks/${task.id}`);
        selectedTask.value = data.task;
    } catch {
        selectedTask.value = task;
    }
}

async function setStatus(task, status) {
    const previousStatus = task.status;
    const row = tasks.value.find((item) => item.id === task.id);
    const previousSummary = pagination.value?.summary ? { ...pagination.value.summary } : null;

    // Update the row immediately; refreshing through the loading state unmounts
    // the list and can make the browser clamp a deep scroll position to the top.
    task.status = status;
    if (row && row !== task) row.status = status;
    if (selectedTask.value?.id === task.id) selectedTask.value.status = status;
    if (previousSummary && previousStatus !== status) {
        const completed = Math.max(0, Math.min(previousSummary.total,
            previousSummary.completed + (status === 'completed' ? 1 : -1)));
        pagination.value.summary = {
            ...previousSummary,
            completed,
            open: previousSummary.total - completed,
            percent: previousSummary.total ? Math.round((completed / previousSummary.total) * 100) : 0,
        };
    }

    try {
        await axios.patch(`/api/tasks/${task.id}`, { status });
        await load(pagination.value?.current_page ?? 1, { preserveContent: true });
    } catch {
        task.status = previousStatus;
        if (row && row !== task) row.status = previousStatus;
        if (selectedTask.value?.id === task.id) selectedTask.value.status = previousStatus;
        if (previousSummary && pagination.value) pagination.value.summary = previousSummary;
        error.value = 'Status tugas belum berhasil diubah. Coba lagi.';
    }
}

async function deleteTask() {
    if (!selectedTask.value) return;
    busy.value = true;
    try {
        await axios.delete(`/api/tasks/${selectedTask.value.id}`);
        showDeleteConfirm.value = false;
        selectedTask.value = null;
        await load(pagination.value?.current_page ?? 1);
    } catch {
        error.value = 'Tugas belum berhasil dihapus. Coba lagi.';
        showDeleteConfirm.value = false;
    } finally {
        busy.value = false;
    }
}

function dateLabel(value) {
    if (!value) return 'Tanpa tenggat';
    return new Date(`${value}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function statusLabel(status) {
    return { planned: 'Belum dimulai', in_progress: 'Sedang dikerjakan', completed: 'Selesai' }[status] ?? status;
}

watch(() => [filters.status, filters.category, filters.assignee_id, filters.event_id], () => load(1));
watch(() => route.query.event_id, (eventId) => {
    filters.event_id = String(eventId ?? '');
});
onMounted(() => {
    load();
    loadMembers();
    loadMilestones();
    loadEvents();
    unregisterUnsavedGuard = addUnsavedGuard(confirmRouteLeave);
    if (route.query.create === '1') {
        openCreate();
        const query = { ...route.query };
        delete query.create;
        router.replace({ path: route.path, query });
    }
});
onBeforeUnmount(() => unregisterUnsavedGuard?.());
</script>

<template>
    <section class="tasks-page page-content" aria-labelledby="tasks-title">
        <div class="page-breadcrumb"><span>Persiapan pernikahan</span><span>/</span><span>Checklist</span></div>
        <header class="page-header">
            <div><h1 id="tasks-title">Checklist Persiapan</h1><p>Pantau tugas menuju hari-H bersama pasangan.</p></div>
            <button class="button button--primary" type="button" @click="openCreate"><FaIcon :icon="['fas', 'plus']" aria-hidden="true" /> Tambah tugas</button>
        </header>

        <div v-if="loading && !pagination" class="task-list-skeleton" aria-label="Memuat tugas">
            <div class="skeleton-line skeleton-line--title"></div><div v-for="n in 4" :key="n" class="skeleton-block skeleton-block--row"></div>
        </div>
        <div v-else-if="error && !pagination" class="page-error" role="alert"><p>{{ error }}</p><button class="button button--quiet" type="button" @click="load()">Coba lagi</button></div>
        <template v-else>
            <section class="task-summary" aria-label="Ringkasan checklist">
                <div class="task-summary__ring" role="progressbar" aria-label="Kelengkapan rencana" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="completion.percent" :aria-valuetext="`${completion.percent}% selesai; ${completion.completed} dari ${completion.total} tugas`" :style="{ '--progress': `${completion.percent}%` }"><span><AnimatedNumber :value="completion.percent" suffix="%" /></span></div>
                <div class="task-summary__main"><span>KELENGKAPAN RENCANA</span><strong><AnimatedNumber :value="completion.completed" /> dari {{ completion.total }} selesai</strong></div>
                <div class="task-summary__stat"><i></i><AnimatedNumber :value="completion.open" /> tugas tersisa</div>
                <div class="task-summary__stat"><i class="task-summary__stat--complete"></i><AnimatedNumber :value="completion.completed" /> selesai</div>
                <div v-if="error" class="task-summary__error" role="alert">{{ error }}</div>
            </section>

            <section class="task-filters" aria-label="Filter tugas">
                <label class="task-search"><FaIcon :icon="['fas', 'magnifying-glass']" aria-hidden="true" /><span class="sr-only">Cari tugas</span><input v-model.trim="filters.q" placeholder="Cari tugas atau catatan" @input="scheduleSearch"></label>
                <label><span class="sr-only">Kategori</span><MotionSelect v-model="filters.category"><option value="">Semua kategori</option><option v-for="category in categories" :key="category" :value="category">{{ category }}</option></MotionSelect></label>
                <label><span class="sr-only">Acara</span><MotionSelect v-model="filters.event_id"><option value="">Semua acara</option><option v-for="event in events" :key="event.id" :value="String(event.id)">{{ event.title }}</option><option value="none">Umum / tanpa acara</option></MotionSelect></label>
                <label><span class="sr-only">Penanggung jawab</span><MotionSelect v-model="filters.assignee_id"><option value="">Semua penanggung jawab</option><option v-for="member in members" :key="member.id" :value="member.id">{{ member.name }}</option></MotionSelect></label>
                <label><span class="sr-only">Status tugas</span><MotionSelect v-model="filters.status"><option value="">Semua status</option><option value="planned">Belum dimulai</option><option value="in_progress">Sedang dikerjakan</option><option value="completed">Selesai</option></MotionSelect></label>
                <button class="filter-reset" type="button" aria-label="Bersihkan filter" @click="Object.assign(filters, { q: '', status: '', category: '', assignee_id: '', event_id: '' }); load(1)"><FaIcon :icon="['fas', 'rotate']" aria-hidden="true" /></button>
            </section>

            <div v-if="loading" class="task-inline-loading" aria-live="polite">Memuat tugas…</div>
            <div v-else-if="tasks.length === 0" class="task-empty">
                <div class="auth-mark" aria-hidden="true"><FaIcon :icon="['fas', 'circle-check']" /></div>
                <h2>{{ filters.q || filters.status || filters.category || filters.assignee_id || filters.event_id ? 'Tidak ada tugas yang cocok.' : 'Belum ada tugas.' }}</h2>
                <p>{{ filters.q || filters.status || filters.category || filters.assignee_id || filters.event_id ? 'Coba ubah filter atau kata pencarian.' : 'Tambahkan satu hal yang perlu kalian siapkan untuk memulai.' }}</p>
                <button v-if="!(filters.q || filters.status || filters.category || filters.assignee_id || filters.event_id)" class="button button--primary" type="button" @click="openCreate"><FaIcon :icon="['fas', 'plus']" aria-hidden="true" /> Tambah tugas pertama</button>
                <button v-else class="button button--quiet" type="button" @click="Object.assign(filters, { q: '', status: '', category: '', assignee_id: '', event_id: '' }); load(1)">Bersihkan filter</button>
            </div>
            <div v-else class="task-list">
                <div class="task-list__title"><div><span class="eyebrow__dot"></span><h2>Tugas kalian</h2></div><span>{{ pagination.total }} terdaftar</span></div>
                <TransitionGroup name="task-list" tag="div" class="task-list__items">
                <article v-for="task in tasks" :key="task.id" class="task-row">
                    <button class="task-check" :class="{ 'task-check--checked': task.status === 'completed' }" type="button" :aria-label="task.status === 'completed' ? `Tandai ${task.title} belum selesai` : `Tandai ${task.title} selesai`" @click="setStatus(task, task.status === 'completed' ? 'planned' : 'completed')">
                        <FaIcon v-if="task.status === 'completed'" :icon="['fas', 'check']" aria-hidden="true" />
                    </button>
                    <button class="task-row__main" type="button" @click="openDetail(task)">
                        <strong :class="{ 'task-row__title--done': task.status === 'completed' }">{{ task.title }}</strong>
                        <span class="task-row__meta"><span>{{ task.category }}</span><span>·</span><span>{{ task.event?.title ?? 'Umum' }}</span><span>·</span><span>{{ dateLabel(task.due_at) }}</span><span>·</span><span>{{ task.assignee?.name ?? 'Belum ditugaskan' }}</span></span>
                    </button>
                    <span class="task-priority" :class="`task-priority--${task.priority}`">{{ { high: 'Prioritas tinggi', normal: 'Normal', low: 'Rendah' }[task.priority] }}</span>
                    <button class="task-row__more" type="button" :aria-label="`Buka detail ${task.title}`" @click="openDetail(task)"><FaIcon :icon="['fas', 'chevron-right']" aria-hidden="true" /></button>
                </article>
                </TransitionGroup>
                <PaginationControls v-if="pagination" :pagination="pagination" @change="load" />
            </div>
        </template>

        <Transition name="overlay" appear>
        <div v-if="showForm" class="sheet-backdrop" @click.self="closeForm">
            <aside class="task-sheet" role="dialog" aria-modal="true" aria-labelledby="task-form-title">
                <header class="task-sheet__header"><div><p class="eyebrow">CHECKLIST PERSIAPAN</p><h2 id="task-form-title">{{ editingTaskId ? 'Ubah tugas' : 'Tambah tugas' }}</h2></div><button class="icon-button" aria-label="Tutup form" type="button" @click="closeForm"><FaIcon :icon="['fas', 'xmark']" aria-hidden="true" /></button></header>
                <form class="task-form" @submit.prevent="saveTask">
                    <label class="field"><span>Nama tugas</span><input v-model.trim="form.title" maxlength="200" placeholder="Contoh: Pilih fotografer" required><small v-if="formErrors.title" class="field-error">{{ formErrors.title[0] }}</small></label>
                    <label class="field"><span>Catatan <em>Opsional</em></span><textarea v-model.trim="form.description" rows="3" maxlength="2000" placeholder="Tambahkan detail yang perlu diingat"></textarea><small v-if="formErrors.description" class="field-error">{{ formErrors.description[0] }}</small></label>
                    <div class="task-form__row">
                        <label class="field"><span>Kategori</span><input v-model.trim="form.category" maxlength="80" placeholder="Contoh: Vendor" required><small v-if="formErrors.category" class="field-error">{{ formErrors.category[0] }}</small></label>
                        <label class="field"><span>Tenggat <em>Opsional</em></span><input v-model="form.due_at" type="date"><small v-if="formErrors.due_at" class="field-error">{{ formErrors.due_at[0] }}</small></label>
                    </div>
                    <div class="task-form__row">
                        <label class="field"><span>Penanggung jawab</span><MotionSelect v-model="form.assignee_id"><option value="">Belum ditugaskan</option><option v-for="member in members" :key="member.id" :value="String(member.id)">{{ member.name }}</option></MotionSelect><small v-if="formErrors.assignee_id" class="field-error">{{ formErrors.assignee_id[0] }}</small></label>
                        <label class="field"><span>Prioritas</span><MotionSelect v-model="form.priority"><option value="low">Rendah</option><option value="normal">Normal</option><option value="high">Tinggi</option></MotionSelect></label>
                    </div>
                    <label class="field"><span>Milestone <em>Opsional</em></span><MotionSelect v-model="form.milestone_id"><option value="">Tanpa milestone</option><option v-for="milestone in milestones" :key="milestone.id" :value="String(milestone.id)">{{ milestone.title }}</option></MotionSelect><small v-if="formErrors.milestone_id" class="field-error">{{ formErrors.milestone_id[0] }}</small></label>
                    <label class="field"><span>Untuk acara <em>Opsional</em></span><MotionSelect v-model="form.event_id"><option value="">Umum / tanpa acara</option><option v-for="event in events" :key="event.id" :value="String(event.id)">{{ event.title }}</option></MotionSelect><small v-if="formErrors.event_id" class="field-error">{{ formErrors.event_id[0] }}</small></label>
                    <label v-if="editingTaskId" class="field"><span>Status</span><MotionSelect v-model="form.status"><option value="planned">Belum dimulai</option><option value="in_progress">Sedang dikerjakan</option><option value="completed">Selesai</option></MotionSelect></label>
                    <p v-if="formErrors.form" class="form-alert form-alert--error" role="alert">{{ formErrors.form[0] }}</p>
                    <footer class="task-form__actions"><button class="button button--quiet" type="button" @click="closeForm">Batal</button><button class="button button--primary" type="submit" :disabled="busy">{{ busy ? 'Menyimpan…' : 'Simpan tugas' }}</button></footer>
                </form>
            </aside>
        </div>
        </Transition>

        <Transition name="overlay" appear>
        <div v-if="selectedTask" class="sheet-backdrop" @click.self="selectedTask = null">
            <aside class="task-sheet" role="dialog" aria-modal="true" aria-labelledby="task-detail-title">
                <RecordDetailHero id="task-detail-title" heading-tag="h2" eyebrow="DETAIL TUGAS" :title="selectedTask.title" :subtitle="selectedTask.description || 'Belum ada catatan untuk tugas ini.'">
                    <template #meta><span class="record-detail-chip">{{ selectedTask.category || 'Persiapan' }}</span><span class="record-detail-chip record-detail-chip--muted">{{ statusLabel(selectedTask.status) }}</span><span v-if="selectedTask.priority === 'high'" class="record-detail-chip record-detail-chip--warning">Prioritas tinggi</span></template>
                    <template #actions><button class="icon-button" aria-label="Tutup detail" type="button" @click="selectedTask = null"><FaIcon :icon="['fas', 'xmark']" aria-hidden="true" /></button></template>
                </RecordDetailHero>
                <div class="task-detail">
                    <dl><div><dt>Acara</dt><dd>{{ selectedTask.event?.title ?? 'Umum / tanpa acara' }}</dd></div><div><dt>Tenggat</dt><dd>{{ dateLabel(selectedTask.due_at) }}</dd></div><div><dt>Milestone</dt><dd>{{ selectedTask.milestone?.title ?? 'Tanpa milestone' }}</dd></div><div><dt>Penanggung jawab</dt><dd>{{ selectedTask.assignee?.name ?? 'Belum ditugaskan' }}</dd></div><div><dt>Prioritas</dt><dd>{{ { high: 'Tinggi', normal: 'Normal', low: 'Rendah' }[selectedTask.priority] }}</dd></div></dl>
                    <div class="task-detail__actions"><button class="button button--quiet" type="button" @click="openEdit(selectedTask)">Ubah tugas</button><button class="button button--quiet button--danger" type="button" @click="showDeleteConfirm = true">Hapus</button><button class="button button--primary" type="button" @click="setStatus(selectedTask, selectedTask.status === 'completed' ? 'planned' : 'completed')">{{ selectedTask.status === 'completed' ? 'Tandai belum selesai' : 'Tandai selesai' }}</button></div>
                </div>
            </aside>
        </div>
        </Transition>

        <Transition name="overlay" appear>
        <div v-if="showDiscardConfirm" class="confirm-backdrop">
            <section class="confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="discard-title"><h2 id="discard-title">Keluar tanpa menyimpan?</h2><p>Perubahan pada form ini akan hilang.</p><div><button class="button button--quiet" type="button" @click="keepEditing">Lanjut mengedit</button><button class="button button--primary" type="button" @click="discardForm">Keluar</button></div></section>
        </div>
        </Transition>
        <Transition name="overlay" appear>
        <div v-if="showDeleteConfirm" class="confirm-backdrop">
            <section class="confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="delete-title"><h2 id="delete-title">Hapus “{{ selectedTask?.title }}”?</h2><p>Tugas ini akan dihapus dari checklist kalian.</p><div><button class="button button--quiet" type="button" @click="showDeleteConfirm = false">Batal</button><button class="button button--destructive" type="button" :disabled="busy" @click="deleteTask">{{ busy ? 'Menghapus…' : 'Hapus tugas' }}</button></div></section>
        </div>
        </Transition>
    </section>
</template>
