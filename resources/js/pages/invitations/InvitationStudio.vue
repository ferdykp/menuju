<script setup>
import axios from 'axios';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import { useConfirmDialog } from '../../composables/useConfirmDialog';
import { registerUnsavedGuard } from '../../unsavedChanges';
import InvitationExperience from './InvitationExperience.vue';
import InvitationOrnaments from './InvitationOrnaments.vue';

const props = defineProps({ workspaceId: { type: [String, Number], required: true }, workspace: { type: Object, required: true } });
const route = useRoute(); const router = useRouter();
const { open: confirmOpen, options: confirmOptions, confirm: askConfirm, finish: finishConfirm } = useConfirmDialog();
let unregisterUnsavedGuard = null;
const coverInput = ref(null);
const galleryInput = ref(null);
const galleryImages = ref([]);
const invitation = ref(null); const guests = ref([]); const loading = ref(true); const saving = ref(false); const coverSaving = ref(false); const error = ref(''); const formError = ref(''); const coverError = ref(''); const saveSuccess = ref(''); const publishConfirm = ref(false); const copied = ref(false); const guestLinks = ref({});
const guestSummary = ref(null);
const events = ref([]);
const form = reactive({ title: '', theme_key: 'botanical', motion_preset: 'theme', decorative_effects: true, cover_title: '', greeting: '', story: '', quote: '', closing: '' });
const previewReplayKey = ref(0);
const savedForm = ref('');
const hasUnsavedChanges = computed(() => mode.value === 'edit' && invitation.value && JSON.stringify(form) !== savedForm.value);
const mode = computed(() => route.path.endsWith('/edit') ? 'edit' : route.path.endsWith('/preview') ? 'preview' : route.path.endsWith('/share') ? 'share' : 'library');
const themes = [
    { key: 'botanical', title: 'Enchanted Flower Garden', detail: 'Wedding floral dengan mawar, greenery, dan taman yang hidup', image: '/images/invitations/enchanted-garden-background.webp', colors: ['#e9eee5','#536a54','#8a9c7f'] },
    { key: 'classic', title: 'Royal Romance', detail: 'Ivory dan aksen emas dengan frame serta tipografi klasik', image: '/images/invitations/editorial-preview.webp', colors: ['#f4eee3','#795b43','#c7a87c'] },
    { key: 'modern', title: 'Modern', detail: 'Minimal dan terang, dengan garis yang rapi', image: '/images/invitations/editorial-preview.webp', colors: ['#f4f2ef','#424744','#b3bbb4'] },
    { key: 'terracotta', title: 'Blooming Promise', detail: 'Undangan lamaran pastel dengan bunga hangat dan cahaya senja', image: '/images/invitations/blooming-promise-background.webp', colors: ['#f0e4db','#8d5745','#c47e61'] },
];
const coupleNames = computed(() => [props.workspace.partner_one_name, props.workspace.partner_two_name].filter(Boolean).join(' & '));
const selectedTheme = computed(() => themes.find((theme) => theme.key === invitation.value?.theme_key) ?? themes[0]);
const publicInvitationUrl = computed(() => `${baseUrl}/w/${invitation.value?.slug ?? ''}`);
const previewInvitation = computed(() => invitation.value ? ({
    ...invitation.value,
    wedding_date: props.workspace.wedding_date,
    timezone: props.workspace.timezone,
    events: events.value.map((event) => ({
        title: event.title,
        type: event.event_type,
        date: event.event_date,
        start_time: event.start_time,
        end_time: event.end_time,
        venue_name: event.venue_name,
        address: event.address,
        agenda: (event.agenda_items ?? []).map((item) => ({ title: item.title, start_time: item.start_time, end_time: item.end_time, location: item.location })),
    })),
    gallery_images: galleryImages.value.map((image) => ({ url: image.image_url, alt: image.alt_text })),
}) : null);
const baseUrl = window.location.origin;
function dateLabel(value) { return value ? new Date(`${String(value).slice(0,10)}T12:00:00Z`).toLocaleDateString('id-ID',{timeZone:'UTC',day:'numeric',month:'long',year:'numeric'}) : 'Tanggal menyusul'; }
async function load() {
    loading.value = true; error.value = '';
    try {
        const { data } = await axios.get(`/api/workspaces/${props.workspaceId}/invitation`); invitation.value = data.invitation;
        if (invitation.value) { Object.assign(form, { ...form, ...invitation.value }); savedForm.value = JSON.stringify(form); galleryImages.value = invitation.value.gallery_images ?? []; }
        if (invitation.value && mode.value === 'library') {
            try { guestSummary.value = (await axios.get(`/api/workspaces/${props.workspaceId}/guests?per_page=1`)).data.meta?.summary ?? null; }
            catch { guestSummary.value = null; }
        }
        if (invitation.value && mode.value === 'preview') events.value = (await axios.get(`/api/workspaces/${props.workspaceId}/events`)).data.events;
        if (mode.value === 'share' && invitation.value) guests.value = (await axios.get(`/api/workspaces/${props.workspaceId}/guests`)).data.data;
    } catch { error.value = 'Undangan belum berhasil dimuat. Coba lagi.'; }
    finally {
        loading.value = false;
        if (mode.value === 'edit' && route.hash === '#invitation-theme-picker') {
            await nextTick();
            const picker = document.getElementById('invitation-theme-picker');
            if (picker) window.scrollTo({ top: Math.max(0, picker.getBoundingClientRect().top + window.scrollY - 100), behavior: 'auto' });
        }
    }
}
async function create(theme) {
    saving.value = true; error.value = '';
    try { const { data } = await axios.post(`/api/workspaces/${props.workspaceId}/invitation`, { title: `Undangan ${coupleNames.value}`, theme_key: theme }); invitation.value = data.invitation; Object.assign(form, data.invitation); savedForm.value = JSON.stringify(form); await router.push('/app/invitations/edit'); }
    catch (e) { error.value = e.response?.data?.message ?? 'Undangan belum berhasil dibuat.'; }
    finally { saving.value = false; }
}
async function save() {
    if (!invitation.value) return false;
    saving.value = true; formError.value = ''; saveSuccess.value = '';
    try { const { data } = await axios.patch(`/api/workspaces/${props.workspaceId}/invitation/${invitation.value.id}`, form); invitation.value = data.invitation; Object.assign(form, data.invitation); savedForm.value = JSON.stringify(form); saveSuccess.value = 'Draft berhasil disimpan.'; return true; }
    catch (e) { formError.value = Object.values(e.response?.data?.errors ?? {}).flat()[0] ?? e.response?.data?.message ?? 'Perubahan undangan belum berhasil disimpan.'; return false; }
    finally { saving.value = false; }
}
async function openPreview() {
    if (saving.value) return;
    if (hasUnsavedChanges.value && !await save()) return;
    await router.push('/app/invitations/preview');
}
watch(form, () => { saveSuccess.value = ''; }, { deep: true });
async function uploadCover(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    coverSaving.value = true; coverError.value = ''; saveSuccess.value = '';
    const payload = new FormData(); payload.append('cover_image', file);
    try {
        const { data } = await axios.post(`/api/workspaces/${props.workspaceId}/invitation/${invitation.value.id}/cover`, payload);
        invitation.value = data.invitation;
    } catch (failure) {
        coverError.value = Object.values(failure.response?.data?.errors ?? {}).flat()[0] ?? failure.response?.data?.message ?? 'Foto sampul belum berhasil diunggah.';
    } finally { coverSaving.value = false; event.target.value = ''; }
}
async function removeCover() {
    coverSaving.value = true; coverError.value = ''; saveSuccess.value = '';
    try { invitation.value = (await axios.delete(`/api/workspaces/${props.workspaceId}/invitation/${invitation.value.id}/cover`)).data.invitation; }
    catch (failure) { coverError.value = failure.response?.data?.message ?? 'Foto sampul belum berhasil dihapus.'; }
    finally { coverSaving.value = false; }
}
async function uploadGallery(event) {
    const files = [...(event.target.files ?? [])];
    if (!files.length) return;
    if (galleryImages.value.length + files.length > 12) {
        coverError.value = 'Galeri maksimal berisi 12 foto.';
        event.target.value = '';
        return;
    }
    coverSaving.value = true; coverError.value = '';
    const payload = new FormData();
    files.forEach((file) => payload.append('images[]', file));
    try {
        galleryImages.value = (await axios.post(`/api/workspaces/${props.workspaceId}/invitation/${invitation.value.id}/gallery`, payload)).data.images;
    } catch (failure) {
        coverError.value = Object.values(failure.response?.data?.errors ?? {}).flat()[0] ?? failure.response?.data?.message ?? 'Foto galeri belum berhasil diunggah.';
    } finally { coverSaving.value = false; event.target.value = ''; }
}
async function removeGalleryImage(image) {
    coverSaving.value = true; coverError.value = '';
    try {
        galleryImages.value = (await axios.delete(`/api/workspaces/${props.workspaceId}/invitation/${invitation.value.id}/gallery/${image.id}`)).data.images;
    } catch (failure) {
        coverError.value = failure.response?.data?.message ?? 'Foto belum berhasil dihapus dari galeri.';
    } finally { coverSaving.value = false; }
}
onMounted(() => {
    unregisterUnsavedGuard = registerUnsavedGuard(async () => !hasUnsavedChanges.value || await askConfirm({ title: 'Perubahan belum disimpan', message: 'Jika meninggalkan editor sekarang, perubahan sejak penyimpanan terakhir akan hilang.', confirmLabel: 'Tinggalkan editor', destructive: true }));
});
onBeforeUnmount(() => unregisterUnsavedGuard?.());
async function publish(published = true) {
    saving.value = true;
    try { const { data } = await axios.post(`/api/workspaces/${props.workspaceId}/invitation/${invitation.value.id}/publish`, { published }); invitation.value = data.invitation; publishConfirm.value = false; }
    catch (e) { error.value = e.response?.data?.message ?? 'Status undangan belum berhasil diubah.'; }
    finally { saving.value = false; }
}
async function copyLink() { error.value = ''; try { await navigator.clipboard.writeText(`${baseUrl}/w/${invitation.value.slug}`); copied.value = true; setTimeout(() => copied.value = false, 1800); } catch { error.value = 'Tautan belum berhasil disalin. Periksa izin clipboard browser.'; } }
async function makeGuestLink(guest) { error.value = ''; try { const { data } = await axios.post(`/api/invitations/${invitation.value.id}/guests/${guest.id}/link`); guestLinks.value = { ...guestLinks.value, [guest.id]: data.url }; } catch (e) { error.value = e.response?.data?.message ?? 'Tautan personal belum berhasil dibuat.'; } }
async function copyGuestLink(guest) { const link = guestLinks.value[guest.id]; if (!link) return; error.value = ''; try { await navigator.clipboard.writeText(link); copied.value = true; setTimeout(() => copied.value = false, 1800); } catch { error.value = 'Tautan belum berhasil disalin. Periksa izin clipboard browser.'; } }
onMounted(load);
watch(() => route.path, () => load());
</script>

<template>
    <section class="invitation-studio" :class="`invitation-studio--${invitation?.theme_key || 'botanical'}`" aria-labelledby="invitation-title">
        <div v-if="loading" class="home-skeleton"><div class="skeleton-line skeleton-line--title"></div><div class="skeleton-block"></div></div>
        <div v-else-if="error && !invitation" class="page-error" role="alert"><p>{{ error }}</p><button class="button button--quiet" @click="load">Coba lagi</button></div>
        <template v-else-if="!invitation">
            <header class="studio-heading"><p class="eyebrow"><span class="eyebrow__dot"></span> UNDANGAN DIGITAL</p><h1 id="invitation-title">Sebuah cerita indah untuk dibagikan.</h1><p>Pilih tampilan awal untuk undangan {{ coupleNames }}. Kalian bisa mengubah semua kata sebelum diterbitkan.</p></header>
            <section class="theme-grid" aria-label="Pilihan tema undangan"><article v-for="theme in themes" :key="theme.key" class="theme-card" :class="`theme-card--${theme.key}`" :style="{ '--theme-bg': theme.colors[0], '--theme-accent': theme.colors[1], '--theme-detail': theme.colors[2] }"><div class="theme-card__preview" :style="{ '--theme-image': `url('${theme.image}')` }"><InvitationOrnaments class="theme-card__ornament" :theme="theme.key" placement="cover" /><span class="theme-card__leaf">m</span><small>UNDANGAN PERNIKAHAN</small><strong>{{ coupleNames }}</strong><i>{{ dateLabel(workspace.wedding_date) }}</i></div><div class="theme-card__body"><div><h2>{{ theme.title }}</h2><p>{{ theme.detail }}</p></div><button class="button button--primary" :disabled="saving" @click="create(theme.key)">Pilih tema</button></div></article></section>
        </template>
        <template v-else>
            <div class="studio-top"><RouterLink class="studio-back" to="/app/invitations"><FaIcon :icon="['fas','chevron-left']" /> Perpustakaan</RouterLink><span v-if="mode!=='library'" class="studio-status" :class="{ 'studio-status--published': invitation.published_at }"><i></i>{{ invitation.published_at ? 'Diterbitkan' : 'Draft' }}</span></div><p v-if="error" class="guest-error" role="alert">{{ error }}</p>
            <section v-if="mode==='library'" class="invitation-library" aria-labelledby="invitation-title">
                <header class="library-heading">
                    <div><p class="eyebrow"><span class="eyebrow__dot"></span> RUANG UNDANGAN KALIAN</p><h1 id="invitation-title">{{ coupleNames || invitation.title }}</h1><p>{{ dateLabel(workspace.wedding_date) }} <span aria-hidden="true">·</span> Tema {{ selectedTheme.title }}</p></div>
                    <span class="library-status" :class="{ 'library-status--published': invitation.published_at }"><i></i>{{ invitation.published_at ? 'Sudah diterbitkan' : 'Masih draft' }}</span>
                </header>
                <div class="library-hero">
                    <div class="library-copy">
                        <p class="library-kicker">{{ invitation.published_at ? 'SIAP DIBAGIKAN KEPADA TAMU' : 'LANJUTKAN CERITA KALIAN' }}</p>
                        <h2>{{ invitation.title }}</h2>
                        <p class="library-intro">{{ invitation.published_at ? 'Undangan kalian sudah online. Bagikan tautannya dan pantau konfirmasi kehadiran dari satu tempat.' : 'Sampul, cerita, dan detail acara kalian tersimpan di sini. Lanjutkan mengedit sebelum undangan diterbitkan.' }}</p>
                        <div class="library-actions">
                            <RouterLink class="button button--primary" to="/app/invitations/edit"><FaIcon :icon="['fas','pen']" /> Lanjutkan mengedit</RouterLink>
                            <RouterLink class="button button--quiet" :to="{ path: '/app/invitations/edit', hash: '#invitation-theme-picker' }"><FaIcon :icon="['fas','palette']" /> Ubah tema</RouterLink>
                            <RouterLink class="button button--quiet" to="/app/invitations/preview"><FaIcon :icon="['fas','eye']" /> Pratinjau</RouterLink>
                        </div>
                        <div v-if="invitation.published_at" class="library-public-link"><a :href="publicInvitationUrl" target="_blank" rel="noopener noreferrer"><FaIcon :icon="['fas','arrow-up-right-from-square']" /> Lihat halaman publik</a><button type="button" @click="copyLink"><FaIcon :icon="['fas', copied ? 'check' : 'link']" /> {{ copied ? 'Tautan tersalin' : 'Salin tautan' }}</button></div>
                        <p v-if="copied" class="library-copy-feedback" role="status">Tautan undangan berhasil disalin.</p>
                    </div>
                    <figure class="library-cover" :style="{ '--library-cover-bg': selectedTheme.colors[0] }">
                        <img :src="invitation.cover_image_url || selectedTheme.image" :alt="invitation.cover_image_url ? `Foto sampul undangan ${coupleNames}` : `Pratinjau tema ${selectedTheme.title}`" />
                        <div class="library-cover__overlay"><span>UNDANGAN PERNIKAHAN</span><strong>{{ invitation.cover_title || coupleNames }}</strong><small>{{ dateLabel(workspace.wedding_date) }}</small></div>
                        <figcaption><span class="library-theme-swatch" :style="{ backgroundColor: selectedTheme.colors[1] }"></span> Tampilan {{ selectedTheme.title }}</figcaption>
                    </figure>
                </div>
                <section class="library-rsvp" aria-label="Ringkasan konfirmasi tamu">
                    <header><div><p class="eyebrow">KONFIRMASI KEHADIRAN</p><h2>Respons tamu</h2></div><RouterLink to="/app/guests">Kelola daftar tamu <FaIcon :icon="['fas','arrow-right']" /></RouterLink></header>
                    <div v-if="guestSummary" class="library-rsvp__stats"><article><span class="library-rsvp__icon"><FaIcon :icon="['fas','user-group']" /></span><div><small>Hadir</small><strong>{{ guestSummary.attending }}</strong></div></article><article><span class="library-rsvp__icon library-rsvp__icon--pending"><FaIcon :icon="['fas','clock']" /></span><div><small>Menunggu</small><strong>{{ guestSummary.pending }}</strong></div></article><article><span class="library-rsvp__icon library-rsvp__icon--declined"><FaIcon :icon="['fas','xmark']" /></span><div><small>Tidak hadir</small><strong>{{ guestSummary.declined }}</strong></div></article><p v-if="guestSummary.total === 0" class="library-rsvp__empty">Belum ada tamu di daftar. Tambahkan tamu untuk mulai mencatat RSVP.</p></div>
                    <p v-else class="library-rsvp__empty" role="status">Ringkasan tamu belum berhasil dimuat. Buka menu Tamu untuk melihat daftar terbaru.</p>
                </section>
            </section>
            <div v-if="mode==='edit'" class="studio-editor"><header><div><p class="eyebrow">STUDIO UNDANGAN</p><h1 id="invitation-title">Susun undangan kalian.</h1><p>Perubahan tersimpan sebagai draft. Tinjau hasilnya sebelum diterbitkan.</p></div><button class="button button--quiet" type="button" :disabled="saving" @click="openPreview">Simpan & pratinjau <FaIcon :icon="['fas','arrow-right']" /></button></header><form @submit.prevent="save"><section class="cover-photo-field" aria-label="Foto sampul undangan"><div class="cover-photo-field__preview"><img v-if="invitation.cover_image_url" :src="invitation.cover_image_url" alt="Pratinjau foto sampul undangan" /><span v-else><FaIcon :icon="['fas','image']" /> Belum ada foto sampul</span></div><div class="cover-photo-field__copy"><strong>Foto sampul</strong><small>Foto pasangan tampil sebagai latar utama pada undangan publik. JPG, PNG, atau WEBP · Maksimal 8 MB.</small><div><input ref="coverInput" class="sr-only" type="file" accept="image/jpeg,image/png,image/webp" @change="uploadCover" /><button class="button button--quiet" type="button" :disabled="coverSaving" @click="coverInput?.click()">{{ coverSaving ? 'Memproses…' : invitation.cover_image_url ? 'Ganti foto' : 'Pilih foto' }}</button><button v-if="invitation.cover_image_url" class="button button--quiet cover-photo-remove" type="button" :disabled="coverSaving" @click="removeCover">Hapus foto</button></div></div></section><section class="cover-photo-field gallery-upload" aria-label="Galeri foto undangan"><div class="cover-photo-field__copy"><strong>Galeri cerita</strong><small>Tambahkan hingga 12 foto untuk halaman undangan publik. Setiap foto maksimal 8 MB.</small><div><input ref="galleryInput" class="sr-only" type="file" accept="image/jpeg,image/png,image/webp" multiple @change="uploadGallery" /><button class="button button--quiet" type="button" :disabled="coverSaving || galleryImages.length >= 12" @click="galleryInput?.click()">{{ coverSaving ? 'Memproses…' : 'Tambah foto' }}</button></div></div><div v-if="galleryImages.length" class="gallery-upload__grid"><article v-for="image in galleryImages" :key="image.id"><img :src="image.image_url" :alt="image.alt_text || 'Foto cerita pasangan'" /><button type="button" :aria-label="`Hapus ${image.alt_text || 'foto galeri'}`" :disabled="coverSaving" @click="removeGalleryImage(image)"><FaIcon :icon="['fas','xmark']" /></button></article></div><p v-else class="gallery-upload__empty">Belum ada foto galeri.</p></section><p v-if="coverError" class="guest-error" role="alert">{{ coverError }}</p><label>Nama undangan<input v-model.trim="form.title" maxlength="160" required placeholder="Nama undangan" /></label><fieldset id="invitation-theme-picker" class="theme-picker"><legend>Tema undangan</legend><div class="theme-picker__grid"><button v-for="theme in themes" :key="theme.key" type="button" class="theme-choice" :class="[`theme-choice--${theme.key}`, { 'theme-choice--active': form.theme_key === theme.key }]" :aria-pressed="form.theme_key === theme.key" @click="form.theme_key = theme.key"><span class="theme-choice__image" :style="{ backgroundImage: `linear-gradient(180deg, #f6eee91a, #66513b42), url('${theme.image}')` }"><InvitationOrnaments :theme="theme.key" placement="cover" /><FaIcon v-if="form.theme_key === theme.key" :icon="['fas', 'check']" /></span><span class="theme-choice__copy"><strong>{{ theme.title }}</strong><small>{{ theme.detail }}</small></span></button></div></fieldset><label>Nama pada sampul<input v-model.trim="form.cover_title" maxlength="160" placeholder="Nama pasangan" /></label><label>Sapaan tamu<textarea v-model.trim="form.greeting" maxlength="240" rows="2" /></label><label>Cerita kalian<textarea v-model.trim="form.story" maxlength="3000" rows="5" placeholder="Ceritakan perjalanan kalian..." /></label><label>Kutipan pilihan <em>Opsional</em><input v-model.trim="form.quote" maxlength="300" /></label><fieldset class="motion-settings"><legend>Gerakan undangan</legend><label>Preset motion<select v-model="form.motion_preset"><option value="theme">Ikuti karakter tema</option><option value="editorial">Editorial — lembut dan berlapis</option><option value="garden">Garden — organik dan mengalir</option><option value="cinematic">Cinematic — dramatis dan tenang</option><option value="minimal">Minimal — ringan dan singkat</option></select></label><label class="motion-settings__toggle"><input v-model="form.decorative_effects" type="checkbox" /><span><strong>Efek dekoratif</strong><small>Aktifkan aksen gerak halus dan parallax pada ornamen serta foto.</small></span></label><p>Preferensi ini berlaku sama pada pratinjau dan undangan publik. Gerakan otomatis mengikuti tema jika preset tema dipilih.</p></fieldset><label>Penutup<textarea v-model.trim="form.closing" maxlength="240" rows="2" /></label><p v-if="formError" class="guest-error" role="alert">{{ formError }}</p><p v-if="saveSuccess" class="studio-success" role="status">{{ saveSuccess }}</p><footer><button class="button button--primary" type="submit" :disabled="saving">{{ saving ? 'Menyimpan…' : 'Simpan draft' }}</button></footer></form></div>
            <div v-if="mode==='preview'" class="invite-preview"><header class="preview-toolbar"><div><p class="eyebrow">PRATINJAU UNDANGAN</p><h1 id="invitation-title">{{ invitation.title }}</h1></div><div class="preview-toolbar__actions"><button class="button button--quiet" type="button" @click="previewReplayKey++"><FaIcon :icon="['fas','rotate-right']" /> Putar ulang</button><RouterLink class="button button--quiet" to="/app/invitations/edit">Kembali ke editor</RouterLink></div></header><InvitationExperience v-if="previewInvitation" :key="previewReplayKey" :invitation="previewInvitation" :preview="true" /><div class="preview-publish"><p>{{ invitation.published_at ? 'Undangan sudah terbit. Kalian dapat membatalkan penerbitannya kapan saja.' : 'Terbitkan hanya setelah isi, tanggal, dan lokasi sudah diperiksa.' }}</p><button class="button button--primary" @click="publishConfirm=true">{{ invitation.published_at ? 'Kelola penerbitan' : 'Terbitkan undangan' }}</button></div></div>
            <section v-if="mode==='share'" class="share-page"><header><p class="eyebrow">TAUTAN UNDANGAN</p><h1 id="invitation-title">Bagikan momen ini.</h1><p>Tautan umum hanya menampilkan isi undangan dan acara. Tautan RSVP personal membatasi konfirmasi ke satu tamu.</p></header><div class="share-general"><div><span>TAUTAN UMUM</span><strong>{{ baseUrl }}/w/{{ invitation.slug }}</strong></div><button class="button button--primary" @click="copyLink">{{ copied ? 'Tersalin' : 'Salin tautan' }}</button></div><section class="share-guests"><h2>Tautan RSVP personal</h2><p>Tautan ini dibuat khusus untuk seorang tamu dan dapat digunakan untuk mengubah jawabannya.</p><article v-for="guest in guests" :key="guest.id"><div><strong>{{ guest.name }}</strong><small>{{ guest.email || guest.phone || 'Kontak belum dicatat' }} · {{ guest.rsvp_status==='attending'?'Hadir':guest.rsvp_status==='declined'?'Tidak hadir':'Menunggu' }}</small><div v-if="guestLinks[guest.id]" class="share-guest-link"><label :for="`guest-link-${guest.id}`">Tautan RSVP personal</label><input :id="`guest-link-${guest.id}`" :value="guestLinks[guest.id]" readonly @focus="$event.target.select()" /></div></div><div class="share-guest-actions"><button v-if="guestLinks[guest.id]" class="button button--quiet" @click="copyGuestLink(guest)">{{ copied?'Tersalin':'Salin link' }}</button><button v-else class="button button--quiet" @click="makeGuestLink(guest)">Buat tautan</button></div></article><div v-if="!guests.length" class="guest-empty-note">Tambahkan tamu terlebih dahulu di menu Tamu.</div></section></section>
        </template>
        <Transition name="overlay" appear>
        <div v-if="publishConfirm" class="confirm-backdrop"><section class="confirm-dialog" role="alertdialog" aria-modal="true"><h2>{{ invitation.published_at ? 'Kelola penerbitan' : 'Terbitkan undangan?' }}</h2><p>{{ invitation.published_at ? 'Undangan akan kembali menjadi draft dan tidak dapat dibuka publik.' : 'Halaman akan bisa dibuka siapa saja yang memiliki tautan. Pastikan nama, tanggal, dan lokasi sudah sesuai.' }}</p><div><button class="button button--quiet" @click="publishConfirm=false">Batal</button><button class="button button--primary" :disabled="saving" @click="publish(!invitation.published_at)">{{ invitation.published_at ? 'Kembali ke draft' : 'Terbitkan' }}</button></div></section></div>
        </Transition>
        <ConfirmDialog :open="confirmOpen" :title="confirmOptions.title" :message="confirmOptions.message" :confirm-label="confirmOptions.confirmLabel" :destructive="confirmOptions.destructive" @cancel="finishConfirm(false)" @confirm="finishConfirm(true)" />
    </section>
</template>

<style scoped>
.invitation-studio{max-width:1180px;margin:auto;padding:38px clamp(20px,4vw,56px) 70px;color:#30362e}.studio-heading{max-width:750px;margin:35px auto 34px;text-align:center}.studio-heading h1,.studio-editor h1,.share-page h1{font:500 clamp(42px,5vw,58px)/1.02 var(--font-display);margin:14px 0 12px}.studio-heading>p:last-child,.studio-editor header>p:last-child,.share-page header>p:last-child{font-size:14px;line-height:1.7;color:#5f675c}.theme-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}.theme-card{background:#fff;border:1px solid #e5e6df;border-radius:16px;overflow:hidden}.theme-card__preview{height:230px;background:var(--theme-bg);color:var(--theme-accent);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;position:relative}.theme-card__preview:before,.theme-card__preview:after{content:'';position:absolute;width:130px;height:130px;border:1px solid var(--theme-detail);border-radius:50%;opacity:.45}.theme-card__preview:before{left:-40px;top:-40px}.theme-card__preview:after{right:-40px;bottom:-40px}.theme-card__leaf{font:italic 36px var(--font-display)}.theme-card__preview small{font-size:9px;letter-spacing:2px}.theme-card__preview strong{font:500 33px var(--font-display);text-align:center;max-width:90%}.theme-card__preview i{font:italic 13px var(--font-display)}.theme-card__body{display:flex;justify-content:space-between;align-items:center;padding:16px 19px;gap:12px}.theme-card__body h2{font:500 24px var(--font-display);margin:0 0 3px}.theme-card__body p{font-size:11px;color:#777e73;margin:0}.theme-card__body .button{font-size:11px;padding:11px 13px}.studio-top{display:flex;justify-content:space-between;align-items:center}.studio-back{font-size:12px;color:#687263;text-decoration:none}.studio-status{font-size:11px;color:#8a7852;display:inline-flex;align-items:center;gap:7px}.studio-status i{width:7px;height:7px;border-radius:50%;background:#d7aa58}.studio-status--published{color:#526c4b}.studio-status--published i{background:#5d8053}.studio-heading--existing{margin:48px auto}.studio-actions{display:flex;justify-content:center;gap:9px;margin-top:22px}.studio-editor{max-width:790px;margin:38px auto}.studio-editor>header{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-bottom:25px}.studio-editor>header h1{margin:12px 0 6px}.studio-editor>header p:last-child{font-size:13px;color:#5f675c}.studio-editor form{background:#fff;border:1px solid #e5e6df;border-radius:16px;padding:24px;display:flex;flex-direction:column;gap:16px}.studio-editor label{display:flex;flex-direction:column;gap:7px;font-size:12px;font-weight:600;color:#50564d}.studio-editor input,.studio-editor textarea,.studio-editor select{font:13px inherit;border:1px solid #dedfd7;border-radius:9px;background:#fff;padding:12px;color:#30362e}.studio-editor label em{font-weight:400;color:#80867d}.studio-editor footer{display:flex;justify-content:flex-end;border-top:1px solid #eee;padding-top:17px}.studio-success{background:#e9f2e6;border-radius:9px;color:#43623b;font-size:12px;margin:0;padding:12px 14px}.preview-toolbar{display:flex;align-items:flex-end;justify-content:space-between;gap:16px}.preview-toolbar h1{font:500 26px var(--font-display);margin:5px 0}.preview-letter{max-width:680px;margin:22px auto 18px;border-radius:18px;background:#e9eee5;padding:44px clamp(25px,7vw,72px);text-align:center;min-height:520px;color:#536a54;box-shadow:0 12px 35px #20251b12}.preview-letter--classic{background:#f4eee3;color:#795b43}.preview-letter--modern{background:#f4f2ef;color:#424744}.preview-letter--terracotta{background:#f0e4db;color:#8d5745}.preview-letter>p:first-child,.preview-events>.eyebrow{font-size:9px;letter-spacing:1.8px}.preview-letter h2{font:500 42px/1.04 var(--font-display);margin:25px 0 10px}.preview-letter>span{font-size:12px}.preview-letter__rule{height:1px;background:currentColor;opacity:.23;margin:26px auto;max-width:100px}.preview-letter>p{font-size:13px;line-height:1.8}.preview-letter blockquote{font:italic 21px var(--font-display);margin:22px 0}.preview-letter__story{white-space:pre-wrap}.preview-events{margin:27px 0;padding:20px 0;border-block:1px solid currentColor}.preview-events article{display:flex;flex-direction:column;gap:6px;margin:13px 0}.preview-events article strong{font:500 24px var(--font-display)}.preview-events article span,.preview-events article small{font-size:11px}.preview-publish{max-width:680px;margin:auto;background:#fff;border:1px solid #e7e6df;border-radius:12px;padding:17px;display:flex;justify-content:space-between;align-items:center;gap:15px}.preview-publish p{font-size:12px;color:#737a70;line-height:1.6}.share-page{max-width:820px;margin:45px auto}.share-page header h1{margin:10px 0}.share-general{background:#fff;border:1px solid #e6e6df;border-radius:14px;padding:18px;display:flex;justify-content:space-between;align-items:center;gap:15px}.share-general div{display:flex;flex-direction:column;gap:6px;min-width:0}.share-general span{font-size:10px;letter-spacing:1px;color:#778072}.share-general strong{font-size:13px;word-break:break-all}.share-guests{margin-top:28px}.share-guests h2{font:500 30px var(--font-display);margin:0 0 5px}.share-guests>p{font-size:12px;color:#5f675c;margin-bottom:15px}.share-guests article{background:#fff;border:1px solid #e7e6df;border-radius:12px;padding:13px 16px;display:flex;justify-content:space-between;align-items:center;gap:12px;margin:8px 0}.share-guests article>div{display:flex;flex-direction:column;gap:5px}.share-guests article strong{font-size:13px}.share-guests article small,.guest-empty-note{font-size:11px;color:#626b60}.share-guests article .button{white-space:nowrap}.guest-empty-note{padding:19px;background:#fff;border:1px dashed #d9ddd3;border-radius:11px}.confirm-dialog p{line-height:1.65}@media(max-width:720px){.invitation-studio{padding:25px 16px 50px}.theme-grid{grid-template-columns:1fr}.theme-card__preview{height:205px}.theme-card__body{align-items:flex-start;flex-direction:column}.studio-heading h1,.studio-editor h1,.share-page h1{font-size:42px}.studio-actions{flex-wrap:wrap}.studio-editor>header{align-items:flex-start;flex-direction:column}.preview-toolbar{align-items:flex-start;flex-direction:column}.preview-publish{align-items:flex-start;flex-direction:column}.share-general{align-items:flex-start;flex-direction:column}.share-guests article{align-items:flex-start;flex-direction:column}.preview-letter{padding:32px 20px}.preview-letter h2{font-size:36px}}
</style>

<style scoped>
.share-guests article > div:first-child { min-width: 0; flex: 1; }
.share-guest-link { display: flex; flex-direction: column; gap: 5px; margin-top: 8px; max-width: 100%; }
.share-guest-link label { color: #5f675c; font-size: 10px; }
.share-guest-link input { width: min(560px, 100%); border: 1px solid #e1e3dc; border-radius: 7px; background: #f8f8f4; color: #4a5146; font: 11px var(--font-body); padding: 9px 10px; }
.share-guest-link input:focus { outline: 2px solid #83917a; outline-offset: 1px; }
.share-guest-actions { display: flex; align-items: center; gap: 8px; }
@media (max-width: 720px) { .share-guests article { align-items: flex-start; flex-direction: column; } .share-guest-link { width: 100%; } .share-guest-link input { width: 100%; } }
</style>

<style scoped>
.theme-card__preview{background-color:var(--theme-bg);background-image:linear-gradient(180deg,#17201418,#172014a3),var(--theme-image);background-position:center;background-size:cover;color:#fff;text-shadow:0 1px 9px #0008}.theme-card__preview small,.theme-card__preview strong,.theme-card__preview i{position:relative;z-index:1}.theme-picker{border:0;margin:0;padding:0}.theme-picker legend{color:#50564d;font-size:12px;font-weight:600;margin-bottom:9px;padding:0}.theme-picker__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.theme-choice{background:#fff;border:1px solid #e1e3dc;border-radius:11px;color:#30362e;cursor:pointer;display:flex;flex-direction:column;font:inherit;min-width:0;overflow:hidden;padding:0;text-align:left;transition:border-color .15s,box-shadow .15s}.theme-choice:hover{border-color:#aab6a3}.theme-choice--active{border-color:#536a54;box-shadow:0 0 0 2px #536a5425}.theme-choice__image{align-items:flex-start;background-position:center;background-size:cover;color:#fff;display:flex;height:105px;justify-content:flex-end;padding:9px;width:100%}.theme-choice__image svg{background:#536a54;border:2px solid #fff;border-radius:50%;font-size:10px;padding:3px}.theme-choice__copy{display:flex;flex-direction:column;gap:3px;padding:10px 11px}.theme-choice__copy strong{font:600 11px var(--font-body)}.theme-choice__copy small{color:#777e73;font-size:9px;line-height:1.45}
@media(max-width:600px){.theme-picker__grid{grid-template-columns:1fr 1fr;gap:8px}.theme-choice__image{height:84px}.theme-choice__copy{padding:9px}.theme-choice__copy small{font-size:8px}}
</style>

<style scoped>
.cover-photo-field.gallery-upload{display:flex;flex-direction:column;align-items:stretch}.gallery-upload__grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px}.gallery-upload__grid article{height:104px;position:relative;overflow:hidden;border-radius:8px}.gallery-upload__grid img{width:100%;height:100%;object-fit:cover}.gallery-upload__grid button{position:absolute;right:6px;top:6px;width:28px;height:28px;border:0;border-radius:50%;background:#fff;color:#97443e;cursor:pointer}.gallery-upload__empty{margin:0;color:#626b60;font-size:11px}.cover-photo-field{display:grid;grid-template-columns:112px minmax(0,1fr);align-items:center;gap:16px;padding:14px;background:#f3f4ee;border:1px solid #e4e6dd;border-radius:12px}.cover-photo-field__preview{height:132px;overflow:hidden;border-radius:9px;background:#e5e9e0;color:#778071;display:grid;place-items:center}.cover-photo-field__preview img{width:100%;height:100%;object-fit:cover}.cover-photo-field__preview span{display:flex;flex-direction:column;align-items:center;gap:7px;font-size:10px;text-align:center;padding:8px}.cover-photo-field__preview span svg{font-size:21px}.cover-photo-field__copy{display:flex;flex-direction:column;gap:7px}.cover-photo-field__copy>strong{font-size:12px;color:#3e483a}.cover-photo-field__copy>small{font-size:10px;color:#72796e;line-height:1.6}.cover-photo-field__copy>div{display:flex;flex-wrap:wrap;gap:8px;margin-top:3px}.cover-photo-field .button{font-size:10px;min-height:36px;padding:8px 11px}.cover-photo-remove{color:#9c4942}.preview-letter{background-position:center;background-size:cover}.preview-letter--with-image{color:#fff}.preview-letter--with-image .eyebrow,.preview-letter--with-image .preview-unpublished{color:#fff}.preview-letter--with-image .preview-events article{background:#17201475;border-color:#ffffff65}
@media(max-width:600px){.gallery-upload__grid{grid-template-columns:repeat(3,minmax(0,1fr))}.gallery-upload__grid article{height:94px}.cover-photo-field{grid-template-columns:82px minmax(0,1fr);gap:11px;padding:10px}.cover-photo-field__preview{height:112px}.cover-photo-field__copy>div{gap:6px}.cover-photo-field .button{font-size:9px;padding:8px}}
</style>

<style scoped>
.invitation-library{max-width:1120px;margin:0 auto;padding:37px clamp(20px,4vw,48px) 64px}.library-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:25px}.library-heading .eyebrow{margin:0 0 8px}.library-heading h1{font:500 clamp(36px,4.4vw,52px)/1.02 var(--font-display);letter-spacing:-.5px;margin:0;color:#293027}.library-heading>div>p:last-child{font-size:12px;color:#626b60;margin:8px 0 0}.library-status{display:inline-flex;align-items:center;gap:8px;white-space:nowrap;border:1px solid #e8dfc7;background:#fbf6e9;border-radius:99px;color:#735d2e;padding:9px 13px;font-size:11px;font-weight:700}.library-status i{width:8px;height:8px;border-radius:50%;background:#c69b45}.library-status--published{border-color:#d6e2d0;background:#edf4e9;color:#355532}.library-status--published i{background:#4f7747}.library-hero{display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,.78fr);align-items:center;gap:clamp(26px,5vw,62px);padding:clamp(25px,4vw,43px);border:1px solid #e5e5db;border-radius:22px;background:radial-gradient(ellipse at 0% 0%,#e8eddf 0,transparent 48%),linear-gradient(125deg,#fff 0%,#fbfaf6 100%);box-shadow:0 14px 38px rgb(42 51 38 / 5%)}.library-copy{max-width:570px}.library-kicker{color:#526c46;font-size:10px;font-weight:700;letter-spacing:1.65px;margin:0 0 13px}.library-copy h2{font:500 clamp(31px,3.6vw,43px)/1.08 var(--font-display);color:#293027;margin:0 0 12px}.library-intro{font-size:13px;line-height:1.8;color:#5f675c;max-width:475px;margin:0}.library-actions{display:flex;flex-wrap:wrap;gap:9px;margin-top:24px}.library-actions .button{min-height:43px}.library-public-link{display:flex;flex-wrap:wrap;align-items:center;gap:19px;margin-top:20px}.library-public-link>a,.library-public-link>button{display:inline-flex;align-items:center;gap:7px;color:#405a39;font-size:11px;font-weight:700}.library-public-link>button{border:0;background:none;padding:0;cursor:pointer;font:inherit}.library-public-link>a:hover,.library-public-link>button:hover{color:#32492f;text-decoration:underline;text-underline-offset:3px}.library-copy-feedback{font-size:11px;color:#355532;margin:8px 0 0}.library-cover{position:relative;isolation:isolate;overflow:hidden;aspect-ratio: .86;max-height:425px;margin:0;border-radius:17px;background:var(--library-cover-bg);box-shadow:0 15px 35px rgb(31 40 27 / 17%)}.library-cover>img{position:absolute;inset:0 0 43px;width:100%;height:calc(100% - 43px);object-fit:cover}.library-cover:before{content:"";position:absolute;z-index:1;inset:0 0 43px;background:linear-gradient(180deg,rgb(24 31 22 / 4%) 18%,rgb(24 31 22 / 12%) 45%,rgb(24 31 22 / 72%) 100%)}.library-cover__overlay{position:absolute;z-index:2;inset:auto 20px 68px;display:flex;flex-direction:column;align-items:center;text-align:center;color:#fff;text-shadow:0 2px 14px rgb(0 0 0 / 40%)}.library-cover__overlay span{font-size:8px;font-weight:700;letter-spacing:2px}.library-cover__overlay strong{font:500 clamp(24px,3vw,36px)/1.05 var(--font-display);margin:12px 0 8px}.library-cover__overlay small{font-size:10px;letter-spacing:.5px}.library-cover figcaption{position:absolute;z-index:3;bottom:0;left:0;right:0;height:43px;display:flex;align-items:center;gap:8px;padding:0 14px;background:#fff;color:#555e51;font-size:10px;font-weight:600}.library-theme-swatch{width:9px;height:9px;border:1px solid rgb(0 0 0 / 10%);border-radius:50%}.library-rsvp{margin-top:24px;padding:22px 25px;border:1px solid #e5e5db;border-radius:16px;background:#fff}.library-rsvp>header{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:17px}.library-rsvp .eyebrow{font-size:9px;margin:0 0 4px}.library-rsvp h2{font:500 25px var(--font-display);margin:0;color:#293027}.library-rsvp>header>a{display:inline-flex;align-items:center;gap:8px;color:#405a39;font-size:11px;font-weight:700}.library-rsvp__stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.library-rsvp__stats>article{display:flex;align-items:center;gap:12px;min-height:67px;padding:11px 13px;border:1px solid #eeeee7;border-radius:11px;background:#fbfbf8}.library-rsvp__icon{display:grid;place-items:center;flex:0 0 37px;width:37px;height:37px;border-radius:50%;background:#e7efe2;color:#405a39}.library-rsvp__icon--pending{background:#f6f0df;color:#82672e}.library-rsvp__icon--declined{background:#f7e9e6;color:#96554b}.library-rsvp__stats article div{display:flex;flex-direction:column;gap:3px}.library-rsvp__stats article small{font-size:10px;color:#626b60}.library-rsvp__stats article strong{font:600 22px/1 var(--font-display);color:#293027}.library-rsvp__empty{grid-column:1/-1;margin:0;padding:14px 2px 2px;color:#626b60;font-size:11px;line-height:1.6}
@media(max-width:760px){.invitation-library{padding:25px 16px 45px}.library-heading{align-items:flex-start;flex-direction:column;gap:14px}.library-hero{grid-template-columns:1fr;gap:25px;padding:22px;border-radius:17px}.library-copy{max-width:none}.library-actions{gap:8px}.library-actions .button{flex:1 1 auto}.library-cover{width:min(100%,420px);justify-self:center;aspect-ratio:.9}.library-rsvp{padding:19px 17px}.library-rsvp__stats{grid-template-columns:1fr}.library-rsvp__stats>article{min-height:60px}}
@media(max-width:420px){.library-actions{display:grid;grid-template-columns:1fr 1fr}.library-actions .button:first-child{grid-column:1/-1}.library-public-link{gap:14px}.library-rsvp>header{align-items:flex-start;flex-direction:column}}
</style>

<style scoped>
.preview-toolbar__actions{display:flex;gap:8px;flex-wrap:wrap}.motion-settings{display:grid;gap:13px;margin:2px 0;padding:17px;border:1px solid #e4e6dd;border-radius:12px;background:#f8f9f5}.motion-settings legend{padding:0 6px;color:#424b3f;font-size:12px;font-weight:700}.motion-settings>label{margin:0}.motion-settings__toggle{display:flex!important;flex-direction:row!important;align-items:flex-start;gap:10px!important;cursor:pointer}.motion-settings__toggle input{width:17px;height:17px;margin:2px 0 0;accent-color:#536a54}.motion-settings__toggle span{display:flex;flex-direction:column;gap:4px}.motion-settings__toggle strong{font-size:12px}.motion-settings__toggle small,.motion-settings>p{margin:0;color:#747c70;font-size:10px;line-height:1.6}.motion-settings>p{border-top:1px solid #e8eae3;padding-top:10px}
</style>

<style scoped>
.theme-card__preview{isolation:isolate;background-position:center 34%!important;background-size:cover!important}.theme-card__ornament{position:absolute;inset:0;width:100%;height:100%;opacity:.34;color:var(--theme-detail);pointer-events:none}.theme-card__leaf,.theme-card__preview small,.theme-card__preview strong,.theme-card__preview i{z-index:1}.theme-card--classic .theme-card__preview{background-image:linear-gradient(180deg,#f7eddd18,#593c3270),radial-gradient(ellipse at center,#bd995744,transparent 53%),var(--theme-image);background-color:#b89567}.theme-card--modern .theme-card__preview{background-image:linear-gradient(132deg,#e7dce344,#47425258),linear-gradient(45deg,#b35f6125 0 22%,transparent 22% 78%,#d5ab6e3a 78%),var(--theme-image);background-color:#d9c3c3}.theme-card--terracotta .theme-card__preview{background-image:linear-gradient(180deg,#f7e8db10,#6a403e42),radial-gradient(ellipse at 70% 25%,#edbd8755,transparent 55%),var(--theme-image);background-color:#eacaba}.theme-card--botanical .theme-card__preview{background-image:linear-gradient(180deg,#e9eee512,#3448333e),radial-gradient(ellipse at 30% 35%,#e4cfb244,transparent 60%),var(--theme-image);background-color:#d6d8bd}.theme-choice__image{position:relative;isolation:isolate;background-position:center 35%!important;background-size:cover!important}.theme-choice__image>.invitation-ornament{position:absolute;inset:0;width:100%;height:100%;opacity:.32;pointer-events:none}.theme-choice__image>svg:not(.invitation-ornament){position:relative;z-index:2}
</style>
