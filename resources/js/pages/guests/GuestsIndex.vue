<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';

const props = defineProps({ workspaceId: { type: [String, Number], required: true } });
const guests = ref([]);
const summary = ref({ total: 0, attending: 0, pending: 0, declined: 0 });
const loading = ref(true);
const error = ref('');
const saving = ref(false);
const showForm = ref(false);
const selected = ref(null);
const editId = ref(null);
const filters = reactive({ q: '', side: '', rsvp_status: '' });
const form = reactive(blankForm());
const formErrors = ref({});
const searchTimer = ref(null);
const categories = computed(() => [...new Set(guests.value.map((guest) => guest.group_name).filter(Boolean))].sort());

function blankForm() { return { name: '', email: '', phone: '', group_name: '', side: 'shared', invitation_status: 'not_sent', rsvp_status: 'pending', plus_ones: 0, notes: '' }; }
function sideName(side) { return { bride: 'Pihak pertama', groom: 'Pihak kedua', shared: 'Bersama' }[side] ?? side; }
function rsvpName(status) { return { pending: 'Menunggu', attending: 'Hadir', declined: 'Tidak hadir' }[status] ?? status; }
function clearForm() { Object.assign(form, blankForm()); formErrors.value = {}; }
function openCreate() { selected.value = null; editId.value = null; clearForm(); showForm.value = true; }
function openEdit(guest) { selected.value = null; editId.value = guest.id; Object.assign(form, { ...blankForm(), ...guest, email: guest.email ?? '', phone: guest.phone ?? '', group_name: guest.group_name ?? '', notes: guest.notes ?? '' }); formErrors.value = {}; showForm.value = true; }

async function load() {
    loading.value = true; error.value = '';
    try {
        const { data } = await axios.get(`/api/workspaces/${props.workspaceId}/guests`, { params: Object.fromEntries(Object.entries(filters).filter(([, value]) => value)) });
        guests.value = data.data; summary.value = data.meta.summary;
    } catch { error.value = 'Daftar tamu belum berhasil dimuat. Coba lagi.'; }
    finally { loading.value = false; }
}

function searchChanged() { window.clearTimeout(searchTimer.value); searchTimer.value = window.setTimeout(load, 250); }
watch(() => [filters.side, filters.rsvp_status], load);

async function save() {
    saving.value = true; formErrors.value = {};
    const payload = { ...form, email: form.email.trim() || null, phone: form.phone.trim() || null, group_name: form.group_name.trim() || null, notes: form.notes.trim() || null, plus_ones: Number(form.plus_ones) };
    try {
        if (editId.value) await axios.patch(`/api/guests/${editId.value}`, payload);
        else await axios.post(`/api/workspaces/${props.workspaceId}/guests`, payload);
        showForm.value = false; await load();
    } catch (failure) { formErrors.value = failure.response?.data?.errors ?? { form: [failure.response?.data?.message ?? 'Tamu belum berhasil disimpan. Coba lagi.'] }; }
    finally { saving.value = false; }
}

async function openDetail(guest) {
    try { selected.value = (await axios.get(`/api/guests/${guest.id}`)).data.guest; }
    catch { selected.value = guest; }
}

async function remove(guest) {
    if (!window.confirm(`Hapus ${guest.name} dari daftar tamu?`)) return;
    try { await axios.delete(`/api/guests/${guest.id}`); selected.value = null; await load(); }
    catch { error.value = 'Tamu belum berhasil dihapus. Coba lagi.'; }
}

async function updateRsvp(guest, rsvp_status) {
    try { await axios.patch(`/api/guests/${guest.id}`, { rsvp_status }); await load(); if (selected.value?.id === guest.id) selected.value = { ...selected.value, rsvp_status }; }
    catch { error.value = 'Status RSVP belum berhasil diperbarui.'; }
}

onMounted(load);
</script>

<template>
    <section class="guests-page" aria-labelledby="guests-title">
        <div class="page-breadcrumb"><span>Persiapan pernikahan</span><span>/</span><span>Tamu</span></div>
        <header class="guests-header"><div><p class="eyebrow"><span class="eyebrow__dot"></span> DAFTAR ORANG TERSAYANG</p><h1 id="guests-title">Tamu undangan</h1><p>Atur daftar tamu dan pantau konfirmasi kehadiran kalian.</p></div><button class="button button--primary" type="button" @click="openCreate"><FaIcon :icon="['fas', 'plus']" /> Tambah tamu</button></header>

        <div v-if="loading && !guests.length" class="home-skeleton" aria-label="Memuat tamu"><div class="skeleton-line skeleton-line--short"></div><div class="skeleton-line skeleton-line--title"></div><div class="skeleton-block"></div></div>
        <div v-else-if="error && !summary.total" class="page-error" role="alert"><p>{{ error }}</p><button class="button button--quiet" @click="load">Coba lagi</button></div>
        <template v-else>
            <section class="guest-metrics" aria-label="Ringkasan tamu"><article><span>DAFTAR TAMU</span><strong>{{ summary.total }}</strong><small>orang tercatat</small></article><article><span>AKAN HADIR</span><strong>{{ summary.attending }}</strong><small>termasuk pendamping</small></article><article><span>MENUNGGU RSVP</span><strong>{{ summary.pending }}</strong><small>belum memberi jawaban</small></article><article><span>TIDAK HADIR</span><strong>{{ summary.declined }}</strong><small>telah mengabari</small></article></section>
            <div v-if="error" class="guest-error" role="alert">{{ error }}</div>
            <section class="guest-table-card">
                <div class="guest-toolbar"><label class="guest-search"><FaIcon :icon="['fas', 'magnifying-glass']" /><span class="sr-only">Cari tamu</span><input v-model.trim="filters.q" placeholder="Cari nama, email, atau kelompok" @input="searchChanged"></label><select v-model="filters.side" aria-label="Filter pihak"><option value="">Semua pihak</option><option value="bride">Pihak pertama</option><option value="groom">Pihak kedua</option><option value="shared">Bersama</option></select><select v-model="filters.rsvp_status" aria-label="Filter RSVP"><option value="">Semua RSVP</option><option value="pending">Menunggu</option><option value="attending">Hadir</option><option value="declined">Tidak hadir</option></select><button class="filter-reset" type="button" aria-label="Bersihkan filter" @click="Object.assign(filters, { q: '', side: '', rsvp_status: '' }); load()"><FaIcon :icon="['fas', 'rotate']" /></button></div>
                <div v-if="loading" class="guest-loading">Memuat daftar tamu…</div>
                <div v-else-if="!guests.length" class="guest-empty"><span class="guest-empty__mark"><FaIcon :icon="['fas', 'people-group']" /></span><h2>{{ filters.q || filters.side || filters.rsvp_status ? 'Tidak ada tamu yang cocok.' : 'Mulai susun daftar tamu kalian.' }}</h2><p>{{ filters.q || filters.side || filters.rsvp_status ? 'Coba ubah kata pencarian atau filter.' : 'Simpan nama dan kelompok tamu di satu tempat. Kalian bisa mengundang mereka nanti.' }}</p><button v-if="!filters.q && !filters.side && !filters.rsvp_status" class="button button--primary" @click="openCreate"><FaIcon :icon="['fas', 'plus']" /> Tambah tamu pertama</button></div>
                <div v-else class="guest-list"><div class="guest-list__head"><span>Nama tamu</span><span>Kelompok</span><span>Pihak</span><span>Status RSVP</span><span></span></div><article v-for="guest in guests" :key="guest.id" class="guest-row"><button class="guest-row__identity" @click="openDetail(guest)"><span class="guest-avatar">{{ guest.name.slice(0,1).toUpperCase() }}</span><span><strong>{{ guest.name }}</strong><small>{{ guest.email || guest.phone || 'Belum ada kontak' }}</small></span></button><span class="guest-group">{{ guest.group_name || '—' }}</span><span class="guest-side">{{ sideName(guest.side) }}</span><span class="guest-status" :class="`guest-status--${guest.rsvp_status}`"><i></i>{{ rsvpName(guest.rsvp_status) }}</span><button class="guest-row__more" :aria-label="`Lihat ${guest.name}`" @click="openDetail(guest)"><FaIcon :icon="['fas', 'chevron-right']" /></button></article></div>
                <footer v-if="guests.length" class="guest-list-footer">Menampilkan {{ guests.length }} tamu <span>Impor CSV akan tersedia pada langkah modul berikutnya.</span></footer>
            </section>
        </template>

        <div v-if="showForm" class="sheet-backdrop" @click.self="showForm = false"><aside class="guest-sheet" role="dialog" aria-modal="true" aria-labelledby="guest-form-title"><header><div><p class="eyebrow">DAFTAR TAMU</p><h2 id="guest-form-title">{{ editId ? 'Ubah tamu' : 'Tambah tamu' }}</h2></div><button class="icon-button" aria-label="Tutup form" @click="showForm = false"><FaIcon :icon="['fas', 'xmark']" /></button></header><form @submit.prevent="save"><label>Nama lengkap<input v-model.trim="form.name" maxlength="160" required placeholder="Nama tamu" /><small v-if="formErrors.name">{{ formErrors.name[0] }}</small></label><div class="guest-form-row"><label>Email <em>Opsional</em><input v-model.trim="form.email" type="email" placeholder="nama@email.com" /><small v-if="formErrors.email">{{ formErrors.email[0] }}</small></label><label>Nomor telepon <em>Opsional</em><input v-model.trim="form.phone" type="tel" placeholder="08xx xxxx xxxx" /></label></div><div class="guest-form-row"><label>Kelompok <em>Opsional</em><input v-model.trim="form.group_name" placeholder="Keluarga, teman, rekan kerja" /></label><label>Dari pihak<select v-model="form.side"><option value="shared">Bersama</option><option value="bride">Pihak pertama</option><option value="groom">Pihak kedua</option></select></label></div><div class="guest-form-row"><label>Status RSVP<select v-model="form.rsvp_status"><option value="pending">Menunggu</option><option value="attending">Hadir</option><option value="declined">Tidak hadir</option></select></label><label>Jumlah pendamping<input v-model.number="form.plus_ones" type="number" min="0" max="10" /></label></div><label>Catatan <em>Opsional</em><textarea v-model.trim="form.notes" rows="3" placeholder="Catatan untuk tim kalian"></textarea></label><p v-if="formErrors.form" class="guest-error" role="alert">{{ formErrors.form[0] }}</p><footer><button class="button button--quiet" type="button" @click="showForm = false">Batal</button><button class="button button--primary" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Simpan tamu' }}</button></footer></form></aside></div>

        <div v-if="selected" class="sheet-backdrop" @click.self="selected = null"><aside class="guest-sheet" role="dialog" aria-modal="true" aria-labelledby="guest-detail-title"><header><div><p class="eyebrow">DETAIL TAMU · {{ rsvpName(selected.rsvp_status).toUpperCase() }}</p><h2 id="guest-detail-title">{{ selected.name }}</h2></div><button class="icon-button" aria-label="Tutup detail" @click="selected = null"><FaIcon :icon="['fas', 'xmark']" /></button></header><dl class="guest-detail-list"><div><dt>Kelompok</dt><dd>{{ selected.group_name || 'Belum dikelompokkan' }}</dd></div><div><dt>Pihak</dt><dd>{{ sideName(selected.side) }}</dd></div><div><dt>Email</dt><dd>{{ selected.email || 'Belum dicatat' }}</dd></div><div><dt>Nomor telepon</dt><dd>{{ selected.phone || 'Belum dicatat' }}</dd></div><div><dt>Jumlah hadir</dt><dd>{{ 1 + Number(selected.plus_ones || 0) }} orang</dd></div><div><dt>Undangan</dt><dd>{{ selected.invitation_status === 'sent' ? 'Terkirim' : 'Belum dikirim' }}</dd></div></dl><p class="guest-detail-notes">{{ selected.notes || 'Belum ada catatan untuk tamu ini.' }}</p><div class="guest-detail-actions"><button class="button button--quiet guest-delete" @click="remove(selected)">Hapus</button><button class="button button--quiet" @click="openEdit(selected)">Ubah data</button><button v-if="selected.rsvp_status !== 'attending'" class="button button--primary" @click="updateRsvp(selected, 'attending')">Tandai hadir</button></div></aside></div>
    </section>
</template>

<style scoped>
.guests-page{max-width:1230px;margin:auto;padding:38px clamp(20px,4vw,56px) 72px;color:#30362e}.guests-header{display:flex;justify-content:space-between;align-items:flex-end;gap:24px;margin:18px 0 28px}.guests-header h1{font:500 clamp(42px,5vw,58px)/1 var(--font-display);letter-spacing:-1px;margin:15px 0 9px}.guests-header p:last-child{font-size:14px;color:#747a70;margin:0}.guest-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}.guest-metrics article{background:#fff;border:1px solid #e7e6df;border-radius:15px;padding:17px 20px;display:flex;flex-direction:column;gap:5px}.guest-metrics article span{font-size:10px;letter-spacing:1.1px;color:#777e72;font-weight:700}.guest-metrics strong{font:500 30px/1.1 var(--font-display);color:#46563f}.guest-metrics small{font-size:11px;color:#82877f}.guest-table-card{background:#fff;border:1px solid #e7e6df;border-radius:17px;overflow:hidden}.guest-toolbar{padding:16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #eeeee8}.guest-search{flex:1;min-width:190px;display:flex;align-items:center;gap:9px;padding:0 12px;border:1px solid #e4e5de;border-radius:9px;color:#7c8278}.guest-search input{height:40px;border:0;outline:0;flex:1;min-width:0;font:13px inherit;color:#30362e}.guest-toolbar select{height:40px;max-width:190px;border:1px solid #e4e5de;border-radius:9px;padding:0 12px;background:#fff;color:#565d53;font-size:12px}.guest-empty{text-align:center;padding:58px 24px}.guest-empty__mark{width:52px;height:52px;border-radius:50%;background:#eff2eb;color:#536a54;display:grid;place-items:center;margin:0 auto 16px}.guest-empty h2{font:500 30px var(--font-display);margin:0 0 8px}.guest-empty p{font-size:13px;color:#777d73;max-width:460px;line-height:1.6;margin:0 auto 20px}.guest-list__head,.guest-row{display:grid;grid-template-columns:minmax(240px,1.7fr) 1fr .85fr 1fr 30px;gap:16px;align-items:center;padding:12px 20px}.guest-list__head{font-size:10px;letter-spacing:.9px;text-transform:uppercase;font-weight:700;color:#7a8076;background:#fbfaf7;border-bottom:1px solid #ecece6}.guest-row{min-height:72px;border-bottom:1px solid #f0f0eb}.guest-row:last-child{border-bottom:0}.guest-row__identity{display:flex;align-items:center;gap:11px;text-align:left;border:0;background:none;padding:0;cursor:pointer;color:inherit;min-width:0}.guest-avatar{width:37px;height:37px;border-radius:50%;display:grid;place-items:center;background:#eff2eb;color:#536a54;font:500 18px var(--font-display)}.guest-row__identity>span:last-child{min-width:0;display:flex;flex-direction:column;gap:4px}.guest-row__identity strong{font-size:13px}.guest-row__identity small{font-size:11px;color:#80867d;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.guest-group,.guest-side{font-size:12px;color:#656c61}.guest-status{display:inline-flex;align-items:center;gap:7px;font-size:11px;color:#6d7568}.guest-status i{width:7px;height:7px;border-radius:50%;background:#d4a34d}.guest-status--attending i{background:#5d8053}.guest-status--declined i{background:#ae6660}.guest-row__more{width:28px;height:28px;border:0;background:none;color:#7c8278;cursor:pointer}.guest-list-footer{padding:14px 20px;border-top:1px solid #eeeee8;display:flex;justify-content:space-between;font-size:11px;color:#777d73}.guest-list-footer span{color:#969a92}.guest-loading{padding:28px;text-align:center;color:#777;font-size:13px}.guest-error{padding:12px 14px;color:#9c3733;background:#fff0ee;border-radius:9px;font-size:13px;margin-bottom:12px}.guest-sheet{background:#fbfaf7;width:min(560px,100%);height:100%;overflow:auto;padding:30px clamp(20px,3vw,36px);box-shadow:-12px 0 50px #14171030}.guest-sheet>header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px solid #e7e5dd;padding-bottom:20px}.guest-sheet h2{font:500 34px var(--font-display);margin:6px 0}.guest-sheet form{padding-top:23px;display:flex;flex-direction:column;gap:16px}.guest-sheet label{font-size:12px;font-weight:600;color:#4c5348;display:flex;flex-direction:column;gap:7px}.guest-sheet label em{font-weight:400;color:#858a81;font-style:normal;font-size:10px}.guest-sheet input,.guest-sheet textarea,.guest-sheet select{font:13px inherit;color:#30362e;border:1px solid #dedfd7;background:#fff;border-radius:9px;padding:12px;outline:none}.guest-sheet input:focus,.guest-sheet textarea:focus,.guest-sheet select:focus{border-color:#74856c;box-shadow:0 0 0 3px #74856c1c}.guest-sheet textarea{resize:vertical}.guest-sheet label small{color:#a33c34}.guest-form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}.guest-sheet form footer{display:flex;justify-content:flex-end;gap:8px;margin-top:6px}.guest-detail-list{margin:24px 0}.guest-detail-list>div{padding:13px 0;border-bottom:1px solid #eaeae4;display:flex;justify-content:space-between;gap:14px;font-size:13px}.guest-detail-list dt{color:#767d72}.guest-detail-list dd{margin:0;text-align:right;font-weight:600}.guest-detail-notes{padding:14px;border-radius:10px;background:#f1f2eb;font-size:12px;line-height:1.7;color:#697064}.guest-detail-actions{display:flex;gap:8px;justify-content:flex-end;border-top:1px solid #e7e5dd;padding-top:18px;margin-top:20px}.guest-delete{margin-right:auto;color:#a33c34!important}
@media(max-width:780px){.guests-page{padding:26px 17px 54px}.guests-header{align-items:flex-start;flex-direction:column}.guests-header h1{font-size:46px}.guest-metrics{grid-template-columns:1fr 1fr;gap:9px}.guest-metrics article{padding:14px}.guest-toolbar{flex-wrap:wrap}.guest-search{flex-basis:100%}.guest-toolbar select{flex:1;max-width:none}.guest-list__head{display:none}.guest-row{grid-template-columns:minmax(0,1fr) auto 28px;gap:9px;padding:13px}.guest-group{display:none}.guest-row__identity{grid-column:1}.guest-side{display:none}.guest-status{grid-column:2;grid-row:1}.guest-row__more{grid-column:3;grid-row:1}.guest-list-footer{flex-direction:column;gap:6px}.guest-form-row{grid-template-columns:1fr}.guest-detail-actions{flex-wrap:wrap}.guest-detail-actions .button{font-size:11px;padding:10px}.guest-delete{width:100%;justify-content:flex-start}}
</style>
