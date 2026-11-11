<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { rememberUser } from '../../session';

const route = useRoute();
const router = useRouter();
const invitation = ref(null);
const busy = ref(false);
const error = ref('');
onMounted(async () => {
    try {
        const { data } = await axios.get(`/api/invitations/${encodeURIComponent(route.params.token)}`);
        invitation.value = data;
    } catch (failure) {
        error.value = failure.response?.data?.message ?? 'Undangan ini tidak valid atau sudah kedaluwarsa.';
    }
});

async function accept() {
    busy.value = true;
    error.value = '';
    try {
        await axios.post(`/api/invitations/${encodeURIComponent(route.params.token)}/accept`);
        const { data } = await axios.get('/api/auth/me');
        rememberUser(data.user);
        router.push('/app/home');
    } catch (failure) {
        error.value = failure.response?.data?.errors?.invitation?.[0]
            ?? failure.response?.data?.message
            ?? 'Undangan belum bisa diterima. Coba lagi.';
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <main class="invite-accept-page">
        <RouterLink to="/welcome" class="brand" aria-label="MENUJU"><span class="brand__mark">m</span><span class="brand__name">menuju</span></RouterLink>
        <section class="invite-accept-card">
            <div class="auth-mark auth-mark--success" aria-hidden="true"><FaIcon :icon="['fas', 'people-group']" /></div>
            <p class="eyebrow">UNDANGAN PARTNER</p>
            <template v-if="invitation">
                <h1>Bersiap bersama.</h1>
                <p class="auth-description">Kamu diundang untuk bergabung ke workspace <strong>{{ invitation.workspace_name }}</strong> sebagai partner persiapan.</p>
                <div class="workspace-summary"><span>{{ invitation.workspace_name }}</span><strong>Undangan ditujukan ke {{ invitation.email }}</strong></div>
                <p v-if="invitation.email_matches === false" class="form-alert form-alert--error">Undangan ini ditujukan ke email lain. Keluar lalu masuk menggunakan alamat email yang tercantum.</p>
                <p v-if="error" class="form-alert form-alert--error" role="alert">{{ error }}</p>
                <button class="button button--primary auth-submit" type="button" :disabled="busy" @click="accept">{{ busy ? 'Memproses…' : 'Terima undangan' }} <FaIcon :icon="['fas', 'arrow-right']" aria-hidden="true" /></button>
            </template>
            <template v-else>
                <h1>Memeriksa undangan.</h1>
                <p v-if="error" class="form-alert form-alert--error" role="alert">{{ error }}</p>
                <p v-else class="auth-description">Tunggu sebentar, kami sedang memastikan tautan ini masih berlaku.</p>
                <RouterLink v-if="error" to="/welcome" class="button button--quiet auth-submit">Kembali ke MENUJU</RouterLink>
            </template>
        </section>
        <p class="onboarding-footer">Data workspace hanya dapat dilihat anggota yang sudah diterima.</p>
    </main>
</template>
