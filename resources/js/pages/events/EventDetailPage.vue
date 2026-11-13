<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import RecordDetailHero from '../../components/RecordDetailHero.vue';
import { useConfirmDialog } from '../../composables/useConfirmDialog';
import { useUnsavedForm } from '../../composables/useUnsavedForm';

import { formatRupiah } from '../../utils/money';
const props = defineProps({ workspaceId: { type: [String, Number], required: true }, timezone: { type: String, default: 'Asia/Jakarta' } });
const route = useRoute();
const router = useRouter();
const { open: confirmOpen, options: confirmOptions, confirm: askConfirm, finish: finishConfirm } = useConfirmDialog();
const event = ref(null);
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const formError = ref('');
const agendaError = ref('');
const agendaOpen = ref(false);
const agendaSaving = ref(false);
const agendaEditId = ref(null);
const agendaSaved = ref('');
const eventForm = reactive(blankEvent());
const agendaForm = reactive(blankAgenda());
const eventSaved = ref('');
const isEditing = computed(() => route.path.endsWith('/edit'));
const eventId = computed(() => route.path.match(/^\/app\/events\/([^/]+)/)?.[1] ?? '');
const isEventDirty = computed(() => isEditing.value && Boolean(event.value) && JSON.stringify(eventForm) !== eventSaved.value);
const isAgendaDirty = computed(() => agendaOpen.value && JSON.stringify(agendaForm) !== agendaSaved.value);
const eventGuard = useUnsavedForm({
    isOpen: computed(() => isEditing.value),
    isDirty: isEventDirty,
    confirm: askConfirm,
    onDiscard: () => { if (event.value) setEventForm(event.value); },
    title: 'Perubahan acara belum disimpan',
    message: 'Jika kembali sekarang, perubahan pada informasi acara akan dibuang.',
});
const agendaGuard = useUnsavedForm({
    isOpen: agendaOpen,
    isDirty: isAgendaDirty,
    confirm: askConfirm,
    onDiscard: () => { agendaOpen.value = false; },
    title: 'Perubahan agenda belum disimpan',
});

function blankEvent() { return { title: '', event_type: 'ceremony', event_date: '', start_time: '', end_time: '', venue_name: '', address: '', notes: '' }; }
function blankAgenda() { return { title: '', start_time: '', end_time: '', location: '', notes: '' }; }
function eventType(type) { return { engagement: 'Lamaran', ceremony: 'Akad / Pemberkatan', reception: 'Resepsi', family: 'Acara keluarga', other: 'Acara lainnya' }[type] ?? 'Acara'; }
function dateLabel(value) { return value ? new Date(`${String(value).slice(0, 10)}T12:00:00Z`).toLocaleDateString('id-ID', { timeZone: 'UTC', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : 'Tanggal belum ditentukan'; }
function timeLabel(value) { return value ? value.slice(0, 5) : '—'; }
function setEventForm(value) {
    Object.assign(eventForm, {
        ...blankEvent(),
        ...value,
        event_date: value.event_date ? String(value.event_date).slice(0, 10) : '',
        start_time: value.start_time?.slice(0, 5) ?? '',
        end_time: value.end_time?.slice(0, 5) ?? '',
    });
    eventSaved.value = JSON.stringify(eventForm);
}
async function load() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await axios.get(`/api/workspaces/${props.workspaceId}/events/${eventId.value}`);
        event.value = data.event;
        setEventForm(data.event);
    } catch (failure) {
        event.value = null;
        error.value = failure.response?.status === 404 ? 'Acara ini tidak ditemukan di workspace kalian.' : 'Detail acara belum berhasil dimuat. Coba lagi.';
    } finally { loading.value = false; }
}
async function saveEvent() {
    saving.value = true;
    formError.value = '';
    try {
        const payload = { ...eventForm, event_date: eventForm.event_date || null, start_time: eventForm.start_time || null, end_time: eventForm.end_time || null };
        const { data } = await axios.patch(`/api/workspaces/${props.workspaceId}/events/${eventId.value}`, payload);
        event.value = data.event;
        setEventForm(data.event);
        await router.replace(`/app/events/${data.event.id}`);
    } catch (failure) {
        formError.value = Object.values(failure.response?.data?.errors ?? {}).flat()[0] ?? failure.response?.data?.message ?? 'Acara belum berhasil disimpan. Periksa isian lalu coba lagi.';
    } finally { saving.value = false; }
}
async function cancelEditing() {
    if (await eventGuard.requestClose()) await router.push(`/app/events/${eventId.value}`);
}
function createAgenda() {
    agendaEditId.value = null;
    agendaError.value = '';
    Object.assign(agendaForm, blankAgenda());
    agendaSaved.value = JSON.stringify(agendaForm);
    agendaOpen.value = true;
}
function editAgenda(item) {
    agendaEditId.value = item.id;
    agendaError.value = '';
    Object.assign(agendaForm, { ...blankAgenda(), ...item, start_time: item.start_time?.slice(0, 5) ?? '', end_time: item.end_time?.slice(0, 5) ?? '' });
    agendaSaved.value = JSON.stringify(agendaForm);
    agendaOpen.value = true;
}
async function saveAgenda() {
    agendaSaving.value = true;
    agendaError.value = '';
    try {
        const payload = { ...agendaForm, start_time: agendaForm.start_time || null, end_time: agendaForm.end_time || null };
        const url = `/api/events/${event.value.id}/agenda-items${agendaEditId.value ? `/${agendaEditId.value}` : ''}`;
        const { data } = agendaEditId.value ? await axios.patch(url, payload) : await axios.post(url, payload);
        if (agendaEditId.value) event.value.agenda_items = event.value.agenda_items.map((item) => item.id === agendaEditId.value ? data.agenda_item : item);
        else event.value.agenda_items = [...(event.value.agenda_items ?? []), data.agenda_item];
        agendaOpen.value = false;
    } catch (failure) {
        agendaError.value = Object.values(failure.response?.data?.errors ?? {}).flat()[0] ?? failure.response?.data?.message ?? 'Agenda belum berhasil disimpan.';
    } finally { agendaSaving.value = false; }
}
async function deleteAgenda(item) {
    if (!await askConfirm({ title: `Hapus agenda “${item.title}”?`, message: 'Agenda ini akan dihapus dari rangkaian acara.', confirmLabel: 'Hapus agenda' })) return;
    try {
        await axios.delete(`/api/events/${event.value.id}/agenda-items/${item.id}`);
        event.value.agenda_items = event.value.agenda_items.filter((agenda) => agenda.id !== item.id);
    } catch (failure) { error.value = failure.response?.data?.message ?? 'Agenda belum berhasil dihapus. Coba lagi.'; }
}
async function deleteEvent() {
    if (!await askConfirm({ title: `Hapus acara “${event.value.title}”?`, message: 'Acara dan seluruh agendanya akan dihapus. Kebutuhan dan tugas tetap tersimpan dan menjadi Umum.', confirmLabel: 'Hapus acara', destructive: true })) return;
    try {
        await axios.delete(`/api/workspaces/${props.workspaceId}/events/${event.value.id}`);
        await router.replace('/app/events');
    } catch (failure) { error.value = failure.response?.data?.message ?? 'Acara belum berhasil dihapus. Coba lagi.'; }
}

onMounted(() => {
    load();
});
watch(eventId, () => load());
</script>

<template>
    <section class="event-detail-page" aria-labelledby="event-detail-title">
        <div v-if="loading" class="home-skeleton"><div class="skeleton-line skeleton-line--title"></div><div class="skeleton-block"></div></div>
        <div v-else-if="error && !event" class="page-error" role="alert"><p>{{ error }}</p><div><button class="button button--quiet" @click="load">Coba lagi</button><RouterLink class="button button--primary" to="/app/events">Kembali ke acara</RouterLink></div></div>
        <template v-else-if="event">
            <RouterLink class="event-detail-back" to="/app/events"><FaIcon :icon="['fas','arrow-left']" /> Semua acara</RouterLink>
            <RecordDetailHero v-if="!isEditing" id="event-detail-title" heading-tag="h1" :eyebrow="eventType(event.event_type)" :title="event.title" :subtitle="`${dateLabel(event.event_date)}${event.start_time ? ` · ${timeLabel(event.start_time)}${event.end_time ? `–${timeLabel(event.end_time)}` : ''} ${event.timezone || timezone}` : ''}`">
                <template #actions><RouterLink class="button button--quiet" :to="`/app/events/${event.id}/edit`"><FaIcon :icon="['fas','pen']" /> Ubah acara</RouterLink><button class="button button--primary" @click="createAgenda"><FaIcon :icon="['fas','plus']" /> Tambah agenda</button></template>
            </RecordDetailHero>
            <template v-if="isEditing">
                <RecordDetailHero id="event-detail-title" heading-tag="h1" eyebrow="INFORMASI ACARA" title="Ubah acara" subtitle="Perbarui tanggal, lokasi, dan detail hari istimewa ini." />
                <form class="event-edit-form" @submit.prevent="saveEvent">
                    <label>Nama acara<input v-model.trim="eventForm.title" required maxlength="160" /></label>
                    <div class="event-edit-grid"><label>Jenis acara<MotionSelect v-model="eventForm.event_type"><option value="engagement">Lamaran</option><option value="ceremony">Akad / Pemberkatan</option><option value="reception">Resepsi</option><option value="family">Acara keluarga</option><option value="other">Acara lainnya</option></MotionSelect></label><label>Tanggal<input v-model="eventForm.event_date" type="date" /></label><label>Mulai<input v-model="eventForm.start_time" type="time" /></label><label>Selesai<input v-model="eventForm.end_time" type="time" /></label></div>
                    <label>Nama lokasi<input v-model.trim="eventForm.venue_name" maxlength="160" placeholder="Nama gedung atau tempat" /></label>
                    <label>Alamat<textarea v-model.trim="eventForm.address" maxlength="500" rows="3" placeholder="Alamat lengkap" /></label>
                    <label>Catatan<textarea v-model.trim="eventForm.notes" maxlength="3000" rows="4" /></label>
                    <p v-if="formError" class="guest-error" role="alert">{{ formError }}</p>
                    <footer><button class="button button--quiet" type="button" @click="cancelEditing">Batal</button><button class="button button--primary" type="submit" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Simpan perubahan' }}</button></footer>
                </form>
            </template>
            <template v-else>
                <div v-if="error" class="guest-error" role="alert">{{ error }}</div>
                <section class="event-facts"><article><span>TANGGAL</span><strong>{{ dateLabel(event.event_date) }}</strong></article><article><span>WAKTU</span><strong>{{ timeLabel(event.start_time) }}<template v-if="event.end_time"> – {{ timeLabel(event.end_time) }}</template></strong><small>{{ event.timezone || timezone }}</small></article><article><span>LOKASI</span><strong>{{ event.venue_name || 'Belum ditentukan' }}</strong><small>{{ event.address || 'Alamat belum dicatat' }}</small></article></section>
                <section class="event-notes"><p class="eyebrow">CATATAN ACARA</p><p>{{ event.notes || 'Belum ada catatan untuk acara ini.' }}</p></section>
                <section class="event-preparation-section" aria-labelledby="event-preparation-title">
                    <header><div><p class="eyebrow">CHECKLIST ACARA</p><h2 id="event-preparation-title">Persiapan {{ event.title }}</h2><p>Tugas yang khusus disiapkan untuk acara ini.</p></div><RouterLink class="button button--quiet" :to="{ path: '/app/tasks', query: { event_id: String(event.id) } }">Lihat checklist</RouterLink></header>
                    <div v-if="!event.tasks_summary?.total" class="event-needs-empty"><span><FaIcon :icon="['fas','list-check']" /></span><div><strong>Belum ada tugas persiapan</strong><p>Tugas umum tidak dihitung ke progres acara ini.</p></div><RouterLink class="button button--primary" :to="{ path: '/app/tasks', query: { event_id: String(event.id), create: '1' } }">Tambah tugas pertama</RouterLink></div>
                    <div v-else class="event-preparation-card"><div class="event-preparation-progress"><span>{{ event.tasks_summary.completed }} dari {{ event.tasks_summary.total }} tugas selesai</span><strong>{{ Math.round(event.tasks_summary.completed / event.tasks_summary.total * 100) }}%</strong></div><div class="event-preparation-track"><span :style="{ width: `${Math.round(event.tasks_summary.completed / event.tasks_summary.total * 100)}%` }"></span></div><small>{{ event.tasks_summary.open }} tugas masih perlu disiapkan</small></div>
                </section>
                <section class="event-needs-section" aria-labelledby="event-needs-title">
                    <header><div><p class="eyebrow">KEBUTUHAN ACARA</p><h2 id="event-needs-title">Kebutuhan untuk acara ini</h2><p>Barang dan jasa yang ditautkan ke {{ event.title }}.</p></div><RouterLink class="button button--primary" :to="{ path: '/app/needs', query: { event_id: String(event.id), create: '1' } }"><FaIcon :icon="['fas','plus']" /> Tambah kebutuhan</RouterLink></header>
                    <div v-if="!event.needs_summary?.total" class="event-needs-empty"><span><FaIcon :icon="['fas','leaf']" /></span><div><strong>Belum ada kebutuhan untuk acara ini</strong><p>Kebutuhan umum dan acara lain tetap dikelola terpisah.</p></div><RouterLink class="button button--quiet" :to="{ path: '/app/needs', query: { event_id: String(event.id), create: '1' } }">Tambah kebutuhan pertama</RouterLink></div>
                    <template v-else>
                        <div class="event-needs-summary"><article><span>JUMLAH ITEM</span><strong>{{ event.needs_summary.total }}</strong><small>{{ event.needs_summary.open }} belum dibeli · {{ event.needs_summary.purchased }} sudah dibeli</small></article><article><span>ESTIMASI BELUM DIBELI</span><strong>{{ formatRupiah(event.needs_summary.estimated) }}</strong><small>Rencana kebutuhan acara</small></article><article><span>REALISASI PEMBELIAN</span><strong>{{ formatRupiah(event.needs_summary.actual) }}</strong><small>Dicatat terpisah dari transaksi</small></article></div>
                        <ul class="event-needs-list"><li v-for="need in event.needs" :key="need.id"><div><strong>{{ need.title }}</strong><small>{{ need.need_category?.name || 'Tanpa kategori' }}</small></div><span>{{ need.status === 'purchased' ? 'Sudah dibeli' : 'Direncanakan' }}</span><strong>{{ formatRupiah(need.status === 'purchased' ? need.purchased_amount : need.estimated_amount) }}</strong></li></ul>
                    </template>
                </section>
                <section class="event-agenda-section"><header><div><p class="eyebrow">RANGKAIAN HARI ISTIMEWA</p><h2>Agenda acara</h2><p>Atur urutan dan waktu setiap momen.</p></div><button class="button button--quiet" @click="createAgenda"><FaIcon :icon="['fas','plus']" /> Tambah agenda</button></header>
                    <div v-if="!event.agenda_items?.length" class="event-agenda-empty"><span><FaIcon :icon="['fas','calendar-days']" /></span><div><strong>Belum ada agenda</strong><p>Tambahkan rangkaian acara, seperti kedatangan keluarga atau sambutan.</p></div><button class="button button--primary" @click="createAgenda">Tambah agenda pertama</button></div>
                    <ol v-else class="event-agenda-list"><li v-for="item in event.agenda_items" :key="item.id"><time>{{ timeLabel(item.start_time) }}<template v-if="item.end_time">–{{ timeLabel(item.end_time) }}</template></time><div><strong>{{ item.title }}</strong><small>{{ item.location || 'Lokasi belum ditentukan' }}</small><p v-if="item.notes">{{ item.notes }}</p></div><div class="event-agenda-row-actions"><button class="icon-button" :aria-label="`Ubah agenda ${item.title}`" @click="editAgenda(item)"><FaIcon :icon="['fas','pen']" /></button><button class="icon-button event-delete" :aria-label="`Hapus agenda ${item.title}`" @click="deleteAgenda(item)"><FaIcon :icon="['fas','trash']" /></button></div></li></ol>
                </section>
                <footer class="event-detail-page-footer"><button class="button button--quiet event-delete" @click="deleteEvent">Hapus acara</button><RouterLink class="button button--quiet" :to="`/app/events/${event.id}/edit`">Ubah acara</RouterLink></footer>
            </template>
        </template>
        <Transition name="overlay" appear>
        <div v-if="agendaOpen" class="sheet-backdrop" @click.self="agendaGuard.requestClose()"><aside class="guest-sheet event-agenda-dialog" role="dialog" aria-modal="true" aria-labelledby="agenda-dialog-title"><header><div><p class="eyebrow">RANGKAIAN ACARA</p><h2 id="agenda-dialog-title">{{ agendaEditId ? 'Ubah agenda' : 'Tambah agenda' }}</h2></div><button class="icon-button" aria-label="Tutup agenda" @click="agendaGuard.requestClose()"><FaIcon :icon="['fas','xmark']" /></button></header><form @submit.prevent="saveAgenda"><label>Nama agenda<input v-model.trim="agendaForm.title" required maxlength="160" placeholder="Contoh: Prosesi akad" /></label><div class="guest-form-row"><label>Mulai<input v-model="agendaForm.start_time" type="time" /></label><label>Selesai<input v-model="agendaForm.end_time" type="time" /></label></div><label>Lokasi<input v-model.trim="agendaForm.location" maxlength="160" /></label><label>Catatan<textarea v-model.trim="agendaForm.notes" maxlength="1500" rows="3" /></label><p v-if="agendaError" class="guest-error" role="alert">{{ agendaError }}</p><footer><button class="button button--quiet" type="button" @click="agendaGuard.requestClose()">Batal</button><button class="button button--primary" type="submit" :disabled="agendaSaving">{{ agendaSaving ? 'Menyimpan…' : 'Simpan agenda' }}</button></footer></form></aside></div>
        </Transition>
        <ConfirmDialog :open="confirmOpen" :title="confirmOptions.title" :message="confirmOptions.message" :confirm-label="confirmOptions.confirmLabel" :destructive="confirmOptions.destructive" @cancel="finishConfirm(false)" @confirm="finishConfirm(true)" />
    </section>
</template>

<style scoped>
.event-detail-page{max-width:1040px;margin:auto;padding:35px clamp(18px,4vw,52px) 70px;color:#30362e}.event-detail-back{display:inline-flex;align-items:center;gap:8px;margin-bottom:22px;color:#65715f;text-decoration:none;font-size:12px}.event-detail-header{display:flex;justify-content:space-between;align-items:flex-end;gap:24px;margin-bottom:28px}.event-detail-header h1{font:500 clamp(40px,5vw,56px)/1.05 var(--font-display);margin:12px 0 9px}.event-detail-header>div>p:last-child{color:#5f675c;font-size:13px}.event-detail-actions{display:flex;gap:9px;flex-wrap:wrap}.event-facts{display:grid;grid-template-columns:1fr 1fr 1.4fr;gap:12px}.event-facts article{min-width:0;min-height:124px;padding:18px;background:#fff;border:1px solid #e6e6df;border-radius:13px;display:flex;flex-direction:column;gap:8px}.event-facts article>span{font-size:9px;letter-spacing:1px;color:#626b60;font-weight:700}.event-facts article>strong{font:500 20px/1.25 var(--font-display);overflow-wrap:anywhere}.event-facts article>small{font-size:11px;line-height:1.5;color:#7b8177}.event-notes{margin:14px 0 34px;padding:20px 22px;background:#edf0e9;border-radius:13px}.event-notes .eyebrow{margin-bottom:9px}.event-notes>p:last-child{margin:0;color:#626b5d;font-size:13px;line-height:1.7;white-space:pre-wrap}.event-needs-section{margin:0 0 34px}.event-needs-section>header{display:flex;justify-content:space-between;align-items:flex-end;gap:14px;margin-bottom:15px}.event-needs-section>header h2{font:500 30px var(--font-display);margin:5px 0}.event-needs-section>header p:last-child{font-size:12px;color:#5f675c;margin:0}.event-needs-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:11px;margin-bottom:12px}.event-needs-summary article{background:#fff;border:1px solid #e6e6df;border-radius:12px;padding:16px;display:flex;flex-direction:column;gap:5px}.event-needs-summary article span{font-size:9px;letter-spacing:.8px;color:#626b60;font-weight:700}.event-needs-summary article strong{font:500 23px var(--font-display);color:#46563f}.event-needs-summary article small{font-size:10px;color:#737a70}.event-needs-empty{display:flex;align-items:center;gap:13px;padding:18px;background:#fff;border:1px dashed #d9ddd3;border-radius:13px}.event-needs-empty>span{flex:0 0 40px;width:40px;height:40px;border-radius:50%;display:grid;place-items:center;background:#eff2eb;color:#536a54}.event-needs-empty>div{flex:1}.event-needs-empty strong{font:500 19px var(--font-display)}.event-needs-empty p{font-size:11px;color:#777e73;margin:4px 0 0}.event-needs-list{list-style:none;margin:0;padding:0;border-top:1px solid #e7e8e1}.event-needs-list li{display:grid;grid-template-columns:minmax(0,1fr) 120px auto;gap:12px;align-items:center;padding:13px 15px;background:#fff;border-bottom:1px solid #e7e8e1}.event-needs-list li>div{display:flex;flex-direction:column;gap:4px}.event-needs-list li strong{font-size:12px}.event-needs-list li small,.event-needs-list li>span{font-size:10px;color:#70786c}.event-needs-list li>strong{font-size:12px;color:#30362e}.event-agenda-section>header{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;margin-bottom:16px}.event-agenda-section h2{font:500 30px var(--font-display);margin:5px 0}.event-agenda-section>header p:last-child{font-size:12px;color:#5f675c;margin:0}.event-agenda-empty{display:flex;align-items:center;gap:14px;padding:22px;background:#fff;border:1px dashed #d9ddd3;border-radius:13px}.event-agenda-empty>span{flex:0 0 42px;width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:#eff2eb;color:#536a54}.event-agenda-empty>div{flex:1}.event-agenda-empty strong{font:500 20px var(--font-display)}.event-agenda-empty p{font-size:11px;line-height:1.55;color:#777e73;margin:5px 0 0}.event-agenda-list{list-style:none;margin:0;padding:0;border-top:1px solid #e7e8e1}.event-agenda-list li{display:grid;grid-template-columns:110px minmax(0,1fr) auto;align-items:start;gap:16px;padding:17px 14px;border-bottom:1px solid #e7e8e1;background:#fff}.event-agenda-list time{font-size:12px;font-weight:700;color:#56694f;padding-top:3px}.event-agenda-list li>div:nth-child(2){display:flex;flex-direction:column;gap:5px;min-width:0}.event-agenda-list strong{font:500 20px var(--font-display)}.event-agenda-list small{font-size:11px;color:#5f675c}.event-agenda-list p{font-size:11px;line-height:1.5;color:#5f675c;margin:4px 0 0;white-space:pre-wrap}.event-agenda-row-actions{display:flex;gap:4px}.event-detail-page-footer{display:flex;justify-content:flex-end;gap:9px;margin-top:28px;padding-top:18px;border-top:1px solid #e6e6df}.event-delete{color:#9c4942}.event-edit-form{max-width:760px;padding:24px;background:#fff;border:1px solid #e6e6df;border-radius:15px;display:flex;flex-direction:column;gap:17px}.event-edit-form label,.event-agenda-dialog form label{display:flex;flex-direction:column;gap:7px;font-size:12px;font-weight:600;color:#4c5348}.event-edit-form input,.event-edit-form select,.event-edit-form textarea,.event-agenda-dialog input,.event-agenda-dialog textarea{width:100%;padding:11px;border:1px solid #dedfd7;border-radius:8px;font:13px var(--font-body);background:#fff}.event-edit-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.event-edit-form footer,.event-agenda-dialog form footer{display:flex;justify-content:flex-end;gap:9px;margin-top:4px}.event-agenda-dialog form{display:flex;flex-direction:column;gap:14px}.event-agenda-dialog .guest-form-row>label{min-width:0}.event-agenda-dialog form input,.event-agenda-dialog form textarea{box-sizing:border-box}
@media(max-width:760px){.event-detail-page{padding:24px 16px 48px}.event-detail-header{align-items:flex-start;flex-direction:column}.event-detail-actions{width:100%}.event-facts{grid-template-columns:1fr 1fr}.event-facts article:last-child{grid-column:1/-1}.event-agenda-section>header,.event-needs-section>header{align-items:flex-start;flex-direction:column}.event-needs-summary{grid-template-columns:1fr}.event-needs-empty{align-items:flex-start;flex-wrap:wrap}.event-needs-empty>div{min-width:calc(100% - 56px)}.event-needs-empty>.button{width:100%}.event-needs-list li{grid-template-columns:minmax(0,1fr) auto;gap:5px 10px}.event-needs-list li>div{grid-column:1}.event-needs-list li>span{grid-column:1}.event-needs-list li>strong{grid-column:2;grid-row:1/3}.event-agenda-empty{align-items:flex-start;flex-wrap:wrap}.event-agenda-empty>div{min-width:calc(100% - 60px)}.event-agenda-list li{grid-template-columns:1fr auto;gap:6px 10px;padding:15px 9px}.event-agenda-list time{grid-column:1}.event-agenda-list li>div:nth-child(2){grid-column:1}.event-agenda-row-actions{grid-row:1/3;grid-column:2}.event-edit-form{padding:16px}.event-detail-page-footer{flex-wrap:wrap}}
.event-preparation-section{margin:0 0 34px}.event-preparation-section>header{display:flex;justify-content:space-between;align-items:flex-end;gap:14px;margin-bottom:15px}.event-preparation-section h2{font:500 30px var(--font-display);margin:5px 0}.event-preparation-section>header p:last-child{font-size:12px;color:#5f675c;margin:0}.event-preparation-card{background:#fff;border:1px solid #e6e6df;border-radius:13px;padding:19px 21px}.event-preparation-progress{display:flex;justify-content:space-between;align-items:center;font-size:12px;font-weight:600;color:#475440}.event-preparation-progress strong{font:500 24px var(--font-display)}.event-preparation-track{height:8px;margin:12px 0 8px;border-radius:99px;background:#eaede6;overflow:hidden}.event-preparation-track span{display:block;height:100%;border-radius:inherit;background:#45613f}.event-preparation-card>small{color:#70786c;font-size:11px}
@media(max-width:760px){.event-preparation-section>header{align-items:flex-start;flex-direction:column}}
@media(max-width:430px){.event-edit-grid{grid-template-columns:1fr}.event-facts{grid-template-columns:1fr}.event-facts article:last-child{grid-column:auto}.event-agenda-empty>.button{width:100%}}
</style>
