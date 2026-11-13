import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';
import { getRememberedUser, rememberUser } from './session';
import { confirmUnsavedChanges } from './unsavedChanges';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', redirect: '/welcome' },
        { path: '/welcome', name: 'welcome', component: () => import('./pages/auth/AuthPage.vue'), props: { mode: 'welcome' } },
        { path: '/login', name: 'login', component: () => import('./pages/auth/AuthPage.vue'), props: { mode: 'login' } },
        { path: '/register', name: 'register', component: () => import('./pages/auth/AuthPage.vue'), props: { mode: 'register' } },
        { path: '/forgot-password', name: 'forgot-password', component: () => import('./pages/auth/AuthPage.vue'), props: { mode: 'forgot' } },
        { path: '/reset-password', name: 'reset-password', component: () => import('./pages/auth/AuthPage.vue'), props: { mode: 'reset' } },
        { path: '/invite/:token', name: 'invite', component: () => import('./pages/auth/InvitationAcceptPage.vue') },
        { path: '/w/:slug', name: 'public-invitation', component: () => import('./pages/invitations/PublicInvitationPage.vue') },
        { path: '/onboarding/workspace', name: 'onboarding-workspace', component: () => import('./pages/onboarding/OnboardingFlow.vue'), props: { step: 'workspace' } },
        { path: '/onboarding/couple', name: 'onboarding-couple', component: () => import('./pages/onboarding/OnboardingFlow.vue'), props: { step: 'couple' } },
        { path: '/onboarding/invite', name: 'onboarding-invite', component: () => import('./pages/onboarding/OnboardingFlow.vue'), props: { step: 'invite' } },
        { path: '/onboarding/done', name: 'onboarding-done', component: () => import('./pages/onboarding/OnboardingFlow.vue'), props: { step: 'done' } },
        { path: '/app/:pathMatch(.*)*', name: 'app', component: () => import('./layouts/AppShell.vue') },
        { path: '/:pathMatch(.*)*', redirect: '/welcome' },
    ],
    scrollBehavior(to) {
        if (to.hash) {
            return {
                el: to.hash,
                top: 90,
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
            };
        }
        return { top: 0 };
    },
});

router.beforeEach(async (to) => {
    if (!await confirmUnsavedChanges()) return false;

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
