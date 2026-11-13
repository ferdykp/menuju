<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import RecordDetailHero from '../../components/RecordDetailHero.vue';
import MoneyInput from '../../components/MoneyInput.vue';
import { useConfirmDialog } from '../../composables/useConfirmDialog';
import { useUnsavedForm } from '../../composables/useUnsavedForm';

import { formatRupiah } from '../../utils/money';
const props = defineProps({ workspaceId: { type: [String, Number], required: true } });
const route = useRoute();
const router = useRouter();
const { open: confirmOpen, options: confirmOptions, confirm: askConfirm, finish: finishConfirm } = useConfirmDialog();
const need = ref(null);
const loading = ref(true);
const error = ref('');
const formError = ref('');
const saving = ref(false);
const purchaseOpen = ref(false);
const purchaseForm = reactive({ purchased_amount: '', purchased_at: new Date().toLocaleDateString('en-CA') });
const savedPurchase = ref('');
const needId = computed(() => route.path.match(/^\/app\/needs\/([^/]+)/)?.[1] ?? '');
const purchaseDirty = computed(() => purchaseOpen.value && JSON.stringify(purchaseForm) !== savedPurchase.value);
const purchaseGuard = useUnsavedForm({
    isOpen: purchaseOpen,
    isDirty: purchaseDirty,
    confirm: askConfirm,
    onDiscard: () => { purchaseOpen.value = false; },
    title: 'Catatan pembelian belum disimpan',
    message: 'Jika ditutup sekarang, jumlah dan tanggal pembelian tidak akan disimpan.',
});

function dateLabel(value) { return value ? new Date(`${String(value).slice(0,10)}T12:00:00Z`).toLocaleDateString('id-ID', { timeZone: 'UTC', day: 'numeric', month: 'long', year: 'numeric' }) : 'Belum ditentukan'; }
function statusName(value) { return { planned: 'Direncanakan', shortlisted: 'Dipertimbangkan', purchased: 'Sudah dibeli' }[value] ?? value; }
async function load() {
    loading.value = true;
    error.value = '';
    try { need.value = (await axios.get(`/api/needs/${needId.value}`)).data.need; }
    catch (failure) { need.value = null; error.value = failure.response?.status === 404 || failure.response?.status === 403 ? 'Kebutuhan ini tidak ditemukan di workspace kalian.' : 'Detail kebutuhan belum berhasil dimuat. Coba lagi.'; }
    finally { loading.value = false; }
}
function openPurchase() {
    formError.value = '';
    Object.assign(purchaseForm, { purchased_amount: need.value?.estimated_amount ?? '', purchased_at: new Date().toLocaleDateString('en-CA') });
    savedPurchase.value = JSON.stringify(purchaseForm);
    purchaseOpen.value = true;
}
async function savePurchase() {
    saving.value = true;
    formError.value = '';
    try {
        need.value = (await axios.post(`/api/needs/${needId.value}/purchase`, { purchased_amount: purchaseForm.purchased_amount, purchased_at: purchaseForm.purchased_at || null })).data.need;
        purchaseOpen.value = false;
    } catch (failure) {
        formError.value = Object.values(failure.response?.data?.errors ?? {}).flat()[0] ?? failure.response?.data?.message ?? 'Pembelian belum berhasil dicatat.';
    } finally { saving.value = false; }
}
async function removeNeed() {
    if (!await askConfirm({ title: `Hapus “${need.value.title}”?`, message: 'Kebutuhan ini akan dihapus dari daftar. Catatan transaksi yang sudah ada tetap tersimpan.', confirmLabel: 'Hapus kebutuhan', destructive: true })) return;
    try { await axios.delete(`/api/needs/${needId.value}`); await router.replace('/app/needs'); }
    catch (failure) { error.value = failure.response?.data?.message ?? 'Kebutuhan belum berhasil dihapus. Coba lagi.'; }
}

onMounted(load);
watch(needId, load);
</script>

<template>
    <section class="need-detail-page" aria-labelledby="need-detail-title">
        <div v-if="loading" class="home-skeleton"><div class="skeleton-line skeleton-line--title"></div><div class="skeleton-block"></div></div>
        <div v-else-if="error && !need" class="page-error" role="alert"><p>{{ error }}</p><div><button class="button button--quiet" @click="load">Coba lagi</button><RouterLink class="button button--primary" to="/app/needs">Kembali ke kebutuhan</RouterLink></div></div>
        <template v-else-if="need">
            <RouterLink class="need-detail-back" :to="{ path: '/app/needs', query: route.query }"><FaIcon :icon="['fas','arrow-left']" /> Kembali ke kebutuhan</RouterLink>
            <RecordDetailHero id="need-detail-title" heading-tag="h1" eyebrow="DETAIL KEBUTUHAN" :title="need.title" :subtitle="need.vendor_name || 'Vendor belum dipilih'">
                <template #meta><div class="need-detail-badges"><span><FaIcon :icon="['fas','leaf']" /> {{ need.category }}</span><span :class="`need-detail-status need-detail-status--${need.status}`">{{ statusName(need.status) }}</span><span v-if="need.priority === 'high'" class="need-detail-priority">Prioritas tinggi</span></div></template>
                <template #actions><RouterLink class="button button--quiet" :to="{ path: '/app/needs', query: { ...route.query, edit: String(need.id) } }"><FaIcon :icon="['fas','pen']" /> Ubah rincian</RouterLink><button v-if="need.status !== 'purchased'" class="button button--primary" @click="openPurchase"><FaIcon :icon="['fas','check']" /> Catat pembelian</button></template>
            </RecordDetailHero>
            <div v-if="error" class="guest-error" role="alert">{{ error }}</div>
            <section class="need-detail-summary"><article><span>ESTIMASI RENCANA</span><strong>{{ formatRupiah(need.estimated_amount) }}</strong><small>Angka rencana, belum mengurangi saldo.</small></article><article><span>REALISASI PEMBELIAN</span><strong>{{ need.status === 'purchased' ? formatRupiah(need.purchased_amount) : 'Belum dibeli' }}</strong><small>{{ need.purchased_at ? `Dibeli ${dateLabel(need.purchased_at)}` : 'Catat setelah pembelian terjadi.' }}</small></article><article><span>JUMLAH</span><strong>{{ new Intl.NumberFormat('id-ID').format(need.quantity) }} unit</strong><small>{{ need.needed_by ? `Dibutuhkan ${dateLabel(need.needed_by)}` : 'Tanggal belum ditentukan' }}</small></article></section>
            <section class="need-detail-content"><article class="need-detail-card"><p class="eyebrow">RINCIAN KEBUTUHAN</p><h2>Informasi barang atau jasa</h2><dl><div><dt>Kategori</dt><dd>{{ need.category }}</dd></div><div><dt>Acara</dt><dd><RouterLink v-if="need.event_id && need.event" :to="`/app/events/${need.event_id}`">{{ need.event.title }}</RouterLink><span v-else>Umum / tanpa acara</span></dd></div><div><dt>Vendor</dt><dd>{{ need.vendor_name || 'Belum dipilih' }}</dd></div><div><dt>Kategori anggaran</dt><dd><RouterLink v-if="need.budget_category_id" :to="`/app/budgets/${need.budget_category_id}`">{{ need.budget_category?.name || (typeof need.budget_category === 'string' ? need.budget_category : 'Belum ditautkan') }}</RouterLink><span v-else>{{ need.budget_category || 'Belum ditautkan' }}</span></dd></div><div><dt>Prioritas</dt><dd>{{ { low: 'Rendah', normal: 'Normal', high: 'Tinggi' }[need.priority] || 'Normal' }}</dd></div><div><dt>Status</dt><dd>{{ statusName(need.status) }}</dd></div></dl></article><article class="need-detail-card need-detail-notes"><p class="eyebrow">CATATAN</p><h2>Informasi tambahan</h2><p>{{ need.notes || 'Belum ada catatan untuk kebutuhan ini.' }}</p></article></section>
            <footer class="need-detail-footer"><span>Estimasi kebutuhan dan pengeluaran aktual dicatat terpisah.</span><button class="button button--quiet need-detail-delete" @click="removeNeed"><FaIcon :icon="['fas','trash']" /> Hapus kebutuhan</button></footer>
        </template>
        <Transition name="overlay" appear>
        <div v-if="purchaseOpen" class="sheet-backdrop" @click.self="purchaseGuard.requestClose()"><aside class="guest-sheet guest-sheet--small" role="dialog" aria-modal="true" aria-labelledby="need-purchase-title"><header><div><p class="eyebrow">PEMBELIAN TERJADI</p><h2 id="need-purchase-title">Catat pembelian</h2></div><button class="icon-button" aria-label="Tutup form pembelian" @click="purchaseGuard.requestClose()"><FaIcon :icon="['fas','xmark']" /></button></header><p class="need-purchase-note">Catatan ini menyimpan realisasi kebutuhan. Transaksi kas/bank dicatat terpisah di menu Transaksi.</p><form @submit.prevent="savePurchase"><label>Jumlah aktual<MoneyInput v-model="purchaseForm.purchased_amount" min="0" required /></label><label>Tanggal pembelian<input v-model="purchaseForm.purchased_at" type="date" required /></label><p v-if="formError" class="guest-error" role="alert">{{ formError }}</p><footer><button class="button button--quiet" type="button" @click="purchaseGuard.requestClose()">Batal</button><button class="button button--primary" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Konfirmasi pembelian' }}</button></footer></form></aside></div>
        </Transition>
        <ConfirmDialog :open="confirmOpen" :title="confirmOptions.title" :message="confirmOptions.message" :confirm-label="confirmOptions.confirmLabel" :destructive="confirmOptions.destructive" @cancel="finishConfirm(false)" @confirm="finishConfirm(true)" />
    </section>
</template>

<style scoped>
.need-detail-page{max-width:1080px;margin:auto;padding:35px clamp(18px,4vw,52px) 70px;color:var(--text-primary)}.need-detail-back{display:inline-flex;align-items:center;gap:8px;margin-bottom:16px;color:var(--accent);text-decoration:none;font-size:12px;font-weight:700}.need-detail-back:hover{text-decoration:underline;text-underline-offset:3px}.need-detail-badges{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.need-detail-badges>span{display:inline-flex;align-items:center;gap:5px;padding:7px 10px;border:1px solid color-mix(in srgb, var(--accent) 8%, var(--surface));border-radius:999px;background:var(--surface-soft);color:var(--accent);font-size:10px;font-weight:700;line-height:1}.need-detail-badges .need-detail-status--purchased{border-color:#cbdcc4;background:#e5f0e0;color:#355b30}.need-detail-badges .need-detail-status--shortlisted{border-color:#ebdfb9;background:#faf3df;color:#71581d}.need-detail-badges .need-detail-priority{border-color:#ecd1c9;background:#f9ebe7;color:#874437}.need-detail-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.need-detail-summary article,.need-detail-card{background:var(--surface);border:1px solid #e1e3da;border-radius:14px;padding:18px;box-shadow:0 5px 17px rgb(38 48 35 / 4%)}.need-detail-summary article{display:flex;flex-direction:column;gap:7px;min-height:112px}.need-detail-summary article>span{font-size:9px;letter-spacing:.9px;color:var(--text-secondary);font-weight:800}.need-detail-summary article>strong{font:600 25px/1.15 var(--font-display);color:var(--accent)}.need-detail-summary article>small{font-size:11px;color:var(--text-secondary);line-height:1.5}.need-detail-content{display:grid;grid-template-columns:1.1fr .9fr;gap:13px;margin-top:14px}.need-detail-card h2{font:600 25px/1.15 var(--font-display);color:var(--text-primary);margin:5px 0 14px}.need-detail-card dl{margin:0}.need-detail-card dl>div{display:flex;justify-content:space-between;gap:14px;padding:12px 0;border-bottom:1px solid #e9ebe4;font-size:12px}.need-detail-card dt{color:var(--text-secondary);font-weight:500}.need-detail-card dd{margin:0;text-align:right;color:var(--text-primary);font-weight:700}.need-detail-card dd a{color:var(--accent);text-decoration:underline;text-underline-offset:3px;font-weight:700}.need-detail-notes>p:last-child{font-size:13px;line-height:1.75;color:var(--text-secondary);white-space:pre-wrap}.need-detail-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:23px;padding-top:16px;border-top:1px solid var(--champagne)}.need-detail-footer>span{font-size:11px;color:var(--text-secondary)}.need-detail-delete{color:#9c4942}.need-purchase-note{font-size:12px;line-height:1.6;color:var(--text-secondary);background:var(--surface-soft);border:1px solid #dfe7d9;border-radius:10px;padding:13px;margin:20px 0}.need-detail-page .guest-sheet form{display:flex;flex-direction:column;gap:14px}.need-detail-page .guest-sheet form label{display:flex;flex-direction:column;gap:7px;font-size:12px;font-weight:700;color:var(--text-secondary)}.need-detail-page .guest-sheet form input{padding:11px;border:1px solid #cfd6c9;border-radius:8px;font:13px var(--font-body);background:var(--surface)}.need-detail-page .guest-sheet form footer{display:flex;justify-content:flex-end;gap:9px;margin-top:5px}
@media(max-width:760px){.need-detail-page{padding:24px 16px 48px}.need-detail-summary{grid-template-columns:1fr}.need-detail-content{grid-template-columns:1fr}.need-detail-footer{align-items:flex-start;flex-direction:column}.need-detail-footer .button{align-self:flex-end}}
/* Improve secondary information and labels without competing with the detail heading. */
.need-detail-back { font-size: 14px; }
.need-detail-badges > span { font-size: 12px; }
.need-detail-summary article > span { font-size: 11px; }
.need-detail-summary article > small { font-size: 13px; }
.need-detail-card dl > div { font-size: 14px; }
.need-detail-footer > span { font-size: 13px; }
.need-purchase-note { font-size: 14px; }
.need-detail-page .guest-sheet form label { font-size: 14px; }
.need-detail-page .guest-sheet form input { font-size: 16px; }
@media (max-width: 760px) {
    .need-detail-summary article > strong { font-size: 23px; }
}
</style>
