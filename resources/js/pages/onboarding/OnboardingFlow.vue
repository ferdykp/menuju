<script setup>
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { getRememberedUser, rememberUser } from '../../session';
import { useToast } from '../../composables/toastBus';

const props = defineProps({ step: { type: String, required: true } });
const router = useRouter();
const { success: notifySuccess } = useToast();
const busy = ref(false);
const errorSummary = ref('');
const errors = ref({});

const state = reactive({
    name: '',
    partner_one_name: '',
    partner_two_name: '',
    wedding_date: '',
    timezone: 'Asia/Jakarta',
    workspace_id: '',
    partner_email: '',
});

const steps = ['workspace', 'couple', 'invite', 'done'];
const stepNumber = computed(() => steps.indexOf(props.step) + 1);
const title = computed(() => ({
    workspace: 'Beri nama ruang persiapan kalian.',
    couple: 'Ceritakan sedikit tentang kalian.',
    invite: 'Ajak pasanganmu bergabung.',
    done: 'Ruang kalian sudah siap.',
}[props.step]));
const description = computed(() => ({
    workspace: 'Semua rencana, catatan, dan keputusan akan tersimpan di sini.',
    couple: 'Nama dan tanggal ini bisa diubah kapan saja dari pengaturan.',
    invite: 'Kalian bisa mengatur semuanya bersama. Undangan partner dapat dikirim sekarang atau nanti.',
    done: 'Mulai dari satu langkah kecil. MENUJU akan membantu kalian tetap tahu apa yang perlu dilakukan berikutnya.',
}[props.step]));

function restore() {
    try {
        const saved = JSON.parse(sessionStorage.getItem('menuju-onboarding') || '{}');
        Object.assign(state, saved);
        const existingWorkspace = getRememberedUser()?.workspaces?.[0];
        if (existingWorkspace && !state.workspace_id) state.workspace_id = String(existingWorkspace.id);
        if (existingWorkspace) {
            state.name ||= existingWorkspace.name;
            state.partner_one_name ||= existingWorkspace.partner_one_name ?? '';
            state.partner_two_name ||= existingWorkspace.partner_two_name ?? '';
            state.wedding_date ||= existingWorkspace.wedding_date ?? '';
        }
    } catch {
        sessionStorage.removeItem('menuju-onboarding');
    }
}

function persist() {
    sessionStorage.setItem('menuju-onboarding', JSON.stringify(state));
}

restore();
watch(state, persist, { deep: true });

async function saveCouple() {
    busy.value = true;
    errors.value = {};
    errorSummary.value = '';
    try {
        const { data } = await axios.post('/api/workspaces', {
            name: state.name,
            partner_one_name: state.partner_one_name,
            partner_two_name: state.partner_two_name,
            wedding_date: state.wedding_date || null,
            timezone: state.timezone,
        });
        state.workspace_id = String(data.workspace.id);
        const current = getRememberedUser();
        if (current) rememberUser({ ...current, workspaces: [...(current.workspaces ?? []), data.workspace] });
        persist();
        await router.push('/onboarding/invite');
    } catch (error) {
        errors.value = error.response?.data?.errors ?? {};
        errorSummary.value = error.response?.data?.message ?? 'Workspace belum berhasil dibuat. Coba lagi.';
    } finally {
        busy.value = false;
    }
}

async function finish(skipInvite = false) {
    busy.value = true;
    errors.value = {};
    errorSummary.value = '';
    try {
        if (!skipInvite && state.partner_email.trim()) {
            await axios.post(`/api/workspaces/${state.workspace_id}/invites`, { email: state.partner_email.trim() });
        }
        const { data } = await axios.post(`/api/workspaces/${state.workspace_id}/complete`);
        const current = getRememberedUser();
        if (current) rememberUser({
            ...current,
            workspaces: (current.workspaces ?? []).map((workspace) => String(workspace.id) === String(data.workspace.id) ? data.workspace : workspace),
        });
        sessionStorage.removeItem('menuju-onboarding');
        notifySuccess('Workspace kalian siap. Selamat merencanakan bersama!');
        await router.push('/onboarding/done');
    } catch (error) {
        errors.value = error.response?.data?.errors ?? {};
        errorSummary.value = error.response?.data?.message ?? 'Belum berhasil menyelesaikan langkah ini. Coba lagi.';
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <main class="onboarding-page">
        <header class="onboarding-header">
            <RouterLink to="/welcome" class="brand" aria-label="Kembali ke MENUJU"><img class="brand__mark" :src="'/images/brand/menuju-logo.png'" alt=""><span class="brand__name">menuju</span></RouterLink>
            <div class="onboarding-progress" :aria-label="`Langkah ${stepNumber} dari 4`">
                <span v-for="n in 4" :key="n" :class="{ 'onboarding-progress__step--active': n <= stepNumber }"></span>
            </div>
            <span class="onboarding-exit">Langkah {{ stepNumber }} dari 4</span>
        </header>

        <section class="onboarding-card">
            <div class="onboarding-card__ornament" aria-hidden="true"><span></span><span></span><span></span></div>
            <div class="eyebrow"><span class="eyebrow__dot"></span> LANGKAH {{ String(stepNumber).padStart(2, '0') }} DARI 04</div>
            <h1>{{ title }}</h1>
            <p class="auth-description">{{ description }}</p>

            <form v-if="step === 'workspace'" class="auth-form onboarding-form" @submit.prevent="router.push('/onboarding/couple')">
                <label class="field">
                    <span>Nama workspace</span>
                    <input v-model.trim="state.name" autocomplete="organization" placeholder="Contoh: Ayu & Bima" maxlength="120" required>
                    <small class="field-hint">Nama ini hanya terlihat oleh kalian berdua.</small>
                </label>
                <button class="button button--primary auth-submit" type="submit">Lanjut <FaIcon :icon="['fas', 'arrow-right']" aria-hidden="true" /></button>
            </form>

            <form v-else-if="step === 'couple'" class="auth-form onboarding-form" @submit.prevent="saveCouple">
                <div class="onboarding-form__row">
                    <label class="field"><span>Nama pertama</span><input v-model.trim="state.partner_one_name" placeholder="Contoh: Ayu" maxlength="100" required><small v-if="errors.partner_one_name" class="field-error">{{ errors.partner_one_name[0] }}</small></label>
                    <label class="field"><span>Nama kedua</span><input v-model.trim="state.partner_two_name" placeholder="Contoh: Bima" maxlength="100" required><small v-if="errors.partner_two_name" class="field-error">{{ errors.partner_two_name[0] }}</small></label>
                </div>
                <label class="field"><span>Tanggal pernikahan <em>Opsional</em></span><input v-model="state.wedding_date" type="date" :min="new Date().toISOString().slice(0, 10)"><small v-if="errors.wedding_date" class="field-error">{{ errors.wedding_date[0] }}</small></label>
                <input v-model="state.timezone" type="hidden">
                <p v-if="errorSummary" class="form-alert form-alert--error" role="alert">{{ errorSummary }}</p>
                <button class="button button--primary auth-submit" type="submit" :disabled="busy"><span>{{ busy ? 'Menyimpan…' : 'Simpan & lanjut' }}</span><FaIcon v-if="!busy" :icon="['fas', 'arrow-right']" aria-hidden="true" /></button>
                <RouterLink class="back-link" to="/onboarding/workspace"><FaIcon :icon="['fas', 'chevron-left']" aria-hidden="true" /> Kembali</RouterLink>
            </form>

            <form v-else-if="step === 'invite'" class="auth-form onboarding-form" @submit.prevent="finish(false)">
                <label class="field"><span>Email pasanganmu <em>Opsional</em></span><input v-model.trim="state.partner_email" type="email" autocomplete="email" placeholder="nama@email.com" :aria-invalid="Boolean(errors.email)"><small v-if="errors.email" class="field-error">{{ errors.email[0] }}</small></label>
                <p v-if="errorSummary" class="form-alert form-alert--error" role="alert">{{ errorSummary }}</p>
                <button class="button button--primary auth-submit" type="submit" :disabled="busy"><span>{{ busy ? 'Menyiapkan…' : state.partner_email ? 'Kirim undangan & lanjut' : 'Selesai tanpa mengundang' }}</span><FaIcon v-if="!busy" :icon="['fas', 'arrow-right']" aria-hidden="true" /></button>
                <button class="button button--quiet auth-submit" type="button" :disabled="busy" @click="finish(true)">Lewati untuk sekarang</button>
            </form>

            <div v-else class="onboarding-done">
                <div class="auth-mark auth-mark--success" aria-hidden="true"><FaIcon :icon="['fas', 'check']" /></div>
                <div class="workspace-summary"><span>{{ state.partner_one_name }} & {{ state.partner_two_name }}</span><strong>{{ state.wedding_date || 'Tanggal belum ditentukan' }}</strong></div>
                <RouterLink class="button button--primary auth-submit" to="/app/home">Ke beranda <FaIcon :icon="['fas', 'arrow-right']" aria-hidden="true" /></RouterLink>
            </div>
        </section>

        <footer class="onboarding-footer">Informasi kalian tersimpan aman dan hanya dibagikan sesuai pilihan.</footer>
    </main>
</template>
