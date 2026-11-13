import { createApp } from 'vue';
import '@fontsource-variable/plus-jakarta-sans';
import '@fontsource/eb-garamond/500.css';
import '@fontsource/eb-garamond/600.css';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import MotionSelect from './components/MotionSelect.vue';
import AnimatedNumber from './components/AnimatedNumber.vue';
import { library } from '@fortawesome/fontawesome-svg-core';
import axios from 'axios';
import {
    faArrowDown,
    faArrowLeft,
    faArrowRight,
    faArrowUp,
    faArrowUpRightFromSquare,
    faBars,
    faBuildingColumns,
    faCalendarDays,
    faCheck,
    faChevronDown,
    faChevronLeft,
    faChevronRight,
    faCircleCheck,
    faChartPie,
    faClock,
    faEllipsis,
    faEye,
    faEnvelopeOpenText,
    faGear,
    faHeart,
    faHouse,
    faImage,
    faImages,
    faLeaf,
    faLock,
    faListCheck,
    faLocationDot,
    faMagnifyingGlass,
    faMoneyBillWave,
    faPeopleGroup,
    faPalette,
    faPen,
    faPiggyBank,
    faPlus,
    faArrowRightArrowLeft,
    faBoxArchive,
    faRoute,
    faRotate,
    faRightFromBracket,
    faScaleBalanced,
    faTrash,
    faLink,
    faUserGroup,
    faWallet,
    faVolumeHigh,
    faVolumeXmark,
    faXmark,
} from '@fortawesome/free-solid-svg-icons';
import App from './App.vue';
import router from './router';
import { useToast } from './composables/toastBus';

library.add(
    faArrowDown, faArrowLeft, faArrowRight, faArrowUp, faArrowUpRightFromSquare, faBars, faBuildingColumns, faCalendarDays, faCheck, faChartPie, faChevronDown,
    faChevronLeft, faChevronRight, faCircleCheck, faClock, faEllipsis,
    faEnvelopeOpenText, faEye, faGear, faHeart, faHouse, faImage, faImages, faLeaf, faLink, faListCheck, faLock, faLocationDot, faMagnifyingGlass,
    faMoneyBillWave, faPalette, faPeopleGroup, faPen, faPiggyBank, faPlus, faArrowRightArrowLeft, faBoxArchive, faRoute, faRotate, faScaleBalanced,
    faRightFromBracket, faTrash, faUserGroup, faWallet, faVolumeHigh, faVolumeXmark, faXmark,
);

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

const { success: notifySuccess } = useToast();
const recordNames = {
    guests: 'Tamu', tasks: 'Tugas', events: 'Acara', 'agenda-items': 'Agenda', needs: 'Kebutuhan',
    'need-categories': 'Kategori kebutuhan', transactions: 'Transaksi', accounts: 'Akun',
    'budget-categories': 'Kategori anggaran', budgets: 'Anggaran', 'payment-obligations': 'Tagihan',
    'savings-goals': 'Target tabungan', deposits: 'Setoran', milestones: 'Tahapan persiapan',
    invitation: 'Undangan', members: 'Akses partner', invites: 'Undangan partner', profile: 'Profil akun',
};

function successfulActionMessage(config) {
    const method = String(config.method ?? 'get').toLowerCase();
    const path = String(config.url ?? '').split('?')[0].replace(/^https?:\/\/[^/]+/i, '');
    if (!path.startsWith('/api/') || !['post', 'put', 'patch', 'delete'].includes(method)) return '';

    if (path === '/api/auth/login') return 'Berhasil masuk. Selamat datang kembali.';
    if (path === '/api/auth/register/verify') return 'Akun berhasil diverifikasi. Selamat bergabung!';
    if (path === '/api/auth/logout') return 'Kamu berhasil keluar dari akun.';
    if (path === '/api/workspaces') return 'Workspace pasangan berhasil dibuat.';

    // These flows already show their own contextual success content.
    if (path.startsWith('/api/auth/') || path.includes('/public/invitations/') && path.endsWith('/rsvp')) return '';
    if (path.endsWith('/guests/import/preview')) return '';
    if (path.includes('/settings') || path === '/api/profile' || /\/(members|invites|invitations\/pending)(\/|$)/.test(path)) return '';
    if (/\/api\/workspaces\/\d+\/(invites|complete)$/.test(path)) return '';
    if (/\/api\/workspaces\/\d+\/invitation\/\d+$/.test(path) && method === 'patch') return '';

    const parts = path.split('/').filter(Boolean);
    const record = parts.find((part) => Object.hasOwn(recordNames, part));
    const name = recordNames[record] ?? 'Data';
    if (parts.includes('cover')) return `Foto sampul berhasil ${method === 'delete' ? 'dihapus' : 'diperbarui'}.`;
    if (parts.includes('gallery')) return `Foto galeri berhasil ${method === 'delete' ? 'dihapus' : 'ditambahkan'}.`;
    if (parts.includes('import')) return 'Daftar tamu berhasil diimpor.';
    if (parts.includes('publish')) return 'Status undangan berhasil diperbarui.';
    if (parts.includes('void')) return 'Transaksi berhasil dibatalkan.';
    if (parts.includes('link')) return 'Tautan personal berhasil dibuat.';
    if (parts.includes('purchase')) return 'Pembelian kebutuhan berhasil dicatat.';
    if (parts.includes('payments')) return 'Pembayaran berhasil dicatat.';
    if (parts.includes('deposits')) return `Setoran ${method === 'delete' ? 'berhasil dibatalkan' : method === 'patch' ? 'berhasil diperbarui' : 'berhasil dicatat'}.`;
    if (method === 'delete') return `${name} berhasil dihapus.`;
    if (method === 'patch' || method === 'put') return `${name} berhasil diperbarui.`;
    if (parts.includes('accept')) return 'Undangan partner berhasil diterima.';
    return `${name} berhasil ditambahkan.`;
}

axios.interceptors.response.use((response) => {
    const message = successfulActionMessage(response.config ?? {});
    if (message) notifySuccess(message);
    return response;
});

router.afterEach((to) => {
    if (!window.sessionStorage.getItem('menuju-google-auth-pending')) return;
    if (to.query.oauth_error || to.path.startsWith('/register')) {
        window.sessionStorage.removeItem('menuju-google-auth-pending');
        return;
    }
    if (/^\/(app|onboarding|invite)\//.test(to.path)) {
        window.sessionStorage.removeItem('menuju-google-auth-pending');
        notifySuccess('Berhasil masuk melalui Google.');
    }
});

createApp(App)
    .component('FaIcon', FontAwesomeIcon)
    .component('MotionSelect', MotionSelect)
    .component('AnimatedNumber', AnimatedNumber)
    .use(router)
    .mount('#app');
