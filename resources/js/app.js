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

createApp(App)
    .component('FaIcon', FontAwesomeIcon)
    .component('MotionSelect', MotionSelect)
    .component('AnimatedNumber', AnimatedNumber)
    .use(router)
    .mount('#app');
