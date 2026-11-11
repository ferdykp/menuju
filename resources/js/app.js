import { createApp } from 'vue';
import '@fontsource-variable/plus-jakarta-sans';
import '@fontsource/eb-garamond/500.css';
import '@fontsource/eb-garamond/600.css';
import { createRouter, createWebHistory } from 'vue-router';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { library } from '@fortawesome/fontawesome-svg-core';
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
    faHouse,
    faMoneyBillWave,
    faPeopleGroup,
    faPlus,
    faRotate,
    faLeaf,
    faWallet,
    faXmark,
} from '@fortawesome/free-solid-svg-icons';
import App from './App.vue';

library.add(
    faArrowRight, faBars, faCalendarDays, faCheck, faChevronDown,
    faChevronLeft, faChevronRight, faCircleCheck, faClock, faEllipsis,
    faHouse, faMoneyBillWave, faPeopleGroup, faPlus, faRotate,
    faLeaf, faWallet, faXmark,
);

const router = createRouter({
    history: createWebHistory(),
    routes: [{ path: '/:pathMatch(.*)*', component: App }],
});

createApp(App)
    .component('FaIcon', FontAwesomeIcon)
    .use(router)
    .mount('#app');
