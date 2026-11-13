<script setup>
import axios from 'axios';
import { computed, defineAsyncComponent, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { getRememberedUser, rememberUser } from '../session';
const HomePage = defineAsyncComponent(() => import('../pages/home/HomePage.vue'));
const TasksIndex = defineAsyncComponent(() => import('../pages/tasks/TasksIndex.vue'));
const JourneyIndex = defineAsyncComponent(() => import('../pages/journey/JourneyIndex.vue'));
const GuestsIndex = defineAsyncComponent(() => import('../pages/guests/GuestsIndex.vue'));
const EventsIndex = defineAsyncComponent(() => import('../pages/events/EventsIndex.vue'));
const EventDetailPage = defineAsyncComponent(() => import('../pages/events/EventDetailPage.vue'));
const NeedsIndex = defineAsyncComponent(() => import('../pages/needs/NeedsIndex.vue'));
const NeedDetailPage = defineAsyncComponent(() => import('../pages/needs/NeedDetailPage.vue'));
const InvitationStudio = defineAsyncComponent(() => import('../pages/invitations/InvitationStudio.vue'));
const FinanceHomePage = defineAsyncComponent(() => import('../pages/finance/FinanceHomePage.vue'));
const FinancialRecordsPage = defineAsyncComponent(() => import('../pages/finance/FinancialRecordsPage.vue'));
const SettingsPage = defineAsyncComponent(() => import('../pages/settings/SettingsPage.vue'));
import { usePageReveal } from '../composables/usePageReveal';

const route = useRoute();
const router = useRouter();
const menuOpen = ref(false);
const accountMenuOpen = ref(false);
const accountMenuTrigger = ref(null);
const accountMenuFirstItem = ref(null);
const mobilePanel = ref('');
const mobilePanelFirstLink = ref(null);
const mobilePanelTrigger = ref(null);
const user = ref(getRememberedUser());
const pageReveal = usePageReveal();
const navigation = [
    { label: 'Beranda', icon: 'house', href: '/app/home', group: 'menuju' },
    { label: 'Perjalanan', icon: 'route', href: '/app/journey', group: 'persiapan' },
    { label: 'Checklist', icon: 'list-check', href: '/app/tasks', group: 'persiapan' },
    { label: 'Acara & Jadwal', icon: 'calendar-days', href: '/app/events', group: 'persiapan' },
    { label: 'Kebutuhan', icon: 'leaf', href: '/app/needs', group: 'persiapan' },
    { label: 'Tamu', icon: 'people-group', href: '/app/guests', group: 'persiapan' },
    { label: 'Undangan', icon: 'envelope-open-text', href: '/app/invitations', group: 'persiapan' },
    { label: 'Ringkasan', icon: 'chart-pie', href: '/app/finance', group: 'keuangan' },
    { label: 'Tabungan', icon: 'piggy-bank', href: '/app/savings', group: 'keuangan' },
    { label: 'Anggaran', icon: 'scale-balanced', href: '/app/budgets', group: 'keuangan' },
    { label: 'Transaksi', icon: 'arrow-right-arrow-left', href: '/app/transactions', group: 'keuangan' },
    { label: 'Akun', icon: 'building-columns', href: '/app/accounts', group: 'keuangan' },
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
const mobileNavigation = [
    { key: 'home', label: 'Beranda', icon: 'house', href: '/app/home' },
    { key: 'preparation', label: 'Persiapan', icon: 'leaf', group: 'persiapan' },
    { key: 'finance', label: 'Keuangan', icon: 'money-bill-wave', group: 'keuangan' },
    { key: 'guests', label: 'Tamu', icon: 'people-group', href: '/app/guests' },
    { key: 'more', label: 'Lainnya', icon: 'ellipsis', group: 'lainnya' },
];
const shortcuts = {
    persiapan: navigation.filter((item) => item.group === 'persiapan' && item.href !== '/app/guests'),
    keuangan: navigation.filter((item) => item.group === 'keuangan'),
    lainnya: [{ label: 'Pengaturan', icon: 'gear', href: '/app/settings' }],
};

function mobileItemActive(item) {
    if (item.href) return isActive(item.href);
    if (item.group === 'persiapan') return shortcuts.persiapan.some((link) => isActive(link.href));
    if (item.group === 'keuangan') return shortcuts.keuangan.some((link) => isActive(link.href));
    if (item.group === 'lainnya') return route.path === '/app/settings';
    return false;
}

async function toggleMobilePanel(item, event) {
    if (item.href) {
        mobilePanel.value = '';
        router.push(item.href);
        return;
    }
    if (mobilePanel.value === item.group) {
        closeMobilePanel(true);
        return;
    }
    mobilePanelTrigger.value = event.currentTarget;
    mobilePanel.value = item.group;
    await nextTick();
    mobilePanelFirstLink.value?.focus();
}

function closeMobilePanel(restoreFocus = false) {
    mobilePanel.value = '';
    if (restoreFocus) nextTick(() => mobilePanelTrigger.value?.focus());
}

onMounted(async () => {
    document.addEventListener('click', handleDocumentClick);
    try {
        const { data } = await axios.get('/api/auth/me');
        user.value = data.user;
        rememberUser(data.user);
    } catch {
        // The router guard sends unauthenticated sessions to the login screen.
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleDocumentClick);
    pageReveal.stop();
});

function animatePageContent(frame) { pageReveal.start(frame); }

function handleDocumentClick() {
    closeAccountMenu();
}

function toggleAccountMenu(event) {
    accountMenuTrigger.value = event.currentTarget;
    accountMenuOpen.value = !accountMenuOpen.value;
    if (accountMenuOpen.value) nextTick(() => (accountMenuFirstItem.value?.$el ?? accountMenuFirstItem.value)?.focus());
}

function closeAccountMenu(restoreFocus = false) {
    if (!accountMenuOpen.value) return;
    accountMenuOpen.value = false;
    if (restoreFocus) nextTick(() => accountMenuTrigger.value?.focus());
}

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
                <img class="brand__mark" :src="'/images/brand/menuju-logo.png'" alt=""><span class="brand__name">menuju</span>
            </RouterLink>

            <div class="workspace-switcher" aria-label="Workspace aktif">
                <div class="workspace-switcher__avatar">{{ workspaceName.slice(0, 1) }}</div>
                <div class="workspace-switcher__copy"><span>Workspace</span><strong>{{ workspaceName }}</strong></div>
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

        </aside>

        <Transition name="scrim">
            <button v-if="menuOpen" class="mobile-scrim" aria-label="Tutup menu" @click="menuOpen = false"></button>
        </Transition>

        <main class="main-area">
            <header class="desktop-topbar">
                <div class="desktop-topbar__workspace">
                    <strong>{{ workspaceName }}</strong>
                    <span v-if="currentWorkspace?.wedding_date">{{ new Date(`${currentWorkspace.wedding_date}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                    <span v-else>Ruang persiapan kalian</span>
                </div>
                <button class="desktop-topbar__account" type="button" :aria-expanded="accountMenuOpen" aria-haspopup="menu" aria-controls="account-menu" @click.stop="toggleAccountMenu($event)"><span>{{ accountName }}</span><span class="profile-chip__avatar">{{ accountName.slice(0, 1) }}</span><FaIcon :icon="['fas','chevron-down']" aria-hidden="true" /></button>
            </header>
            <header class="mobile-header">
                <button class="icon-button" aria-label="Buka navigasi" @click="mobilePanel = ''; menuOpen = true">
                    <FaIcon :icon="['fas', 'bars']" aria-hidden="true" />
                </button>
                <RouterLink class="brand brand--mobile" to="/app/home"><img class="brand__mark" :src="'/images/brand/menuju-logo.png'" alt=""><span class="brand__name">menuju</span></RouterLink>
                <button class="profile-chip__avatar mobile-header__account-trigger" type="button" :aria-label="`Buka menu akun ${accountName}`" :aria-expanded="accountMenuOpen" aria-haspopup="menu" aria-controls="account-menu" @click.stop="toggleAccountMenu($event)">{{ accountName.slice(0, 1) }}</button>
            </header>

            <Transition name="popover" appear>
                <div v-if="accountMenuOpen" id="account-menu" class="account-dropdown" role="menu" aria-label="Menu akun" @click.stop @keydown.esc.stop.prevent="closeAccountMenu(true)">
                    <div class="account-dropdown__identity"><span class="profile-chip__avatar">{{ accountName.slice(0, 1) }}</span><span><strong>{{ accountName }}</strong><small>{{ user?.email ?? '' }}</small></span></div>
                    <div class="account-dropdown__divider"></div>
                    <RouterLink ref="accountMenuFirstItem" class="account-dropdown__item" role="menuitem" to="/app/settings" @click="closeAccountMenu()"><FaIcon :icon="['fas','gear']" aria-hidden="true" /><span>Pengaturan</span><FaIcon :icon="['fas','chevron-right']" class="account-dropdown__arrow" aria-hidden="true" /></RouterLink>
                    <button class="account-dropdown__item account-dropdown__item--logout" role="menuitem" type="button" @click="closeAccountMenu();logout()"><FaIcon :icon="['fas','right-from-bracket']" aria-hidden="true" /><span>Keluar</span><FaIcon :icon="['fas','chevron-right']" class="account-dropdown__arrow" aria-hidden="true" /></button>
                </div>
            </Transition>

            <Transition name="mobile-popover">
                <button v-if="mobilePanel" class="mobile-nav-scrim" aria-label="Tutup pintasan navigasi" @click="closeMobilePanel(true)"></button>
            </Transition>
            <Transition name="mobile-popover">
                <nav v-if="mobilePanel" id="mobile-shortcuts" class="mobile-nav-panel" :aria-label="mobilePanel === 'persiapan' ? 'Menu persiapan' : mobilePanel === 'keuangan' ? 'Menu keuangan' : 'Menu lainnya'" @keydown.esc.prevent="closeMobilePanel(true)">
                    <p class="mobile-nav-panel__title">{{ mobilePanel === 'persiapan' ? 'PERSIAPAN' : mobilePanel === 'keuangan' ? 'KEUANGAN' : 'LAINNYA' }}</p>
                    <RouterLink v-for="(item, index) in shortcuts[mobilePanel]" :key="item.href" :ref="index === 0 ? (element) => { mobilePanelFirstLink = element?.$el ?? element } : undefined" class="mobile-nav-panel__link" :class="{ 'mobile-nav-panel__link--active': isActive(item.href) }" :to="item.href" @click="closeMobilePanel()">
                        <FaIcon :icon="['fas', item.icon]" aria-hidden="true" /><span>{{ item.label }}</span><FaIcon :icon="['fas','chevron-right']" class="mobile-nav-panel__arrow" aria-hidden="true" />
                    </RouterLink>
                </nav>
            </Transition>

            <div class="app-page-surface">
                <Transition name="page" appear @after-enter="animatePageContent" @after-appear="animatePageContent">
                <div :key="route.path" class="page-transition-frame">
                <HomePage v-if="route.path === '/app/home' && currentWorkspace" :workspace-id="currentWorkspace.id" :workspace="currentWorkspace" />
                <TasksIndex v-else-if="route.path === '/app/tasks' && currentWorkspace" :workspace-id="currentWorkspace.id" />
                <JourneyIndex v-else-if="route.path === '/app/journey' && currentWorkspace" :workspace-id="currentWorkspace.id" :wedding-date="currentWorkspace.wedding_date" />
                <GuestsIndex v-else-if="route.path === '/app/guests' && currentWorkspace" :workspace-id="currentWorkspace.id" />
                <EventsIndex v-else-if="route.path === '/app/events' && currentWorkspace" :workspace-id="currentWorkspace.id" :timezone="currentWorkspace.timezone" />
                <EventDetailPage v-else-if="route.path.startsWith('/app/events/') && currentWorkspace" :key="route.path" :workspace-id="currentWorkspace.id" :timezone="currentWorkspace.timezone" />
                <NeedsIndex v-else-if="route.path === '/app/needs' && currentWorkspace" :workspace-id="currentWorkspace.id" />
                <NeedDetailPage v-else-if="route.path.startsWith('/app/needs/') && currentWorkspace" :key="route.path" :workspace-id="currentWorkspace.id" />
                <InvitationStudio v-else-if="route.path.startsWith('/app/invitations') && currentWorkspace" :workspace-id="currentWorkspace.id" :workspace="currentWorkspace" />
                <FinanceHomePage v-else-if="route.path === '/app/finance' && currentWorkspace" :workspace-id="currentWorkspace.id" />
                <FinancialRecordsPage v-else-if="['accounts','transactions','budgets','savings'].includes(route.path.split('/')[2]) && currentWorkspace" :key="route.path.split('/')[2]" :workspace-id="currentWorkspace.id" :mode="route.path.split('/')[2]" />
                <SettingsPage v-else-if="route.path === '/app/settings' && currentWorkspace" :workspace="currentWorkspace" :user="user" @updated="user = $event; rememberUser($event)" />
                <div v-else class="foundation-content foundation-placeholder">
                    <div class="eyebrow"><span class="eyebrow__dot"></span> RUANG PERSIAPAN KALIAN</div>
                    <h1>Halaman tidak ditemukan</h1>
                    <p class="foundation-lead">Menu yang kamu buka tidak tersedia. Kembali ke beranda untuk melanjutkan persiapan.</p>
                    <RouterLink class="back-link" to="/app/home"><FaIcon :icon="['fas', 'chevron-left']" aria-hidden="true" /> Kembali ke beranda</RouterLink>
                </div>
                </div>
                </Transition>
            </div>
        </main>

        <nav class="mobile-dock" aria-label="Navigasi utama">
            <template v-for="item in mobileNavigation" :key="item.key">
                <RouterLink v-if="item.href" class="mobile-dock__item" :class="{ 'mobile-dock__item--active': mobileItemActive(item) }" :to="item.href" :aria-current="mobileItemActive(item) ? 'page' : undefined" @click="closeMobilePanel()">
                    <FaIcon :icon="['fas', item.icon]" aria-hidden="true" /><span>{{ item.label }}</span>
                </RouterLink>
                <button v-else class="mobile-dock__item" :class="{ 'mobile-dock__item--active': mobileItemActive(item) || mobilePanel === item.group }" type="button" :aria-expanded="mobilePanel === item.group" aria-controls="mobile-shortcuts" @click="toggleMobilePanel(item, $event)">
                    <FaIcon :icon="['fas', item.icon]" aria-hidden="true" /><span>{{ item.label }}</span>
                </button>
            </template>
        </nav>
    </div>
</template>
