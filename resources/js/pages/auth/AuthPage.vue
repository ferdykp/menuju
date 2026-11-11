<script setup>
import axios from 'axios';
import { computed, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { rememberUser } from '../../session';

const props = defineProps({ mode: { type: String, required: true } });
const router = useRouter();
const route = useRoute();
const busy = ref(false);
const notice = ref('');
const errorSummary = ref('');
const errors = ref({});
const form = reactive({ name: '', email: '', password: '', password_confirmation: '', remember: false });
form.email = typeof route.query.email === 'string' ? route.query.email : '';

const title = computed(() => ({
    welcome: 'Rencanakan hari spesial dengan lebih tenang.',
    login: 'Selamat datang kembali.',
    register: 'Mulai perjalanan kalian.',
    forgot: 'Atur ulang kata sandi.',
    reset: 'Buat kata sandi baru.',
}[props.mode]));

const description = computed(() => ({
    welcome: 'Satu ruang untuk merencanakan hari istimewa, bersama orang yang kamu pilih.',
    login: 'Masuk untuk melanjutkan persiapan bersama.',
    register: 'Buat akun untuk mulai menyiapkan hari istimewa bersama.',
    forgot: 'Masukkan email akunmu. Kami akan mengirim tautan jika email tersebut terdaftar.',
    reset: 'Gunakan kata sandi yang kuat dan mudah kamu ingat.',
}[props.mode]));

async function submit() {
    busy.value = true;
    errors.value = {};
    errorSummary.value = '';
    notice.value = '';

    try {
        if (props.mode === 'register') {
            const { data } = await axios.post('/api/auth/register', form);
            rememberUser(data.user);
            router.push(validPostAuthPath(route.query.redirect) ?? '/onboarding/workspace');
            return data;
        }

        if (props.mode === 'login') {
            const { data } = await axios.post('/api/auth/login', form);
            rememberUser(data.user);
            const workspaces = data.user?.workspaces ?? [];
            router.push(validPostAuthPath(route.query.redirect) ?? (workspaces.length ? '/app/home' : '/onboarding/workspace'));
            return data;
        }

        if (props.mode === 'forgot') {
            const { data } = await axios.post('/api/auth/forgot-password', { email: form.email });
            notice.value = data.message;
            return data;
        }

        if (props.mode === 'reset') {
            const { data } = await axios.post('/api/auth/reset-password', {
                token: route.query.token,
                email: form.email,
                password: form.password,
                password_confirmation: form.password_confirmation,
            });
            notice.value = data.message;
            window.setTimeout(() => router.push('/login'), 900);
            return data;
        }
    } catch (error) {
        errors.value = error.response?.data?.errors ?? {};
        errorSummary.value = error.response?.data?.message ?? 'Belum berhasil. Periksa kembali isianmu dan coba lagi.';
    } finally {
        busy.value = false;
    }
}

function validPostAuthPath(path) {
    return typeof path === 'string' && /^\/(invite|app|onboarding)\//.test(path) ? path : null;
}
</script>

<template>
    <main class="auth-page">
        <section class="auth-story" aria-label="Tentang MENUJU">
            <RouterLink to="/welcome" class="brand auth-brand" aria-label="MENUJU">
                <span class="brand__mark">m</span><span class="brand__name">menuju</span>
            </RouterLink>
            <div class="auth-story__body">
                <div class="eyebrow"><span class="eyebrow__dot"></span> RENCANAKAN BERSAMA</div>
                <p class="auth-story__quote">“Hari istimewa terasa lebih dekat saat direncanakan berdua.”</p>
                <p class="auth-story__note">Persiapan yang rapi, ruang untuk menikmati setiap langkah.</p>
            </div>
            <span class="auth-story__footer">MENUJU · Perjalanan menuju hari istimewa</span>
        </section>

        <section class="auth-panel">
            <div class="auth-panel__top">
                <span>{{ mode === 'welcome' ? 'Ruang persiapan untuk berdua' : 'Akun MENUJU' }}</span>
                <RouterLink v-if="mode === 'login' || mode === 'forgot' || mode === 'reset'" to="/register" class="text-link">Buat akun</RouterLink>
                <RouterLink v-else-if="mode === 'register'" to="/login" class="text-link">Sudah punya akun?</RouterLink>
            </div>

            <div class="auth-form-wrap">
                <div v-if="mode === 'welcome'" class="auth-welcome">
                    <div class="auth-mark" aria-hidden="true"><FaIcon :icon="['fas', 'heart']" /></div>
                    <p class="eyebrow auth-welcome__eyebrow">BERDUA, SELANGKAH DEMI SELANGKAH</p>
                    <h1>{{ title }}</h1>
                    <p class="auth-description">{{ description }}</p>
                    <RouterLink to="/register" class="button button--primary auth-submit">Mulai persiapan <FaIcon :icon="['fas', 'arrow-right']" aria-hidden="true" /></RouterLink>
                    <p class="auth-switch">Sudah punya akun? <RouterLink to="/login">Masuk</RouterLink></p>
                </div>

                <template v-else>
                    <p class="eyebrow auth-form__eyebrow">{{ mode === 'register' ? 'LANGKAH PERTAMA' : 'SENANG BERTEMU LAGI' }}</p>
                    <h1>{{ title }}</h1>
                    <p class="auth-description">{{ description }}</p>

                    <form class="auth-form" novalidate @submit.prevent="submit">
                        <label v-if="mode === 'register'" class="field">
                            <span>Nama kamu</span>
                            <input v-model.trim="form.name" autocomplete="name" placeholder="Contoh: Ayu Pratami" :aria-invalid="Boolean(errors.name)" required>
                            <small v-if="errors.name" class="field-error">{{ errors.name[0] }}</small>
                        </label>

                        <label class="field">
                            <span>Email</span>
                            <input v-model.trim="form.email" type="email" autocomplete="email" placeholder="nama@email.com" :aria-invalid="Boolean(errors.email)" required>
                            <small v-if="errors.email" class="field-error">{{ errors.email[0] }}</small>
                        </label>

                        <label v-if="mode === 'login' || mode === 'register' || mode === 'reset'" class="field">
                            <span>Kata sandi</span>
                            <input v-model="form.password" type="password" :autocomplete="mode === 'login' ? 'current-password' : 'new-password'" placeholder="Minimal 10 karakter" :aria-invalid="Boolean(errors.password)" required>
                            <small v-if="errors.password" class="field-error">{{ errors.password[0] }}</small>
                        </label>

                        <label v-if="mode === 'register' || mode === 'reset'" class="field">
                            <span>Ulangi kata sandi</span>
                            <input v-model="form.password_confirmation" type="password" autocomplete="new-password" placeholder="Ketik ulang kata sandi" required>
                        </label>

                        <div v-if="mode === 'login'" class="auth-form__options">
                            <label class="check-field"><input v-model="form.remember" type="checkbox"><span>Ingat saya</span></label>
                            <RouterLink to="/forgot-password" class="text-link">Lupa kata sandi?</RouterLink>
                        </div>

                        <p v-if="errorSummary" class="form-alert form-alert--error" role="alert">{{ errorSummary }}</p>
                        <p v-if="notice" class="form-alert form-alert--success" role="status">{{ notice }}</p>

                        <button class="button button--primary auth-submit" type="submit" :disabled="busy">
                            <span>{{ busy ? 'Mohon tunggu…' : mode === 'login' ? 'Masuk' : mode === 'register' ? 'Buat akun' : mode === 'forgot' ? 'Kirim tautan' : 'Simpan kata sandi' }}</span>
                            <FaIcon v-if="!busy" :icon="['fas', 'arrow-right']" aria-hidden="true" />
                        </button>
                    </form>

                    <p v-if="mode === 'login'" class="auth-switch">Belum punya akun? <RouterLink to="/register">Daftar gratis</RouterLink></p>
                    <p v-else class="auth-switch"><RouterLink to="/login">Kembali ke masuk</RouterLink></p>
                </template>
            </div>

            <div class="auth-panel__footer"><span>Privasi kalian selalu dijaga.</span><RouterLink to="/welcome">MENUJU</RouterLink></div>
        </section>
    </main>
</template>
