import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: () => import('../layouts/PublicLayout.vue'),
      children: [
        { path: '', name: 'home', component: () => import('../views/HomePage.vue') },
        { path: 'about', name: 'about', component: () => import('../views/AboutPage.vue') },
        { path: 'contact', name: 'contact', component: () => import('../views/ContactPage.vue') }
      ]
    },
    {
      path: '/auth',
      component: () => import('../layouts/AuthLayout.vue'),
      meta: { requiresGuest: true },
      children: [
        { path: 'login', name: 'login', component: () => import('../views/LoginPage.vue') },
        { path: 'register', name: 'register', component: () => import('../views/RegisterPage.vue') }
      ]
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/DashboardPage.vue'),
      meta: { requiresAuth: true }
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
    next({ name: 'dashboard' }) // Redirect authenticated users away from auth pages
  } else {
    next()
  }
})

export default router
