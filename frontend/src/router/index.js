import { createRouter, createWebHistory, useRoute, useRouter } from 'vue-router'
import { onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useWorkspaceStore } from '../stores/workspace'

const routes = [
  {
    path: '/landing',
    name: 'landing',
    component: () => import('../pages/LandingPage.vue'),
    meta: { public: true },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../pages/LoginPage.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../pages/RegisterPage.vue'),
    meta: { guest: true },
  },
  {
    path: '/invitation/:token',
    name: 'accept-invitation',
    component: () => import('../pages/AcceptInvitationPage.vue'),
  },
  {
    path: '/auth/callback',
    name: 'auth-callback',
    component: {
      template: '<div class="min-h-screen flex items-center justify-center text-[#8b949e] bg-[#0d1117]">Autenticando...</div>',
      setup() {
        const route = useRoute()
        const router = useRouter()

        onMounted(async () => {
          const authStore = useAuthStore()
          const token = route.query.token
          const needsOnboarding = route.query.needs_onboarding
          if (token) {
            try {
              await authStore.handleGoogleCallback(token)
              if (needsOnboarding) {
                router.push('/onboarding')
              } else {
                router.push('/boards')
              }
            } catch {
              router.push('/login')
            }
          } else {
            router.push('/login')
          }
        })
      },
    },
    meta: { guest: true },
  },
  {
    path: '/auth/sso-callback',
    name: 'sso-callback',
    component: {
      template: '<div class="min-h-screen flex items-center justify-center text-[#8b949e] bg-[#0d1117]">Autenticando via SSO...</div>',
      setup() {
        const route = useRoute()
        const router = useRouter()

        onMounted(async () => {
          const authStore = useAuthStore()
          const workspaceStore = useWorkspaceStore()

          const token = route.query.token
          const workspaceId = route.query.workspace
          if (token) {
            try {
              localStorage.setItem('zentra_token', token)
              authStore.token = token
              await authStore.fetchMe()
              if (workspaceId) {
                await workspaceStore.fetchWorkspaces()
                const ws = workspaceStore.workspaces.find(w => w.id === workspaceId)
                if (ws) {
                  workspaceStore.setCurrentWorkspace(ws)
                }
              }
              router.push('/boards')
            } catch {
              router.push('/login')
            }
          } else {
            router.push('/login')
          }
        })
      },
    },
    meta: { guest: true },
  },
  {
    path: '/workspaces',
    name: 'workspaces',
    component: () => import('../pages/WorkspaceSelectorPage.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/onboarding',
    name: 'onboarding',
    component: () => import('../pages/OnboardingPage.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/',
    component: () => import('../layouts/AppLayout.vue'),
    meta: { requiresAuth: true, requiresWorkspace: true },
    children: [
      { path: '', redirect: '/boards' },
      { path: 'dashboard', name: 'dashboard', component: () => import('../pages/DashboardPage.vue') },
      { path: 'boards', name: 'boards', component: () => import('../pages/BoardsPage.vue') },
      { path: 'boards/:id', name: 'board', component: () => import('../pages/BoardPage.vue') },
      { path: 'plans', name: 'plans', component: () => import('../pages/PlansPage.vue') },
      { path: 'billing', name: 'billing', component: () => import('../pages/BillingPage.vue') },
      { path: 'billing/success', redirect: '/billing' },
      { path: 'settings', name: 'settings', component: () => import('../pages/WorkspaceSettingsPage.vue') },
      { path: 'chat', name: 'chat', component: () => import('../pages/ChatPage.vue') },
      { path: 'api-keys', name: 'api-keys', component: () => import('../pages/ApiKeysPage.vue') },
      { path: 'power-ups', name: 'power-ups', component: () => import('../pages/PowerUpsPage.vue') },
      { path: 'templates', name: 'templates', component: () => import('../pages/TemplatesPage.vue') },
      { path: 'profile', name: 'profile', component: () => import('../pages/UserProfilePage.vue') },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/login' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to) {
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    return { top: 0 }
  },
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  const workspaceStore = useWorkspaceStore()

  // Public routes are always accessible
  if (to.meta.public) {
    return next()
  }

  // If guest route and user is already authenticated, redirect to boards
  if (to.meta.guest && authStore.isAuthenticated) {
    return next('/boards')
  }

  // If route requires auth and user is not authenticated
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next('/login')
  }

  // Fetch user data if we have a token but no user object (page reload)
  if (authStore.isAuthenticated && !authStore.user) {
    try {
      await authStore.fetchMe()
    } catch {
      localStorage.removeItem('zentra_token')
      return next('/login')
    }
  }

  // If route requires workspace, ensure one is selected
  if (to.meta.requiresWorkspace && authStore.isAuthenticated) {
    if (workspaceStore.workspaces.length === 0) {
      try {
        await workspaceStore.fetchWorkspaces()
      } catch {
        return next('/login')
      }
    }

    if (!workspaceStore.currentWorkspace) {
      if (workspaceStore.workspaces.length === 0) {
        return next('/workspaces')
      }
      workspaceStore.setCurrentWorkspace(workspaceStore.workspaces[0])
    }
  }

  next()
})

export default router
