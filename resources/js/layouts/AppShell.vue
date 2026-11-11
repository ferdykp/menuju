<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { getRememberedUser, rememberUser } from '../session';
import HomePage from '../pages/home/HomePage.vue';
import TasksIndex from '../pages/tasks/TasksIndex.vue';
import JourneyIndex from '../pages/journey/JourneyIndex.vue';

const route = useRoute();
const router = useRouter();
const menuOpen = ref(false);
const user = ref(getRememberedUser());

const navigation = [
    { label: 'Beranda', icon: 'house', href: '/app/home', group: 'menuju' },
    { label: 'Perjalanan', icon: 'clock', href: '/app/journey', group: 'persiapan' },
    { label: 'Checklist', icon: 'circle-check', href: '/app/tasks', group: 'persiapan' },
    { label: 'Acara & Jadwal', icon: 'calendar-days', href: '/app/events', group: 'persiapan' },
    { label: 'Kebutuhan', icon: 'leaf', href: '/app/needs', group: 'persiapan' },
    { label: 'Tamu', icon: 'people-group', href: '/app/guests', group: 'persiapan' },
    { label: 'Undangan', icon: 'arrow-right', href: '/app/invitations', group: 'persiapan' },
    { label: 'Ringkasan', icon: 'money-bill-wave', href: '/app/finance', group: 'keuangan' },
    { label: 'Tabungan', icon: 'wallet', href: '/app/savings', group: 'keuangan' },
    { label: 'Anggaran', icon: 'wallet', href: '/app/budgets', group: 'keuangan' },
    { label: 'Transaksi', icon: 'money-bill-wave', href: '/app/transactions', group: 'keuangan' },
    { label: 'Akun', icon: 'wallet', href: '/app/accounts', group: 'keuangan' },
];

const groups = [
    { key: 'menuju', title: '' },
    { key: 'persiapan', title: 'PERSIAPAN' },
    { key: 'keuangan', title: 'KEUANGAN' },
];

const pageName = computed(() => navigation.find((item) => isActive(item.href))?.label ?? 'Beranda');
const workspaceName = computed(() => user.value?.workspaces?.[0]?.name ?? 'Workspace');
const currentWorkspace = computed(() => user.value?.workspaces?.[0] ?? null);
const accountName = computed(() => user.value?.name ?? 'Akun');

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/auth/me');
        user.value = data.user;
        rememberUser(data.user);
    } catch {
        // The router guard sends unauthenticated sessions to the login screen.
    }
});

async function logout() {
    await axios.post('/api/auth/logout');
    rememberUser(null);
    router.push('/welcome');
}

function isActive(href) {
    return route.path === href || route.path.startsWith(`${href}/`);
}
</script>

<template>
    <div class="app-frame">
        <aside class="sidebar" :class="{ 'sidebar--open': menuOpen }" aria-label="Navigasi utama">
            <RouterLink class="brand" to="/app/home" aria-label="MENUJU, beranda">
                <span class="brand__mark">m</span><span class="brand__name">menuju</span>
            </RouterLink>

            <div class="workspace-switcher">
                <div class="workspace-switcher__avatar">{{ workspaceName.slice(0, 1) }}</div>
                <div class="workspace-switcher__copy"><span>Workspace</span><strong>{{ workspaceName }}</strong></div>
                <FaIcon :icon="['fas', 'chevron-down']" class="workspace-switcher__chevron" aria-hidden="true" />
            </div>

            <nav class="nav-groups">
                <section v-for="group in groups" :key="group.key" class="nav-group">
                    <h2 v-if="group.title" class="nav-group__title">{{ group.title }}</h2>
                    <RouterLink
                        v-for="item in navigation.filter((link) => link.group === group.key)"
                        :key="item.href"
                        class="nav-link"
                        :class="{ 'nav-link--active': isActive(item.href) }"
                        :to="item.href"
                        :aria-current="isActive(item.href) ? 'page' : undefined"
                        @click="menuOpen = false"
                    >
                        <FaIcon :icon="['fas', item.icon]" class="nav-link__icon" aria-hidden="true" />
                        <span>{{ item.label }}</span>
                    </RouterLink>
                </section>
            </nav>

            <div class="sidebar__bottom">
                <RouterLink class="nav-link" to="/app/settings">
                    <FaIcon :icon="['fas', 'ellipsis']" class="nav-link__icon" aria-hidden="true" /><span>Pengaturan</span>
                </RouterLink>
                <div class="profile-chip">
                    <div class="profile-chip__avatar">{{ accountName.slice(0, 1) }}</div>
                    <div class="profile-chip__copy"><strong>{{ accountName }}</strong><span>{{ user?.email ?? '' }}</span></div>
                </div>
                <button class="nav-link nav-link--logout" type="button" @click="logout">
                    <FaIcon :icon="['fas', 'xmark']" class="nav-link__icon" aria-hidden="true" /><span>Keluar</span>
                </button>
            </div>
        </aside>

        <button v-if="menuOpen" class="mobile-scrim" aria-label="Tutup menu" @click="menuOpen = false"></button>

        <main class="main-area">
            <header class="desktop-topbar">
                <div class="desktop-topbar__workspace">
                    <strong>{{ workspaceName }}</strong>
                    <span v-if="currentWorkspace?.wedding_date">{{ new Date(`${currentWorkspace.wedding_date}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                    <span v-else>Ruang persiapan kalian</span>
                </div>
                <div class="desktop-topbar__account"><span>{{ accountName }}</span><span class="profile-chip__avatar">{{ accountName.slice(0, 1) }}</span></div>
            </header>
            <header class="mobile-header">
                <button class="icon-button" aria-label="Buka navigasi" @click="menuOpen = true">
                    <FaIcon :icon="['fas', 'bars']" aria-hidden="true" />
                </button>
                <RouterLink class="brand brand--mobile" to="/app/home"><span class="brand__mark">m</span><span class="brand__name">menuju</span></RouterLink>
                <div class="profile-chip__avatar">{{ accountName.slice(0, 1) }}</div>
            </header>

            <HomePage v-if="route.path === '/app/home' && currentWorkspace" :workspace-id="currentWorkspace.id" :workspace="currentWorkspace" />
            <TasksIndex v-else-if="route.path === '/app/tasks' && currentWorkspace" :workspace-id="currentWorkspace.id" />
            <JourneyIndex v-else-if="route.path === '/app/journey' && currentWorkspace" :workspace-id="currentWorkspace.id" :wedding-date="currentWorkspace.wedding_date" />
            <div v-else class="foundation-content foundation-placeholder">
                <div class="eyebrow"><span class="eyebrow__dot"></span> RUANG PERSIAPAN KALIAN</div>
                <h1>{{ pageName }}</h1>
                <p class="foundation-lead">Halaman {{ pageName.toLowerCase() }} sedang disiapkan. Perjalanan aplikasi akan dibangun per modul dan dicatat berdasarkan Screen ID.</p>
                <RouterLink class="back-link" to="/app/home"><FaIcon :icon="['fas', 'chevron-left']" aria-hidden="true" /> Kembali ke beranda</RouterLink>
            </div>
        </main>
    </div>
</template>
