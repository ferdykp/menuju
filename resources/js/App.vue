<script setup>
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const menuOpen = ref(false);

const navigation = [
    {
        label: 'Beranda',
        icon: 'house',
        href: '/app/home',
        group: 'menuju',
    },
    {
        label: 'Perjalanan',
        icon: 'clock',
        href: '/app/journey',
        group: 'persiapan',
    },
    {
        label: 'Checklist',
        icon: 'circle-check',
        href: '/app/tasks',
        group: 'persiapan',
    },
    {
        label: 'Acara & Jadwal',
        icon: 'calendar-days',
        href: '/app/events',
        group: 'persiapan',
    },
    {
        label: 'Kebutuhan',
        icon: 'leaf',
        href: '/app/needs',
        group: 'persiapan',
    },
    {
        label: 'Tamu',
        icon: 'people-group',
        href: '/app/guests',
        group: 'persiapan',
    },
    {
        label: 'Undangan',
        icon: 'arrow-right',
        href: '/app/invitations',
        group: 'persiapan',
    },
    {
        label: 'Ringkasan',
        icon: 'money-bill-wave',
        href: '/app/finance',
        group: 'keuangan',
    },
    {
        label: 'Tabungan',
        icon: 'wallet',
        href: '/app/savings',
        group: 'keuangan',
    },
    {
        label: 'Anggaran',
        icon: 'wallet',
        href: '/app/budgets',
        group: 'keuangan',
    },
    {
        label: 'Transaksi',
        icon: 'money-bill-wave',
        href: '/app/transactions',
        group: 'keuangan',
    },
    {
        label: 'Akun',
        icon: 'wallet',
        href: '/app/accounts',
        group: 'keuangan',
    },
];

const groups = [
    { key: 'menuju', title: '' },
    { key: 'persiapan', title: 'PERSIAPAN' },
    { key: 'keuangan', title: 'KEUANGAN' },
];

const activePath = computed(() => route.path);

function isActive(href) {
    return activePath.value === href || activePath.value.startsWith(`${href}/`);
}
</script>

<template>
    <div class="app-frame">
        <aside class="sidebar" :class="{ 'sidebar--open': menuOpen }" aria-label="Navigasi utama">
            <a class="brand" href="/app/home" aria-label="MENUJU, beranda">
                <span class="brand__mark">m</span>
                <span class="brand__name">menuju</span>
            </a>

            <div class="workspace-switcher">
                <div class="workspace-switcher__avatar">+</div>
                <div class="workspace-switcher__copy">
                    <span>Workspace</span>
                    <strong>Belum dibuat</strong>
                </div>
                <FaIcon :icon="['fas', 'chevron-down']" class="workspace-switcher__chevron" aria-hidden="true" />
            </div>

            <nav class="nav-groups">
                <section v-for="group in groups" :key="group.key" class="nav-group">
                    <h2 v-if="group.title" class="nav-group__title">{{ group.title }}</h2>
                    <a
                        v-for="item in navigation.filter((link) => link.group === group.key)"
                        :key="item.href"
                        class="nav-link"
                        :class="{ 'nav-link--active': isActive(item.href) }"
                        :href="item.href"
                        :aria-current="isActive(item.href) ? 'page' : undefined"
                        @click="menuOpen = false"
                    >
                        <FaIcon :icon="['fas', item.icon]" class="nav-link__icon" aria-hidden="true" />
                        <span>{{ item.label }}</span>
                    </a>
                </section>
            </nav>

            <div class="sidebar__bottom">
                <a class="nav-link" href="/app/settings">
                    <FaIcon :icon="['fas', 'ellipsis']" class="nav-link__icon" aria-hidden="true" />
                    <span>Pengaturan</span>
                </a>
                <div class="profile-chip">
                    <div class="profile-chip__avatar">F</div>
                    <div class="profile-chip__copy">
                        <strong>Akun</strong>
                        <span>Masuk atau daftar</span>
                    </div>
                </div>
            </div>
        </aside>

        <button v-if="menuOpen" class="mobile-scrim" aria-label="Tutup menu" @click="menuOpen = false"></button>

        <main class="main-area">
            <header class="mobile-header">
                <button class="icon-button" aria-label="Buka navigasi" @click="menuOpen = true">
                    <FaIcon :icon="['fas', 'bars']" aria-hidden="true" />
                </button>
                <a class="brand brand--mobile" href="/app/home">
                    <span class="brand__mark">m</span>
                    <span class="brand__name">menuju</span>
                </a>
                <div class="profile-chip__avatar">?</div>
            </header>

            <div class="foundation-content">
                <div class="eyebrow"><span class="eyebrow__dot"></span> PERJALANAN BARU DIMULAI</div>
                <h1>Hari spesialmu, dimulai dengan tenang.</h1>
                <p class="foundation-lead">
                    MENUJU sedang disiapkan sebagai ruang bersama untuk menyusun hari istimewa kalian—
                    satu langkah kecil, satu keputusan pada satu waktu.
                </p>
                <div class="foundation-actions">
                    <button class="button button--primary" type="button" disabled>
                        Mulai siapkan bersama
                        <FaIcon :icon="['fas', 'arrow-right']" aria-hidden="true" />
                    </button>
                    <span class="foundation-note">Fondasi produk · Modul pertama menyusul</span>
                </div>
                <div class="foundation-divider"></div>
                <div class="foundation-meta">
                    <span>Warm Editorial Utility</span>
                    <span>Dirancang untuk berdua</span>
                </div>
            </div>
        </main>
    </div>
</template>
