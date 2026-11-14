<script setup>
import axios from 'axios';
import { computed, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { rememberUser } from '../../session';

const props = defineProps({ mode: { type: String, required: true } });
const router = useRouter();
const route = useRoute();
const busy = ref(false);
const registrationStage = ref(route.query.verify_email ? 'verify' : 'details');
const verificationCode = ref('');
const notice = ref(route.query.oauth_notice === 'otp_sent' ? 'Kode konfirmasi dikirim ke email Google kamu.' : '');
const errorSummary = ref(route.query.oauth_error === 'otp_delivery_failed' ? 'Email OTP belum terkirim. Periksa SMTP, lalu pilih kirim ulang kode.' : '');
const errors = ref({});
const form = reactive({ name: '', email: '', password: '', password_confirmation: '', remember: false });
const passwordRequirements = computed(() => [
    { key: 'length', label: '10 karakter', met: form.password.length >= 10 },
    { key: 'letter', label: '1 huruf', met: /\p{L}/u.test(form.password) },
    { key: 'number', label: '1 angka', met: /\d/.test(form.password) },
]);
const passwordMatches = computed(() => form.password_confirmation.length > 0 && form.password === form.password_confirmation);
form.email = typeof route.query.verify_email === 'string'
    ? route.query.verify_email
    : typeof route.query.email === 'string' ? route.query.email : '';

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

const googleError = computed(() => ({
    google_not_configured: 'Login Google belum aktif. Konfigurasi OAuth aplikasi terlebih dahulu.',
    invalid_state: 'Sesi Google tidak valid. Silakan coba lagi.',
    cancelled: 'Proses Google dibatalkan.',
    unavailable: 'Layanan Google sedang tidak dapat dijangkau. Coba lagi.',
    failed: 'Login Google belum berhasil. Silakan coba lagi.',
    unverified_email: 'Google belum mengonfirmasi alamat email akun ini.',
    account_conflict: 'Akun Google ini sudah terhubung ke akun MENUJU lain.',
}[route.query.oauth_error] ?? 'Login Google belum berhasil. Silakan coba lagi.'));
const googleAuthUrl = computed(() => {
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '';
    return `/auth/google/redirect?flow=${props.mode}&redirect=${encodeURIComponent(redirect)}`;
});

function rememberGoogleAuthAttempt() {
    window.sessionStorage.setItem('menuju-google-auth-pending', '1');
}

async function submit() {
    busy.value = true;
    errors.value = {};
    errorSummary.value = '';
    notice.value = '';

    try {
        if (props.mode === 'register') {
            if (registrationStage.value === 'details') {
                const { data } = await axios.post('/api/auth/register', form);
                registrationStage.value = 'verify';
                notice.value = data.message;
                return data;
            }

            const { data } = await axios.post('/api/auth/register/verify', { email: form.email, code: verificationCode.value });
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

async function resendRegistrationCode() {
    busy.value = true;
    errors.value = {};
    errorSummary.value = '';
    notice.value = '';
    try {
        const { data } = await axios.post('/api/auth/register/resend-code', { email: form.email });
        notice.value = data.message;
    } catch (error) {
        errors.value = error.response?.data?.errors ?? {};
        errorSummary.value = error.response?.data?.message ?? 'Kode belum berhasil dikirim ulang.';
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
                <img class="brand__mark" :src="'/images/brand/menuju-logo.png'" alt=""><span class="brand__name">menuju</span>
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

                    <template v-if="(mode === 'login' || mode === 'register') && (mode !== 'register' || registrationStage === 'details')">
                        <a class="button button--google" :href="googleAuthUrl" @click="rememberGoogleAuthAttempt">
                            <svg aria-hidden="true" viewBox="0 0 48 48" class="google-mark">
                                <path fill="#4285F4" d="M48 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h13.52c-.59 3.04-2.3 5.62-4.85 7.35l7.85 6.09C45.1 38.09 48 31.8 48 24.55Z" />
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.91-5.8l-7.85-6.09c-2.18 1.46-4.96 2.32-8.06 2.32-6.19 0-11.43-4.18-13.3-9.8l-8.09 6.26C6.55 42.73 14.62 48 24 48Z" />
                                <path fill="#FBBC05" d="M10.7 28.63A14.4 14.4 0 0 1 9.92 24c0-1.6.28-3.15.78-4.63l-8.09-6.26A23.9 23.9 0 0 0 0 24c0 3.87.93 7.53 2.61 10.89l8.09-6.26Z" />
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.55 5.27 2.61 13.11l8.09 6.26C12.57 13.68 17.81 9.5 24 9.5Z" />
                            </svg>
                            Lanjutkan dengan Google
                        </a>
                        <div class="auth-divider"><span>atau dengan email</span></div>
                        <p v-if="route.query.oauth_error" class="form-alert form-alert--error" role="alert">{{ googleError }}</p>
                    </template>

                    <form class="auth-form" novalidate @submit.prevent="submit">
                        <template v-if="mode !== 'register' || registrationStage === 'details'">
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
                            <div v-if="mode === 'register' || mode === 'reset'" class="password-checklist" aria-label="Syarat kata sandi">
                                <span v-for="requirement in passwordRequirements" :key="requirement.key" class="password-checklist__item" :class="{ 'is-valid': requirement.met }">
                                    <span aria-hidden="true">{{ requirement.met ? '✓' : '·' }}</span>{{ requirement.label }}
                                </span>
                            </div>
                            <small v-if="errors.password" class="field-error">{{ errors.password[0] }}</small>
                        </label>

                        <label v-if="mode === 'register' || mode === 'reset'" class="field">
                            <span>Ulangi kata sandi</span>
                            <input v-model="form.password_confirmation" type="password" autocomplete="new-password" placeholder="Ketik ulang kata sandi" :aria-invalid="Boolean(form.password_confirmation) && !passwordMatches" required>
                            <small v-if="form.password_confirmation" class="password-match" :class="{ 'is-match': passwordMatches }" aria-live="polite"><span aria-hidden="true">{{ passwordMatches ? '✓' : '·' }}</span>{{ passwordMatches ? 'Kata sandi cocok' : 'Kata sandi belum cocok' }}</small>
                            <small v-if="errors.password_confirmation" class="field-error">{{ errors.password_confirmation[0] }}</small>
                        </label>
                        </template>

                        <template v-else>
                            <p class="auth-description">Masukkan kode enam digit yang kami kirim ke <strong>{{ form.email }}</strong>.</p>
                            <label class="field">
                                <span>Kode konfirmasi</span>
                                <input v-model.trim="verificationCode" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" placeholder="000000" :aria-invalid="Boolean(errors.code)" required>
                                <small v-if="errors.code" class="field-error">{{ errors.code[0] }}</small>
                            </label>
                        </template>

                        <div v-if="mode === 'login'" class="auth-form__options">
                            <label class="check-field"><input v-model="form.remember" type="checkbox"><span>Ingat saya</span></label>
                            <RouterLink to="/forgot-password" class="text-link">Lupa kata sandi?</RouterLink>
                        </div>

                        <p v-if="errorSummary" class="form-alert form-alert--error" role="alert">{{ errorSummary }}</p>
                        <p v-if="notice" class="form-alert form-alert--success" role="status">{{ notice }}</p>

                        <button class="button button--primary auth-submit" type="submit" :disabled="busy">
                            <span>{{ busy ? 'Mohon tunggu…' : mode === 'login' ? 'Masuk' : mode === 'register' ? registrationStage === 'details' ? 'Kirim kode konfirmasi' : 'Konfirmasi email' : mode === 'forgot' ? 'Kirim tautan' : 'Simpan kata sandi' }}</span>
                            <FaIcon v-if="!busy" :icon="['fas', 'arrow-right']" aria-hidden="true" />
                        </button>
                    </form>

                    <div v-if="mode === 'register' && registrationStage === 'verify'" class="auth-form__options">
                        <button class="text-link" type="button" :disabled="busy" @click="resendRegistrationCode">Kirim ulang kode</button>
                        <button class="text-link" type="button" :disabled="busy" @click="registrationStage = 'details'; verificationCode = ''; notice = ''; errorSummary = ''">Ubah email</button>
                    </div>

                    <p v-if="mode === 'login'" class="auth-switch">Belum punya akun? <RouterLink to="/register">Daftar gratis</RouterLink></p>
                    <p v-else class="auth-switch"><RouterLink to="/login">Kembali ke masuk</RouterLink></p>
                </template>
            </div>

            <div class="auth-panel__footer"><span>Privasi kalian selalu dijaga.</span><RouterLink to="/welcome">MENUJU</RouterLink></div>
        </section>
    </main>
</template>
