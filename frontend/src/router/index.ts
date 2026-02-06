import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
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
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/pages/DashboardPage.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/rooms',
      name: 'rooms',
      component: () => import('@/pages/room/RoomsPage.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/room/:id',
      name: 'room',
      component: () => import('@/pages/room/RoomPage.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/calendar',
      name: 'calendar',
      component: () => import('@/pages/calendar/CalendarPage.vue'),
      meta: { requiresAuth: true }
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
      path: '/invitation/create',
      name: 'invitation-create',
      component: () => import('@/pages/invitation/CreateInvitationPage.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/confirm-delete',
      name: 'confirm-delete',
      component: () => import('@/pages/account/ConfirmDeletePage.vue')
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('@/pages/account/ProfilePage.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/admin/users',
      name: 'admin-users',
      component: () => import('@/pages/admin/UsersPage.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/pages/NotFoundPage.vue')
    }
  ]
})

router.beforeEach((to, _from, next) => {
  const authStore = useAuthStore()

  // Si connecté et qu'on essaie d'aller sur landing ou pages guest → redirect vers rooms
  if (authStore.isAuthenticated && (to.name === 'landing' || to.meta.guest)) {
    next({ name: 'rooms' })
  }
  // Si non connecté et page protégée → redirect vers landing
  else if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'landing' })
  }
  // Si pas admin et page admin → redirect vers rooms
  else if (to.meta.requiresAdmin && authStore.user?.role !== 'admin') {
    next({ name: 'rooms' })
  }
  else {
    next()
  }
})

export default router
