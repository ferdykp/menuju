<script setup>
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import InvitationExperience from './InvitationExperience.vue';

const route = useRoute();
const invitation = ref(null);
const loading = ref(true);
const unavailable = ref(false);
const invalidGuestLink = ref(false);
const submitting = ref(false);
const result = ref('');
const formError = ref('');
const guestToken = computed(() => typeof route.query.guest === 'string' ? route.query.guest : '');
async function load() {
    loading.value = true;
    unavailable.value = false; invalidGuestLink.value = false; result.value = ''; formError.value = '';
    try {
        invitation.value = (await axios.get(`/api/public/invitations/${route.params.slug}`, { params: guestToken.value ? { guest: guestToken.value } : {} })).data.invitation;
        invalidGuestLink.value = Boolean(guestToken.value) && !invitation.value?.guest_name;
    }
    catch { unavailable.value = true; invitation.value = null; }
    finally { loading.value = false; }
}
async function sendRsvp(values) {
    submitting.value = true; formError.value = '';
    try {
        const { data } = await axios.post(`/api/public/invitations/${route.params.slug}/rsvp`, { guest_token: guestToken.value, ...values });
        result.value = data.message;
    } catch (e) {
        const firstError = Object.values(e.response?.data?.errors ?? {}).flat()[0];
        const rawValidationKey = typeof firstError === 'string' && firstError.startsWith('validation.');
        formError.value = e.response?.status === 404 || rawValidationKey
            ? 'Tautan RSVP tidak berlaku atau sudah tidak aktif. Minta pasangan mengirimkan tautan terbaru.'
            : firstError ?? e.response?.data?.message ?? 'Konfirmasi belum berhasil dikirim. Periksa tautan dan coba lagi.';
    }
    finally { submitting.value = false; }
}
watch(() => [route.params.slug, route.query.guest], load, { immediate: true });
</script>

<template>
    <main v-if="loading" class="public-loading" aria-label="Memuat undangan"><span class="public-loader"></span><p>Membuka undangan…</p></main>
    <main v-else-if="unavailable || !invitation" class="public-unavailable"><div class="public-unavailable__mark">m</div><p class="eyebrow">UNDANGAN DIGITAL</p><h1>Undangan belum tersedia.</h1><p>Periksa kembali tautan yang diberikan oleh pasangan.</p></main>
    <InvitationExperience v-else :invitation="invitation" :guest-token="guestToken" :invalid-guest-link="invalidGuestLink" :submitting="submitting" :result="result" :form-error="formError" @submit-rsvp="sendRsvp" />
</template>

<style scoped>
.public-unavailable,.public-loading{min-height:100svh;background:#f7f5ef;display:flex;align-items:center;justify-content:center;flex-direction:column;text-align:center;padding:25px;color:#40483e}.public-unavailable__mark{width:46px;height:46px;border-radius:13px;background:#596b53;color:#fff;display:grid;place-items:center;font:italic 30px Georgia,serif;margin-bottom:24px}.public-unavailable h1{font:500 46px Georgia,serif;margin:15px 0}.public-unavailable p:last-child,.public-loading p{font-size:13px;color:#777d73}.public-loader{width:26px;height:26px;border:2px solid #dce2d7;border-top-color:#596b53;border-radius:50%;animation:spin .7s linear infinite}@keyframes spin{to{transform:rotate(360deg)}}
</style>
