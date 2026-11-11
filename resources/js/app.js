import { createApp } from 'vue';
import '@fontsource-variable/plus-jakarta-sans';
import '@fontsource/eb-garamond/500.css';
import '@fontsource/eb-garamond/600.css';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { library } from '@fortawesome/fontawesome-svg-core';
import axios from 'axios';
import {
    faArrowRight,
    faBars,
    faCalendarDays,
    faCheck,
    faChevronDown,
    faChevronLeft,
    faChevronRight,
    faCircleCheck,
    faClock,
    faEllipsis,
    faHeart,
    faHouse,
    faLeaf,
    faMagnifyingGlass,
    faMoneyBillWave,
    faPeopleGroup,
    faPlus,
    faRotate,
    faWallet,
    faXmark,
} from '@fortawesome/free-solid-svg-icons';
import App from './App.vue';
import router from './router';

library.add(
    faArrowRight, faBars, faCalendarDays, faCheck, faChevronDown,
    faChevronLeft, faChevronRight, faCircleCheck, faClock, faEllipsis,
    faHeart, faHouse, faLeaf, faMagnifyingGlass, faMoneyBillWave, faPeopleGroup, faPlus,
    faRotate, faWallet, faXmark,
);

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

createApp(App)
    .component('FaIcon', FontAwesomeIcon)
    .use(router)
    .mount('#app');
