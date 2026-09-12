import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: () => import('../components/templates/PublicLayout.vue'),
      children: [
        { path: '', name: 'home', component: () => import('../pages/public/HomePage.vue') },
        { path: 'about', name: 'about', component: () => import('../pages/public/AboutPage.vue') },
        { path: 'contact', name: 'contact', component: () => import('../pages/public/ContactPage.vue') }
      ]
    },
    {
      path: '/auth',
      component: () => import('../components/templates/AuthLayout.vue'),
      meta: { requiresGuest: true },
      children: [
        { path: 'login', name: 'login', component: () => import('../pages/auth/LoginPage.vue') },
        { path: 'register', name: 'register', component: () => import('../pages/auth/RegisterPage.vue') }
      ]
    },
    {
      path: '/dashboard',
      component: () => import('../components/templates/DashboardLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        { path: '', name: 'dashboard', component: () => import('../pages/dashboard/FarmerDashboard.vue') },
        { path: 'farms', name: 'farm-manager', component: () => import('../pages/dashboard/FarmManager.vue') },
        { path: 'farms/:farmId/plots', name: 'plot-planner', component: () => import('../pages/dashboard/PlotPlanner.vue') }
      ]
    }
  ]
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Try to fetch user if token exists but user isn't loaded
  if (authStore.token && !authStore.user) {
    await authStore.fetchUser()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login' })
  } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
  } else {
    next()
  }
})

export default router
