import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // Routes publiques / non-entreprise
    {
      path: '/',
      name: 'landing',
      component: () => import('@/pages/LandingPage.vue'),
      meta: { layout: 'landing' }
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/pages/auth/LoginPage.vue'),
      meta: { guest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/pages/auth/RegisterPage.vue'),
      meta: { guest: true }
    },
    {
      path: '/auth/callback',
      name: 'auth-callback',
      component: () => import('@/pages/auth/AuthCallbackPage.vue'),
      meta: { guest: true }
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('@/pages/auth/ForgotPasswordPage.vue'),
      meta: { guest: true }
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: () => import('@/pages/auth/ResetPasswordPage.vue'),
      meta: { guest: true }
    },
    {
      path: '/invitation/accept',
      name: 'invitation-accept',
      component: () => import('@/pages/invitation/AcceptInvitationPage.vue')
    },
    {
      path: '/confirm-delete',
      name: 'confirm-delete',
      component: () => import('@/pages/account/ConfirmDeletePage.vue')
    },
    {
      path: '/unauthorized',
      name: 'unauthorized',
      component: () => import('@/pages/UnauthorizedPage.vue')
    },

    // Routes entreprise-scoped — préfixées par /:entrepriseSlug
    {
      path: '/:entrepriseSlug',
      meta: { requiresAuth: true, requiresEntreprise: true },
      children: [
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('@/pages/DashboardPage.vue')
        },
        {
          path: 'rooms',
          name: 'rooms',
          component: () => import('@/pages/room/RoomsPage.vue')
        },
        {
          path: 'room/:id',
          name: 'room',
          component: () => import('@/pages/room/RoomPage.vue')
        },
        {
          path: 'calendar',
          name: 'calendar',
          component: () => import('@/pages/calendar/CalendarPage.vue')
        },
        {
          path: 'invitation/create',
          name: 'invitation-create',
          component: () => import('@/pages/invitation/CreateInvitationPage.vue'),
          meta: { requiresAdmin: true }
        },
        {
          path: 'profile',
          name: 'profile',
          component: () => import('@/pages/account/ProfilePage.vue')
        },
        {
          path: 'admin/users',
          name: 'admin-users',
          component: () => import('@/pages/admin/UsersPage.vue'),
          meta: { requiresAdmin: true }
        },
        {
          path: 'admin/ai-settings',
          name: 'admin-ai-settings',
          component: () => import('@/pages/admin/AiSettingsPage.vue'),
          meta: { requiresAdmin: true }
        }
      ]
    },

    // 404
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/pages/NotFoundPage.vue')
    }
  ]
})

router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore()

  // Attendre que le store soit initialisé (fetchUser terminé) avant toute décision
  await authStore.waitForInit()

  // Connecté → landing ou pages guest → rediriger vers dashboard entreprise
  if (authStore.isAuthenticated && (to.name === 'landing' || to.meta.guest)) {
    const slug = authStore.getFirstEntrepriseSlug()
    // Si user chargé avec une entreprise → dashboard, sinon rester sur landing (API peut-être down)
    if (slug) return next({ name: 'dashboard', params: { entrepriseSlug: slug } })
    if (authStore.user) return next() // connecté mais sans entreprise → landing ok
    return next() // API down mais token valide → landing (pas de déconnexion forcée)
  }

  // Non connecté → page protégée → landing
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'landing' })
  }

  // Vérification du slug entreprise pour les routes /:entrepriseSlug/*
  if (to.params.entrepriseSlug) {
    const slug = to.params.entrepriseSlug as string
    const membership = authStore.user?.entreprises.find(e => e.slug === slug)
    if (!membership) {
      return next({ name: 'unauthorized' })
    }
    authStore.setCurrentEntreprise(slug)
  }

  // Vérification du rôle admin (per-enterprise)
  if (to.meta.requiresAdmin) {
    const slug = to.params.entrepriseSlug as string
    const membership = authStore.user?.entreprises.find(e => e.slug === slug)
    if (membership?.role !== 'admin') {
      return next({ name: 'rooms', params: { entrepriseSlug: slug } })
    }
  }

  next()
})

export default router
