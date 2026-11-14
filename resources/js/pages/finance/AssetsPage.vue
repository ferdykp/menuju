<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import MoneyInput from '../../components/MoneyInput.vue';
import PaginationControls from '../../components/PaginationControls.vue';
import { useConfirmDialog } from '../../composables/useConfirmDialog';
import { useUnsavedForm } from '../../composables/useUnsavedForm';
import { formatRupiah } from '../../utils/money';

const props = defineProps({ workspaceId: { type: [String, Number], required: true } });
const route = useRoute();
const router = useRouter();
const { open: confirmOpen, options: confirmOptions, confirm: askConfirm, finish: finishConfirm } = useConfirmDialog();
const rows = ref([]);
const needs = ref([]);
const pagination = ref(null);
const summary = ref({ count: 0, estimated_value: 0 });
const selected = ref(null);
const loading = ref(true);
const error = ref('');
const formError = ref('');
const saving = ref(false);
const editor = ref(false);
const allocator = ref(false);
const form = reactive(blankAsset());
const allocation = reactive({ need_id: '', quantity: '', notes: '' });
const savedForm = ref('');
const savedAllocation = ref('');
const editorDirty = computed(() => JSON.stringify(form) !== savedForm.value);
const allocationDirty = computed(() => JSON.stringify(allocation) !== savedAllocation.value);
const editorGuard = useUnsavedForm({ isOpen: editor, isDirty: editorDirty, confirm: askConfirm, onDiscard: () => { editor.value = false; }, title: 'Perubahan aset belum disimpan' });
const allocationGuard = useUnsavedForm({ isOpen: allocator, isDirty: allocationDirty, confirm: askConfirm, onDiscard: () => { allocator.value = false; }, title: 'Alokasi belum disimpan' });
const available = computed(() => Math.max(Number(selected.value?.quantity ?? 0) - Number(selected.value?.allocated_quantity ?? 0), 0));

function blankAsset() { return { name: '', asset_type: 'gold', owner_name: '', quantity: '1', unit: 'gram', estimated_value: '', valuation_date: new Date().toLocaleDateString('en-CA'), acquired_at: '', status: 'available', notes: '' }; }
function dateLabel(value) { return value ? new Date(`${String(value).slice(0, 10)}T12:00:00Z`).toLocaleDateString('id-ID', { timeZone: 'UTC', day: 'numeric', month: 'long', year: 'numeric' }) : 'Belum dinilai'; }
function typeLabel(type) { return { gold: 'Emas', jewelry: 'Perhiasan', investment: 'Investasi', other: 'Lainnya' }[type] ?? 'Lainnya'; }

async function load(page = 1) {
    loading.value = true; error.value = '';
    try {
        const { data } = await axios.get(`/api/workspaces/${props.workspaceId}/assets`, { params: { page, per_page: 25 } });
        rows.value = data.assets; needs.value = data.needs; pagination.value = data.pagination; summary.value = data.summary;
        if (route.params.id) await openAsset(route.params.id, false);
    } catch { error.value = 'Daftar aset belum berhasil dimuat. Coba lagi.'; }
    finally { loading.value = false; }
}

async function openAsset(id, navigate = true) {
    try {
        const { data } = await axios.get(`/api/assets/${id}`); selected.value = data.asset;
        if (navigate) router.push(`/app/assets/${id}`);
    } catch { error.value = 'Detail aset belum berhasil dimuat.'; }
}

function openCreate() { selected.value = null; Object.assign(form, blankAsset()); formError.value = ''; savedForm.value = JSON.stringify(form); editor.value = true; }
function openEdit(asset) { selected.value = asset; Object.assign(form, { ...blankAsset(), ...asset, quantity: String(asset.quantity), estimated_value: asset.estimated_value ?? '' }); savedForm.value = JSON.stringify(form); formError.value = ''; editor.value = true; }

async function save() {
    saving.value = true; formError.value = '';
    const body = { name: form.name, asset_type: form.asset_type, owner_name: form.owner_name || null, quantity: Number(form.quantity), unit: form.unit, estimated_value: form.estimated_value === '' ? null : Number(form.estimated_value), valuation_date: form.valuation_date || null, acquired_at: form.acquired_at || null, status: form.status, notes: form.notes || null };
    try {
        if (selected.value && form.id) await axios.patch(`/api/assets/${selected.value.id}`, body);
        else await axios.post(`/api/workspaces/${props.workspaceId}/assets`, body);
        editor.value = false; await load();
        if (selected.value) await openAsset(selected.value.id, false);
    } catch (e) { formError.value = e.response?.data?.message ?? 'Aset belum tersimpan. Periksa data lalu coba lagi.'; }
    finally { saving.value = false; }
}

function edit(asset) { form.id = asset.id; openEdit(asset); }
async function closeEditor() { await editorGuard.requestClose(); }
async function closeAllocator() { await allocationGuard.requestClose(); }
async function removeAsset() {
    if (!selected.value) return;
    const confirmed = await askConfirm({ title: `Hapus “${selected.value.name}”?`, message: 'Aset yang belum dialokasikan akan dihapus dari daftar. Histori keuangan tunai tidak berubah.', confirmLabel: 'Hapus aset', destructive: true });
    if (!confirmed) return;
    try { await axios.delete(`/api/assets/${selected.value.id}`); selected.value = null; await router.push('/app/assets'); await load(); }
    catch (e) { formError.value = e.response?.data?.message ?? 'Aset belum dapat dihapus.'; }
}
function openAllocation() { Object.assign(allocation, { need_id: '', quantity: String(available.value || ''), notes: '' }); savedAllocation.value = JSON.stringify(allocation); formError.value = ''; allocator.value = true; }
async function allocate() {
    saving.value = true; formError.value = '';
    try {
        await axios.post(`/api/assets/${selected.value.id}/allocations`, { ...allocation, need_id: Number(allocation.need_id), quantity: Number(allocation.quantity) });
        allocator.value = false; await load(); await openAsset(selected.value.id, false);
    } catch (e) { formError.value = e.response?.data?.message ?? 'Alokasi belum tersimpan.'; }
    finally { saving.value = false; }
}
async function release(allocationId) { try { await axios.delete(`/api/assets/${selected.value.id}/allocations/${allocationId}`); await load(); await openAsset(selected.value.id, false); } catch { formError.value = 'Alokasi belum dapat dilepas.'; } }
function closeDetail() { selected.value = null; router.push('/app/assets'); }
onMounted(load);
</script>

<template>
    <section class="financial-records asset-page" aria-labelledby="asset-title">
        <div v-if="loading" class="page-loading" role="status">Memuat aset…</div>
        <div v-else-if="error" class="page-error" role="alert"><p>{{ error }}</p><button class="button button--quiet" @click="load()">Coba lagi</button></div>
        <template v-else>
            <header class="records-head"><div><span class="eyebrow">KEUANGAN · KEPEMILIKAN NON-TUNAI</span><h1 id="asset-title">Aset</h1><p>Catat aset yang disiapkan untuk kebutuhan pernikahan. Nilainya tidak menambah saldo kas.</p></div><button class="button button--primary" @click="openCreate">Tambah aset</button></header>
            <div class="records-summary"><article><span>Aset tercatat</span><strong>{{ summary.count }}</strong></article><article><span>Perkiraan nilai saat ini</span><strong>{{ formatRupiah(summary.estimated_value) }}</strong><small>Penilaian per tanggal yang tercatat di tiap aset</small></article></div>
            <div v-if="!rows.length" class="empty-state"><h2>Belum ada aset</h2><p>Tambahkan emas atau aset lain yang sudah dimiliki dan disiapkan untuk pernikahan.</p><button class="button button--primary" @click="openCreate">Tambah aset pertama</button></div>
            <div v-else class="record-list" aria-label="Daftar aset"><button v-for="asset in rows" :key="asset.id" class="transaction-row asset-row" @click="openAsset(asset.id)"><span class="transaction-icon"><FaIcon :icon="['fas', asset.asset_type === 'gold' || asset.asset_type === 'jewelry' ? 'gem' : 'box-archive']" aria-hidden="true" /></span><span class="transaction-copy"><strong>{{ asset.name }}</strong><small>{{ typeLabel(asset.asset_type) }} · {{ asset.quantity }} {{ asset.unit }}</small><small>{{ Number(asset.allocated_quantity) ? `${asset.allocated_quantity} ${asset.unit} dialokasikan` : 'Belum dialokasikan' }}</small></span><span class="asset-value"><strong>{{ formatRupiah(asset.estimated_value) }}</strong><small>Dinilai {{ dateLabel(asset.valuation_date) }}</small></span><FaIcon :icon="['fas','chevron-right']" /></button></div><PaginationControls v-if="pagination" :pagination="pagination" @change="load" />
        </template>

        <div v-if="selected" class="sheet-backdrop" @click.self="closeDetail"><aside class="guest-sheet finance-sheet" role="dialog" aria-modal="true" aria-labelledby="asset-detail-title"><header><div><span class="eyebrow">DETAIL ASET</span><h2 id="asset-detail-title">{{ selected.name }}</h2><p>{{ typeLabel(selected.asset_type) }} · {{ selected.owner_name || 'Pemilik belum dicatat' }}</p></div><button class="icon-button" aria-label="Tutup" @click="closeDetail">×</button></header><div class="asset-detail"><div class="records-summary"><article><span>Jumlah</span><strong>{{ selected.quantity }} {{ selected.unit }}</strong></article><article><span>Tersedia untuk alokasi</span><strong>{{ available }} {{ selected.unit }}</strong></article><article><span>Perkiraan nilai</span><strong>{{ formatRupiah(selected.estimated_value) }}</strong><small>Penilaian {{ dateLabel(selected.valuation_date) }}</small></article></div><p v-if="selected.notes">{{ selected.notes }}</p><div class="asset-actions"><button class="button button--quiet" @click="edit(selected)">Ubah aset</button><button class="button button--primary" :disabled="available <= 0 || !needs.length" @click="openAllocation">Alokasikan ke kebutuhan</button><button class="button button--quiet asset-delete" :disabled="selected.allocations?.length" @click="removeAsset">Hapus aset</button></div><p v-if="formError" class="form-error" role="alert">{{ formError }}</p><section class="asset-valuation-history"><h3>Riwayat penilaian</h3><article v-for="valuation in selected.valuations" :key="valuation.id"><time>{{ dateLabel(valuation.valuation_date) }}</time><strong>{{ formatRupiah(valuation.estimated_value) }}</strong></article><p v-if="!selected.valuations?.length">Belum ada riwayat nilai.</p></section><h3>Dialokasikan ke</h3><div v-if="selected.allocations?.length" class="asset-allocation-list"><article v-for="item in selected.allocations" :key="item.id"><span><strong>{{ item.need?.title ?? 'Kebutuhan dihapus' }}</strong><small>{{ item.quantity }} {{ selected.unit }}<template v-if="item.need?.event"> · {{ item.need.event.title }}</template></small></span><button class="text-link" @click="release(item.id)">Lepaskan</button></article></div><p v-else>Belum ada kebutuhan yang ditautkan.</p><p class="field-note">Aset yang dialokasikan tetap bukan saldo tunai dan tidak membuat transaksi.</p></div></aside></div>

        <div v-if="editor" class="sheet-backdrop" @click.self="closeEditor"><aside class="guest-sheet finance-sheet" role="dialog" aria-modal="true" aria-labelledby="asset-form-title"><header><div><span class="eyebrow">CATAT KEPEMILIKAN</span><h2 id="asset-form-title">{{ form.id ? 'Ubah aset' : 'Tambah aset' }}</h2></div><button class="icon-button" aria-label="Tutup" @click="closeEditor">×</button></header><form @submit.prevent="save"><label>Nama aset<input v-model.trim="form.name" required maxlength="160" placeholder="Emas untuk mahar"></label><div class="guest-form-row"><label>Jenis<select v-model="form.asset_type"><option value="gold">Emas</option><option value="jewelry">Perhiasan</option><option value="investment">Investasi</option><option value="other">Lainnya</option></select></label><label>Pemilik<input v-model.trim="form.owner_name" maxlength="120" placeholder="Nama pemilik"></label></div><div class="guest-form-row"><label>Jumlah<input v-model="form.quantity" type="number" min="0.001" step="0.001" required></label><label>Satuan<input v-model.trim="form.unit" maxlength="24" required placeholder="gram / item"></label></div><label>Perkiraan nilai<MoneyInput v-model="form.estimated_value" min="0" placeholder="Nilai rupiah" /></label><div class="guest-form-row"><label>Tanggal penilaian<input v-model="form.valuation_date" type="date"></label><label>Tanggal diperoleh<input v-model="form.acquired_at" type="date"></label></div><label>Catatan<textarea v-model.trim="form.notes" maxlength="3000" placeholder="Simpan keterangan atau lokasi penyimpanan"></textarea></label><p v-if="formError" class="form-error" role="alert">{{ formError }}</p><p class="field-note">Nilai adalah estimasi pada tanggal penilaian dan tidak mengubah saldo kas atau rekening.</p><button class="button button--primary" type="submit" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Simpan aset' }}</button></form></aside></div>

        <div v-if="allocator && selected" class="sheet-backdrop" @click.self="closeAllocator"><aside class="guest-sheet finance-sheet" role="dialog" aria-modal="true" aria-labelledby="allocation-title"><header><div><span class="eyebrow">TAUTKAN KEBUTUHAN</span><h2 id="allocation-title">Alokasi aset</h2></div><button class="icon-button" aria-label="Tutup" @click="closeAllocator">×</button></header><form @submit.prevent="allocate"><label>Kebutuhan<select v-model="allocation.need_id" required><option value="">Pilih kebutuhan</option><option v-for="need in needs" :key="need.id" :value="String(need.id)">{{ need.title }}</option></select></label><label>Jumlah dialokasikan<input v-model="allocation.quantity" type="number" min="0.001" :max="available" step="0.001" required><em>Tersedia {{ available }} {{ selected.unit }}</em></label><label>Catatan<textarea v-model.trim="allocation.notes" maxlength="1000"></textarea></label><p v-if="formError" class="form-error" role="alert">{{ formError }}</p><button class="button button--primary" type="submit" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Simpan alokasi' }}</button></form></aside></div>
        <ConfirmDialog :open="confirmOpen" :title="confirmOptions.title" :message="confirmOptions.message" :confirm-label="confirmOptions.confirmLabel" :destructive="confirmOptions.destructive" @cancel="finishConfirm(false)" @confirm="finishConfirm(true)" />
    </section>
</template>

<style scoped>
.asset-page { color: var(--text-primary); margin: 0 auto; max-width: 1220px; min-width: 0; padding: 36px clamp(18px, 4vw, 52px) 72px; width: 100%; }
.asset-page .records-head { align-items: flex-end; display: flex; gap: 22px; justify-content: space-between; margin: 8px 0 26px; }
.asset-page .records-head > div { min-width: 0; }
.asset-page .records-head .eyebrow { color: var(--text-secondary); display: block; font-size: 10px; font-weight: 800; letter-spacing: 1px; margin-bottom: 8px; }
.asset-page .records-head h1 { color: var(--text-primary); font: 500 clamp(40px, 5vw, 56px)/1 var(--font-display); margin: 0 0 10px; }
.asset-page .records-head p { color: var(--text-secondary); font-size: 14px; line-height: 1.6; margin: 0; max-width: 610px; }
.asset-page .records-head > .button { flex: 0 0 auto; }
.asset-page > .records-summary { display: grid; gap: 12px; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom: 16px; }
.asset-page > .records-summary article { background: var(--surface); border: 1px solid var(--champagne); border-radius: 14px; display: flex; flex-direction: column; gap: 7px; min-height: 106px; padding: 17px 19px; }
.asset-page > .records-summary article > span { color: var(--text-secondary); font-size: 10px; font-weight: 800; letter-spacing: .8px; text-transform: uppercase; }
.asset-page > .records-summary article > strong { color: var(--accent); font: 600 clamp(22px, 2.4vw, 30px)/1.1 var(--font-sans); font-variant-numeric: tabular-nums; }
.asset-page > .records-summary article > small { color: var(--text-secondary); font-size: 11px; line-height: 1.45; }
.asset-page .empty-state { align-items: center; background: var(--surface); border: 1px solid var(--champagne); border-radius: 16px; display: flex; flex-direction: column; margin-top: 12px; padding: 44px 24px; text-align: center; }
.asset-page .empty-state h2 { color: var(--text-primary); font: 600 30px/1.15 var(--font-display); margin: 0 0 8px; }
.asset-page .empty-state p { color: var(--text-secondary); font-size: 13px; line-height: 1.6; margin: 0 0 18px; max-width: 480px; }
.asset-page .record-list { background: var(--surface); border: 1px solid var(--champagne); border-radius: 15px; overflow: hidden; }
.asset-page .asset-row { align-items: center; background: transparent; border: 0; border-bottom: 1px solid var(--champagne); color: var(--text-primary); cursor: pointer; display: grid; gap: 14px; grid-template-columns: 40px minmax(0, 1fr) minmax(150px, auto) 18px; padding: 15px 18px; text-align: left; width: 100%; }
.asset-page .asset-row:last-child { border-bottom: 0; }
.asset-page .asset-row:hover { background: var(--surface-soft); }
.asset-page .asset-row:focus-visible { outline: 3px solid var(--focus); outline-offset: -3px; }
.asset-page .transaction-icon { align-items: center; background: var(--surface-soft); border-radius: 11px; color: var(--accent); display: flex; height: 40px; justify-content: center; width: 40px; }
.asset-page .transaction-copy { display: grid; gap: 4px; min-width: 0; }
.asset-page .transaction-copy strong { font-size: 13px; overflow: hidden; text-overflow: ellipsis; }
.asset-page .transaction-copy small, .asset-page .asset-value small { color: var(--text-secondary); font-size: 11px; line-height: 1.4; }
.asset-page .asset-value { display: grid; gap: 4px; justify-items: end; text-align: right; }
.asset-page .asset-value strong { color: var(--accent); font-size: 13px; font-variant-numeric: tabular-nums; }
.asset-page .asset-row > svg:last-child { color: var(--text-secondary); font-size: 12px; }
.asset-page .pagination-controls { background: var(--surface); border: 1px solid var(--champagne); border-radius: 0 0 14px 14px; margin-top: -1px; }
.asset-page .page-loading, .asset-page .page-error { color: var(--text-secondary); font-size: 14px; padding: 36px 0; }
.asset-page .field-note { color: var(--text-secondary); font-size: 11px; line-height: 1.55; }
.asset-page .asset-delete { color: var(--danger); }
@media (max-width: 760px) {
    .asset-page { padding: 26px 16px 52px; }
    .asset-page .records-head { align-items: flex-start; flex-direction: column; gap: 15px; }
    .asset-page > .records-summary { grid-template-columns: 1fr; }
    .asset-page .asset-row { gap: 10px; grid-template-columns: 38px minmax(0, 1fr) 18px; padding: 13px 12px; }
    .asset-page .asset-row .asset-value { grid-column: 2; justify-items: start; text-align: left; }
    .asset-page .asset-row > svg:last-child { grid-column: 3; grid-row: 1; }
    .asset-page .transaction-icon { height: 36px; width: 36px; }
    .asset-page .empty-state { padding: 34px 18px; }
}
</style>
