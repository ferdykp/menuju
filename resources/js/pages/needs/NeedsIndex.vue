<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';
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
const needs = ref([]); const pagination = ref(null); const currentPage = ref(1); const needCategories = ref([]); const budgetCategories = ref([]); const events = ref([]); const summary = ref({ total: 0, open: 0, purchased: 0, estimated: 0, actual: 0 }); const loading = ref(true); const error = ref(''); const showForm = ref(false); const editingId = ref(null); const saving = ref(false); const showCategoryComposer = ref(false); const newCategoryName = ref(''); const creatingCategory = ref(false); const categoryCreateError = ref(''); const filters = reactive({ q: String(route.query.q ?? ''), need_category_id: String(route.query.need_category_id ?? ''), event_id: String(route.query.event_id ?? ''), status: String(route.query.status ?? ''), budget_category_id: String(route.query.budget_category_id ?? '') }); const form = reactive(blankNeed()); const formError = ref(''); const timer = ref(null); const savedForm = ref('');
const needFormDirty = computed(() => JSON.stringify({ ...form, new_category_name: newCategoryName.value }) !== savedForm.value);
const selectedCategorySummary = computed(() => needCategories.value.find((category) => String(category.id) === filters.need_category_id) ?? null);
const needFormGuard = useUnsavedForm({ isOpen: showForm, isDirty: needFormDirty, confirm: askConfirm, onDiscard: () => { showForm.value = false; }, title: 'Perubahan kebutuhan belum disimpan' });
function blankNeed() { return { title: '', need_category_id: '', event_id: '', vendor_name: '', budget_category: '', budget_category_id: '', quantity: 1, estimated_amount: '', needed_by: '', priority: 'normal', status: 'planned', notes: '' }; }
function dateLabel(value) { if (!value) return 'Tidak ditentukan'; const iso = String(value).slice(0,10); return new Date(`${iso}T12:00:00Z`).toLocaleDateString('id-ID',{timeZone:'UTC',day:'numeric',month:'short',year:'numeric'}); }
function statusName(value) { return { planned: 'Direncanakan', shortlisted: 'Dipertimbangkan', purchased: 'Sudah dibeli' }[value] ?? value; }
async function load(page = currentPage.value) {
    loading.value = true;
    error.value = '';
    try {
        const params = { ...Object.fromEntries(Object.entries(filters).filter(([, value]) => value)), page, per_page: 25 };
        const { data } = await axios.get(`/api/workspaces/${props.workspaceId}/needs`, { params });
        if (page > data.pagination.last_page && data.pagination.last_page > 0) return await load(data.pagination.last_page);
        needs.value = data.needs;
        pagination.value = data.pagination;
        currentPage.value = data.pagination.current_page;
        needCategories.value = data.need_categories ?? [];
        budgetCategories.value = data.budget_categories ?? [];
        events.value = data.events ?? [];
        summary.value = data.summary;
        const editId = String(route.query.edit ?? '');
        if (editId && !showForm.value) {
            let editNeed = needs.value.find((item) => String(item.id) === editId);
            if (!editNeed) editNeed = (await axios.get(`/api/needs/${editId}`)).data.need;
            openEdit(editNeed);
        } else if (route.query.create && !showForm.value) openCreate();
    } catch {
        error.value = 'Daftar kebutuhan belum berhasil dimuat. Coba lagi.';
    } finally {
        loading.value = false;
    }
}
function openCreate() { editingId.value = null; Object.assign(form, { ...blankNeed(), event_id: String(route.query.event_id ?? filters.event_id ?? '') }); newCategoryName.value = ''; showCategoryComposer.value = false; formError.value = ''; categoryCreateError.value = ''; savedForm.value = JSON.stringify({ ...form, new_category_name: '' }); showForm.value = true; }
function openEdit(item) { editingId.value = item.id; Object.assign(form, { ...blankNeed(), ...item, need_category_id: item.need_category_id ? String(item.need_category_id) : '', event_id: item.event_id ? String(item.event_id) : '', budget_category_id: item.budget_category_id ? String(item.budget_category_id) : '', estimated_amount: item.estimated_amount ?? '', needed_by: item.needed_by ?? '' }); newCategoryName.value = ''; showCategoryComposer.value = false; savedForm.value = JSON.stringify({ ...form, new_category_name: '' }); formError.value = ''; categoryCreateError.value = ''; showForm.value = true; }
async function closeForm() { if (await needFormGuard.requestClose() && (route.query.edit || route.query.create)) await router.replace({ path: '/app/needs', query: { ...route.query, edit: undefined, create: undefined } }); }
async function save() {
    saving.value = true;
    formError.value = '';
    const body = {
        title: form.title,
        need_category_id: form.need_category_id ? Number(form.need_category_id) : null,
        event_id: form.event_id ? Number(form.event_id) : null,
        vendor_name: form.vendor_name || null,
        budget_category_id: form.budget_category_id ? Number(form.budget_category_id) : null,
        quantity: Number(form.quantity || 1),
        estimated_amount: form.estimated_amount === '' ? null : form.estimated_amount,
        needed_by: form.needed_by || null,
        priority: form.priority,
        notes: form.notes || null,
    };
    if (editingId.value && form.status !== 'purchased') body.status = form.status;

    try {
        if (editingId.value) await axios.patch(`/api/needs/${editingId.value}`, body);
        else await axios.post(`/api/workspaces/${props.workspaceId}/needs`, body);
        showForm.value = false;
        if (route.query.edit || route.query.create) await router.replace({ path: '/app/needs', query: { ...route.query, edit: undefined, create: undefined } });
        if (!editingId.value) currentPage.value = 1;
        await load();
    } catch (failure) {
        const message = Object.values(failure.response?.data?.errors ?? {}).flat()[0] ?? failure.response?.data?.message;
        formError.value = typeof message === 'string' && message.startsWith('validation.')
            ? 'Ada isian yang formatnya tidak sesuai. Periksa kembali data lalu coba lagi.'
            : message ?? 'Kebutuhan belum berhasil disimpan.';
    } finally {
        saving.value = false;
    }
}
async function createNeedCategory() { creatingCategory.value = true; categoryCreateError.value = ''; try { const { data } = await axios.post(`/api/workspaces/${props.workspaceId}/need-categories`, { name: newCategoryName.value }); needCategories.value.push({ ...data.category, item_count: 0, open_count: 0, purchased_count: 0, estimated_total: 0, actual_total: 0 }); form.need_category_id = String(data.category.id); newCategoryName.value = ''; showCategoryComposer.value = false; } catch (e) { categoryCreateError.value = Object.values(e.response?.data?.errors ?? {}).flat()[0] ?? 'Kategori belum berhasil ditambahkan.'; } finally { creatingCategory.value = false; } }
watch(() => [filters.need_category_id, filters.event_id, filters.status, filters.budget_category_id], () => { currentPage.value = 1; load(1); }); function searchChanged() { clearTimeout(timer.value); timer.value = setTimeout(() => { currentPage.value = 1; load(1); }, 250); }
onMounted(load);
</script>

<template>
    <section class="needs-page" aria-labelledby="needs-title">
        <header class="needs-head"><div><p class="eyebrow"><span class="eyebrow__dot"></span> DAFTAR KEBUTUHAN HARI ISTIMEWA</p><h1 id="needs-title">Kebutuhan</h1><p>Rencanakan barang dan jasa. Catatan rencana tidak mengubah saldo kalian.</p></div><button class="button button--primary" @click="openCreate"><FaIcon :icon="['fas','plus']" /> Tambah kebutuhan</button></header>
        <div v-if="loading && !needs.length" class="home-skeleton"><div class="skeleton-line skeleton-line--title"></div><div class="skeleton-block"></div></div>
        <div v-else-if="error && !summary.total" class="page-error" role="alert"><p>{{ error }}</p><button class="button button--quiet" @click="load">Coba lagi</button></div>
        <template v-else>
            <section class="need-summary" aria-label="Ringkasan kebutuhan"><article><span>KEBUTUHAN AKTIF</span><strong><AnimatedNumber :value="summary.open" /></strong><small>dari {{ summary.total }} kebutuhan</small></article><article><span>ESTIMASI TERBUKA</span><strong class="need-summary__money"><AnimatedNumber :value="summary.estimated" currency /></strong><small>rencana, bukan pengeluaran</small></article><article><span>SUDAH DIBELI</span><strong><AnimatedNumber :value="summary.purchased" /></strong><small><span class="need-summary__money-small"><AnimatedNumber :value="summary.actual" currency /></span> realisasi yang dicatat</small></article></section>
            <div v-if="error" class="guest-error" role="alert">{{ error }}</div>
            <section class="need-card"><div class="need-toolbar"><label class="guest-search"><FaIcon :icon="['fas','magnifying-glass']" /><span class="sr-only">Cari kebutuhan</span><input v-model.trim="filters.q" placeholder="Cari barang, jasa, atau vendor" @input="searchChanged" /></label><MotionSelect v-model="filters.need_category_id" aria-label="Filter kategori kebutuhan"><option value="">Semua kategori</option><option v-for="category in needCategories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></MotionSelect><MotionSelect v-model="filters.event_id" aria-label="Filter acara"><option value="">Semua acara</option><option v-for="event in events" :key="event.id" :value="String(event.id)">{{ event.title }}</option></MotionSelect><MotionSelect v-model="filters.budget_category_id" aria-label="Filter kategori anggaran"><option value="">Semua anggaran</option><option v-for="category in budgetCategories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></MotionSelect><MotionSelect v-model="filters.status" aria-label="Filter status"><option value="">Semua status</option><option value="planned">Direncanakan</option><option value="shortlisted">Dipertimbangkan</option><option value="purchased">Sudah dibeli</option></MotionSelect><button class="filter-reset" aria-label="Bersihkan filter" @click="Object.assign(filters,{q:'',need_category_id:'',event_id:'',status:'',budget_category_id:''});currentPage=1;load(1)"><FaIcon :icon="['fas','rotate']" /></button></div>
                <section v-if="selectedCategorySummary" class="need-category-summary" aria-live="polite"><div><p class="eyebrow">TOTAL KATEGORI</p><h2>{{ selectedCategorySummary.name }}</h2><small>{{ selectedCategorySummary.item_count }} item · {{ selectedCategorySummary.open_count }} belum dibeli · {{ selectedCategorySummary.purchased_count }} sudah dibeli</small></div><div><span>ESTIMASI BELUM DIBELI</span><strong>{{ formatRupiah(selectedCategorySummary.estimated_total) }}</strong></div><div><span>REALISASI PEMBELIAN</span><strong>{{ formatRupiah(selectedCategorySummary.actual_total) }}</strong></div></section>
                <div v-if="loading" class="guest-loading">Memuat kebutuhan…</div><div v-else-if="!needs.length" class="need-empty"><span><FaIcon :icon="['fas','leaf']" /></span><h2>{{ filters.q || filters.need_category_id || filters.event_id || filters.status || filters.budget_category_id ? 'Tidak ada kebutuhan yang cocok.' : 'Apa yang perlu kalian siapkan?' }}</h2><p>{{ filters.q || filters.need_category_id || filters.event_id || filters.status || filters.budget_category_id ? 'Ubah kata pencarian atau filter.' : 'Simpan kebutuhan dan estimasinya di sini tanpa mencampurkannya dengan pengeluaran aktual.' }}</p><button v-if="!filters.q&&!filters.need_category_id&&!filters.event_id&&!filters.status&&!filters.budget_category_id" class="button button--primary" @click="openCreate">Tambah kebutuhan pertama</button></div>
                <div v-else class="need-list"><div class="need-list__header"><span>Barang / jasa</span><span>Kategori</span><span>Target</span><span>Estimasi</span><span>Status</span></div><TransitionGroup name="list" tag="div" class="need-list__items"><article v-for="item in needs" :key="item.id" class="need-row"><RouterLink class="need-item" :to="{ path: '/app/needs/' + item.id, query: route.query }"><span class="need-item__mark"><FaIcon :icon="['fas',item.status==='purchased'?'check':'leaf']" /></span><span><strong>{{ item.title }}</strong><small>{{ item.vendor_name || item.quantity + ' unit' }} · {{ item.event?.title || 'Umum' }}</small></span></RouterLink><span class="need-cat">{{ item.need_category?.name || item.category }}</span><span class="need-date">{{ dateLabel(item.needed_by) }}</span><span class="need-amount">{{ formatRupiah(item.status==='purchased'?item.purchased_amount:item.estimated_amount) }}</span><span class="need-pill" :class="`need-pill--${item.status}`">{{ statusName(item.status) }}</span><RouterLink class="need-arrow" :aria-label="`Detail ${item.title}`" :to="{ path: '/app/needs/' + item.id, query: route.query }"><FaIcon :icon="['fas','chevron-right']" /></RouterLink></article></TransitionGroup></div>
                <PaginationControls v-if="pagination" :pagination="pagination" @change="load" />
                <footer v-if="needs.length" class="need-foot">{{ needs.length }} kebutuhan ditampilkan <span>Estimasi tetap terpisah dari pembelian aktual.</span></footer>
            </section>
        </template>
        <Transition name="overlay" appear>
        <div v-if="showForm" class="sheet-backdrop" @click.self="closeForm"><aside class="guest-sheet" role="dialog" aria-modal="true" aria-labelledby="need-form-title"><header><div><p class="eyebrow">DAFTAR KEBUTUHAN</p><h2 id="need-form-title">{{ editingId?'Ubah kebutuhan':'Tambah kebutuhan' }}</h2></div><button class="icon-button" aria-label="Tutup" @click="closeForm"><FaIcon :icon="['fas','xmark']" /></button></header><form @submit.prevent="save"><label>Nama barang atau jasa<input v-model.trim="form.title" required maxlength="180" placeholder="Contoh: Dekorasi meja akad" /></label><div class="guest-form-row need-category-fields"><label>Kategori kebutuhan<MotionSelect v-model="form.need_category_id" required><option value="" disabled>Pilih kategori</option><option v-for="category in needCategories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></MotionSelect></label><label>Jumlah<input v-model.number="form.quantity" type="number" min="1" /></label></div><button class="need-add-category" type="button" @click="showCategoryComposer = !showCategoryComposer">{{ showCategoryComposer ? 'Tutup tambah kategori' : '+ Buat kategori baru' }}</button><div v-if="showCategoryComposer" class="need-category-composer"><label for="need-category-name">Nama kategori</label><div><input id="need-category-name" v-model.trim="newCategoryName" maxlength="80" required placeholder="Contoh: Seserahan" /><button class="button button--primary" type="button" :disabled="creatingCategory || !newCategoryName" @click="createNeedCategory">{{ creatingCategory ? 'Menyimpan…' : 'Simpan kategori' }}</button></div><p v-if="categoryCreateError" class="guest-error" role="alert">{{ categoryCreateError }}</p></div><div class="guest-form-row"><label>Estimasi biaya<MoneyInput v-model="form.estimated_amount" min="0" placeholder="0" /></label><label>Dibutuhkan pada<input v-model="form.needed_by" type="date" /></label></div><label>Untuk acara<MotionSelect v-model="form.event_id"><option value="">Umum / tanpa acara</option><option v-for="event in events" :key="event.id" :value="String(event.id)">{{ event.title }}</option></MotionSelect></label><div class="guest-form-row"><label>Vendor <em>Opsional</em><input v-model.trim="form.vendor_name" /></label><label>Kategori anggaran <em>Opsional</em><MotionSelect v-model="form.budget_category_id"><option value="">Tanpa kategori anggaran</option><option v-for="category in budgetCategories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></MotionSelect></label></div><label v-if="editingId && form.status !== 'purchased'">Status kebutuhan<MotionSelect v-model="form.status"><option value="planned">Direncanakan</option><option value="shortlisted">Dipertimbangkan</option></MotionSelect></label><p v-else-if="form.status === 'purchased'" class="need-status-locked">Sudah dibeli. Koreksi realisasi dari alur pembelian agar catatan tetap akurat.</p><label>Prioritas<MotionSelect v-model="form.priority"><option value="low">Rendah</option><option value="normal">Normal</option><option value="high">Tinggi</option></MotionSelect></label><label>Catatan<textarea v-model.trim="form.notes" rows="3" /></label><p v-if="formError" class="guest-error">{{ formError }}</p><footer><button class="button button--quiet" type="button" @click="closeForm">Batal</button><button class="button button--primary" :disabled="saving">Simpan</button></footer></form></aside></div>
        </Transition>
        <ConfirmDialog :open="confirmOpen" :title="confirmOptions.title" :message="confirmOptions.message" :confirm-label="confirmOptions.confirmLabel" @cancel="finishConfirm(false)" @confirm="finishConfirm(true)" />
    </section>
</template>

<style scoped>
.needs-page{max-width:1230px;margin:auto;padding:38px clamp(20px,4vw,56px) 70px;color:var(--text-primary)}.needs-head{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin:12px 0 28px}.needs-head h1{font:500 clamp(42px,5vw,58px)/1 var(--font-display);margin:14px 0 9px}.needs-head p:last-child{font-size:14px;color:var(--text-secondary);margin:0}.need-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:13px;margin-bottom:19px}.need-summary article{background:var(--surface);border:1px solid var(--champagne);border-radius:14px;padding:17px 20px;display:flex;flex-direction:column;gap:4px}.need-summary article>span{font-size:10px;font-weight:700;letter-spacing:1px;color:var(--text-secondary)}.need-summary strong{font:500 27px var(--font-display);color:var(--accent)}.need-summary .need-summary__money{font:600 clamp(22px,2vw,28px)/1.2 var(--font-sans);font-variant-numeric:tabular-nums;letter-spacing:-.025em;white-space:nowrap}.need-summary small{font-size:11px;color:var(--text-secondary)}.need-summary .need-summary__money-small{font-size:14px;font-weight:700;font-variant-numeric:tabular-nums;color:var(--accent);letter-spacing:-.01em}.need-card{background:var(--surface);border:1px solid var(--champagne);border-radius:16px;overflow:hidden}.need-toolbar{padding:14px;display:flex;gap:9px;border-bottom:1px solid var(--champagne)}.need-toolbar select{height:40px;border:1px solid #e2e4dc;background:var(--surface);border-radius:8px;padding:0 11px;font-size:12px;color:var(--text-secondary)}.need-toolbar .guest-search{flex:1}.need-empty{text-align:center;padding:48px 18px}.need-empty>span{width:50px;height:50px;border-radius:50%;background:var(--surface-soft);color:var(--accent);display:grid;place-items:center;margin:auto auto 14px}.need-empty h2{font:500 29px var(--font-display);margin:0 0 8px}.need-empty p{font-size:13px;color:var(--text-secondary);max-width:490px;margin:0 auto 20px;line-height:1.6}.need-list__header,.need-row{display:grid;grid-template-columns:minmax(200px,1.8fr) 1fr 1fr 1fr 1.1fr 24px;gap:12px;align-items:center;padding:12px 17px}.need-list__header{font-size:10px;text-transform:uppercase;letter-spacing:.7px;color:var(--text-secondary);background:var(--surface);border-bottom:1px solid var(--champagne)}.need-row{min-height:70px;border-bottom:1px solid #f0f0eb}.need-item{display:flex;align-items:center;gap:10px;border:0;background:none;text-align:left;padding:0;color:inherit;cursor:pointer}.need-item__mark{width:34px;height:34px;display:grid;place-items:center;border-radius:10px;background:#f0f2ec;color:var(--accent)}.need-item>span:last-child{display:flex;flex-direction:column;gap:4px}.need-item strong{font-size:12px}.need-item small,.need-date,.need-cat{font-size:11px;color:var(--text-secondary)}.need-amount{font-size:15px;line-height:1.3;font-weight:700;font-variant-numeric:tabular-nums;letter-spacing:-.01em;white-space:nowrap;color:var(--text-primary)}.need-pill{font-size:10px;border-radius:20px;background:#f0f1ed;color:#6f756c;padding:6px 8px;width:max-content}.need-pill--purchased{background:#e8efe4;color:var(--accent)}.need-pill--shortlisted{background:#f5f0e1;color:#8a7132}.need-arrow{border:0;background:none;color:#788075;cursor:pointer}.need-foot{border-top:1px solid var(--champagne);padding:13px 17px;display:flex;justify-content:space-between;font-size:11px;color:#7e847b}.need-foot span{color:#979b93}.need-status-locked{background:var(--surface-soft);border-radius:9px;color:var(--text-secondary);font-size:11px;line-height:1.55;margin:0;padding:12px}.purchase-note{font-size:12px;line-height:1.6;color:var(--text-secondary);background:var(--surface-soft);border-radius:9px;padding:13px;margin:20px 0}.sheet-backdrop--nested{z-index:110;background:#2228}.guest-sheet--small{max-width:460px}.need-category-summary{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:18px 20px;background:var(--surface-soft);border-bottom:1px solid var(--surface-soft)}.need-category-summary .eyebrow{margin:0 0 4px}.need-category-summary h2{font:500 24px var(--font-display);margin:0 0 3px;color:var(--text-primary)}.need-category-summary small{font-size:11px;color:var(--text-secondary)}.need-category-summary>div:not(:first-child){display:flex;flex-direction:column;gap:5px}.need-category-summary>div>span{font-size:9px;font-weight:700;letter-spacing:.7px;color:var(--text-secondary)}.need-category-summary strong{font:600 21px/1.2 var(--font-sans);font-variant-numeric:tabular-nums;letter-spacing:-.02em;color:var(--accent)}.need-category-fields select{width:100%}.need-add-category{align-self:flex-start;margin-top:-5px;border:0;background:none;color:var(--accent);font-size:12px;font-weight:700;cursor:pointer;padding:4px 0}.need-category-composer{background:#f6f7f3;border:1px solid var(--surface-soft);border-radius:10px;padding:13px}.need-category-composer>label{display:block;font-size:12px;font-weight:700;margin-bottom:7px}.need-category-composer>div{display:flex;gap:8px}.need-category-composer input{flex:1;min-width:0}.need-category-composer .guest-error{margin:8px 0 0}@media(max-width:790px){.needs-page{padding:25px 16px 50px}.needs-head{align-items:flex-start;flex-direction:column}.need-summary{grid-template-columns:1fr}.need-toolbar{flex-wrap:wrap}.need-toolbar .guest-search{flex-basis:100%}.need-toolbar select{flex:1;min-width:130px}.need-list__header{display:none}.need-row{grid-template-columns:minmax(0,1fr) auto 26px;gap:8px;padding:13px}.need-cat,.need-date{display:none}.need-item{grid-column:1}.need-amount{grid-column:2;font-size:14px}.need-pill{display:none}.need-arrow{grid-column:3}.need-foot{flex-direction:column;gap:5px}.need-category-summary{align-items:flex-start;flex-direction:column;gap:12px}.need-category-summary>div:not(:first-child){width:100%;flex-direction:row;align-items:center;justify-content:space-between}}
/* Operational copy stays comfortably readable; compact uppercase labels remain secondary. */
.needs-page .button { font-size: 14px; }
.need-summary article > span,
.need-list__header,
.need-category-summary > div > span { font-size: 11px; }
.need-summary small,
.need-category-summary small { font-size: 13px; }
:deep(.need-toolbar .motion-select__trigger) { font-size: 14px; }
.need-list__header { letter-spacing: .5px; }
.need-item strong { font-size: 14px; }
.need-item small,
.need-date,
.need-cat { font-size: 13px; }
.need-pill { font-size: 12px; }
.need-foot { font-size: 12px; }
.need-category-summary strong { font-size: 23px; }
@media (max-width: 790px) {
    .need-amount { font-size: 15px; }
    :deep(.need-toolbar .motion-select__trigger) { font-size: 13px; }
}
</style>
