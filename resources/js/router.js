import { createRouter, createWebHistory } from 'vue-router';
import AuthPage from './pages/auth/AuthPage.vue';
import OnboardingFlow from './pages/onboarding/OnboardingFlow.vue';
import AppShell from './layouts/AppShell.vue';
import InvitationAcceptPage from './pages/auth/InvitationAcceptPage.vue';
import axios from 'axios';
import { getRememberedUser, rememberUser } from './session';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', redirect: '/welcome' },
        { path: '/welcome', name: 'welcome', component: AuthPage, props: { mode: 'welcome' } },
        { path: '/login', name: 'login', component: AuthPage, props: { mode: 'login' } },
        { path: '/register', name: 'register', component: AuthPage, props: { mode: 'register' } },
        { path: '/forgot-password', name: 'forgot-password', component: AuthPage, props: { mode: 'forgot' } },
        { path: '/reset-password', name: 'reset-password', component: AuthPage, props: { mode: 'reset' } },
        { path: '/invite/:token', name: 'invite', component: InvitationAcceptPage },
        { path: '/onboarding/workspace', name: 'onboarding-workspace', component: OnboardingFlow, props: { step: 'workspace' } },
        { path: '/onboarding/couple', name: 'onboarding-couple', component: OnboardingFlow, props: { step: 'couple' } },
        { path: '/onboarding/invite', name: 'onboarding-invite', component: OnboardingFlow, props: { step: 'invite' } },
        { path: '/onboarding/done', name: 'onboarding-done', component: OnboardingFlow, props: { step: 'done' } },
        { path: '/app/:pathMatch(.*)*', name: 'app', component: AppShell },
        { path: '/:pathMatch(.*)*', redirect: '/welcome' },
    ],
    scrollBehavior() {
        return { top: 0 };
    },
});

router.beforeEach(async (to) => {
    const needsSession = to.name === 'app' || to.name === 'invite' || String(to.name).startsWith('onboarding-');
    if (!needsSession) return true;

    let user = getRememberedUser();
    if (!user) {
        try {
            const { data } = await axios.get('/api/auth/me');
            user = data.user;
            rememberUser(user);
        } catch {
            return { name: 'login', query: { redirect: to.fullPath } };
        }
    }

    const workspace = user.workspaces?.[0];
    if (to.name === 'onboarding-workspace' && workspace) {
        return { name: workspace.onboarding_completed_at ? 'app' : 'onboarding-invite' };
    }
    if (to.name === 'app' && !workspace) return { name: 'onboarding-workspace' };
    if (to.name === 'app' && workspace && !workspace.onboarding_completed_at) return { name: 'onboarding-invite' };

    let onboarding = {};
    try { onboarding = JSON.parse(sessionStorage.getItem('menuju-onboarding') || '{}'); } catch { /* use the first onboarding step */ }
    if (to.name === 'onboarding-couple' && !onboarding.name) return { name: 'onboarding-workspace' };
    if (to.name === 'onboarding-invite' && !onboarding.workspace_id && !workspace) return { name: 'onboarding-workspace' };
    if (to.name === 'onboarding-done' && !workspace) return { name: 'onboarding-workspace' };

    return true;
});

export default router;
