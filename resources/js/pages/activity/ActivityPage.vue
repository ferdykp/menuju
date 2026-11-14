<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import PaginationControls from '../../components/PaginationControls.vue';
import { useConfirmDialog } from '../../composables/useConfirmDialog';
import { useUnsavedForm } from '../../composables/useUnsavedForm';

const props = defineProps({ workspaceId: { type: [String, Number], required: true } });
const { open: confirmOpen, options: confirmOptions, confirm: askConfirm, finish: finishConfirm } = useConfirmDialog();
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const rows = ref([]);
const pagination = ref(null);
const decisions = ref([]);
const events = ref([]);
const tab = ref('activity');
const editor = ref(false);
const selectedId = ref(null);
const formError = ref('');
const form = reactive(blankDecision());
const savedForm = ref('');
const formDirty = computed(() => JSON.stringify(form) !== savedForm.value);
const formGuard = useUnsavedForm({ isOpen: editor, isDirty: formDirty, confirm: askConfirm, onDiscard: () => { editor.value = false; }, title: 'Catatan keputusan belum disimpan' });
const openDecisions = computed(() => decisions.value.filter((decision) => decision.status === 'open').length);

function blankDecision() { return { title: '', context: '', options_text: '', event_id: '', status: 'open', outcome: '' }; }
async function load(page = 1) {
    loading.value = true; error.value = '';
    try {
        const { data } = await axios.get(`/api/workspaces/${props.workspaceId}/activity`, { params: { page, per_page: 30 } });
        rows.value = data.activities; pagination.value = data.pagination; decisions.value = data.decisions; events.value = data.events;
    } catch { error.value = 'Aktivitas belum berhasil dimuat. Coba lagi.'; }
    finally { loading.value = false; }
}
function openCreate() { selectedId.value = null; Object.assign(form, blankDecision()); savedForm.value = JSON.stringify(form); formError.value = ''; editor.value = true; }
function openEdit(decision) {
    selectedId.value = decision.id;
    Object.assign(form, { ...blankDecision(), ...decision, options_text: (decision.options ?? []).join('\n'), event_id: decision.event_id ? String(decision.event_id) : '' });
    savedForm.value = JSON.stringify(form);
    formError.value = ''; editor.value = true;
}
async function save() {
    saving.value = true; formError.value = '';
    const body = { title: form.title, context: form.context || null, options: form.options_text.split('\n').map((value) => value.trim()).filter(Boolean), event_id: form.event_id ? Number(form.event_id) : null, status: form.status, outcome: form.outcome || null };
    try {
        if (selectedId.value) await axios.patch(`/api/workspaces/${props.workspaceId}/decisions/${selectedId.value}`, body);
        else await axios.post(`/api/workspaces/${props.workspaceId}/decisions`, body);
        editor.value = false; await load();
    } catch (e) { formError.value = e.response?.data?.message ?? 'Catatan keputusan belum tersimpan.'; }
    finally { saving.value = false; }
}
async function closeEditor() { await formGuard.requestClose(); }
function statusLabel(value) { return { open: 'Perlu diputuskan', decided: 'Sudah diputuskan', parked: 'Ditunda' }[value] ?? value; }
function dateLabel(value) { return value ? new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : ''; }
onMounted(load);
</script>

<template>
    <section class="activity-page" aria-labelledby="activity-title">
        <div v-if="loading" class="page-loading" role="status">Memuat aktivitas…</div>
        <div v-else-if="error" class="page-error" role="alert"><p>{{ error }}</p><button class="button button--quiet" @click="load()">Coba lagi</button></div>
        <template v-else>
            <header class="activity-head"><div><span class="eyebrow">RUANG BERSAMA</span><h1 id="activity-title">Aktivitas & keputusan</h1><p>Lihat perubahan terbaru dan simpan hal yang perlu kalian putuskan bersama.</p></div><button class="button button--primary" @click="openCreate">Catat keputusan</button></header>
            <div class="activity-tabs" role="tablist" aria-label="Aktivitas dan keputusan"><button role="tab" :aria-selected="tab === 'activity'" :class="{ active: tab === 'activity' }" @click="tab = 'activity'">Aktivitas</button><button role="tab" :aria-selected="tab === 'decisions'" :class="{ active: tab === 'decisions' }" @click="tab = 'decisions'">Keputusan <span v-if="openDecisions">{{ openDecisions }}</span></button></div>
            <section v-if="tab === 'activity'" class="activity-panel" aria-label="Riwayat aktivitas">
                <div v-if="!rows.length" class="empty-state"><h2>Belum ada aktivitas</h2><p>Perubahan pada tugas, acara, tamu, kebutuhan, aset, dan keuangan akan tercatat di sini.</p></div>
                <ol v-else class="activity-timeline"><li v-for="item in rows" :key="item.id"><span class="activity-dot" aria-hidden="true"></span><div><p><strong>{{ item.actor?.name ?? 'Anggota workspace' }}</strong> {{ item.summary }}</p><time :datetime="item.occurred_at">{{ dateLabel(item.occurred_at) }}</time></div></li></ol><PaginationControls v-if="pagination" :pagination="pagination" @change="load" />
            </section>
            <section v-else class="decision-grid" aria-label="Catatan keputusan">
                <div v-if="!decisions.length" class="empty-state"><h2>Belum ada keputusan tercatat</h2><p>Catat pertanyaan, pilihan, lalu hasilnya supaya keputusan bersama mudah diingat.</p><button class="button button--quiet" @click="openCreate">Catat keputusan pertama</button></div>
                <article v-for="decision in decisions" :key="decision.id" class="decision-card"><div class="decision-card__top"><span class="decision-status" :class="`decision-status--${decision.status}`">{{ statusLabel(decision.status) }}</span><small v-if="decision.event">{{ decision.event.title }}</small></div><h2>{{ decision.title }}</h2><p v-if="decision.context">{{ decision.context }}</p><ul v-if="decision.options?.length"><li v-for="option in decision.options" :key="option">{{ option }}</li></ul><p v-if="decision.outcome" class="decision-outcome"><strong>Hasil:</strong> {{ decision.outcome }}</p><footer><small>Diperbarui {{ dateLabel(decision.updated_at) }}</small><button class="text-link" @click="openEdit(decision)">Ubah catatan</button></footer></article>
            </section>
        </template>
        <div v-if="editor" class="sheet-backdrop" @click.self="closeEditor"><aside class="guest-sheet finance-sheet" role="dialog" aria-modal="true" aria-labelledby="decision-form-title"><header><div><span class="eyebrow">CATATAN BERSAMA</span><h2 id="decision-form-title">{{ selectedId ? 'Ubah keputusan' : 'Catat keputusan' }}</h2></div><button class="icon-button" aria-label="Tutup" @click="closeEditor">×</button></header><form @submit.prevent="save"><label>Hal yang perlu diputuskan<input v-model.trim="form.title" maxlength="180" required placeholder="Contoh: pilih vendor katering"></label><label>Catatan konteks<textarea v-model.trim="form.context" maxlength="3000" placeholder="Apa yang perlu dipertimbangkan?"></textarea></label><label>Acara terkait<select v-model="form.event_id"><option value="">Umum</option><option v-for="event in events" :key="event.id" :value="String(event.id)">{{ event.title }}</option></select></label><label>Opsi yang dipertimbangkan <em>satu opsi per baris</em><textarea v-model="form.options_text" maxlength="3000" placeholder="Paket A&#10;Paket B"></textarea></label><div class="guest-form-row"><label>Status<select v-model="form.status"><option value="open">Perlu diputuskan</option><option value="decided">Sudah diputuskan</option><option value="parked">Ditunda</option></select></label></div><label v-if="form.status === 'decided'">Hasil keputusan<textarea v-model.trim="form.outcome" maxlength="3000" required placeholder="Catat pilihan akhirnya"></textarea></label><p v-if="formError" class="form-error" role="alert">{{ formError }}</p><button class="button button--primary" type="submit" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Simpan catatan' }}</button></form></aside></div>
        <ConfirmDialog :open="confirmOpen" :title="confirmOptions.title" :message="confirmOptions.message" :confirm-label="confirmOptions.confirmLabel" :destructive="confirmOptions.destructive" @cancel="finishConfirm(false)" @confirm="finishConfirm(true)" />
    </section>
</template>
