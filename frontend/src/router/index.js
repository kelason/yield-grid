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
        {
          path: 'contact',
          name: 'contact',
          component: () => import('../pages/public/ContactPage.vue'),
        },
        {
          path: 'marketplace',
          name: 'marketplace',
          component: () => import('@/pages/public/MarketplacePage.vue'),
        },
        {
          path: 'checkout/success',
          name: 'checkout-success',
          component: () => import('@/pages/public/CheckoutSuccessPage.vue'),
        },
        {
          path: 'checkout/cancel',
          name: 'checkout-cancel',
          component: () => import('@/pages/public/CheckoutCancelPage.vue'),
        },
      ],
    },
    {
      path: '/auth',
      component: () => import('../components/templates/AuthLayout.vue'),
      meta: { requiresGuest: true },
      children: [
        { path: 'login', name: 'login', component: () => import('../pages/auth/LoginPage.vue') },
        {
          path: 'register',
          name: 'register',
          component: () => import('../pages/auth/RegisterPage.vue'),
        },
      ],
    },
    {
      path: '/dashboard',
      component: () => import('../components/templates/DashboardLayout.vue'),
      meta: { requiresAuth: true },
      beforeEnter: (to) => {
        // Redirect bare /dashboard to the role-appropriate sub-route
        if (to.path === '/dashboard' || to.path === '/dashboard/') {
          const authStore = useAuthStore()
          if (authStore.userRole === 'buyer') return { name: 'buyer-dashboard' }
          return { name: 'farmer-dashboard' }
        }
        return true
      },
      children: [
        {
          path: 'farmer',
          name: 'farmer-dashboard',
          component: () => import('../pages/dashboard/FarmerDashboard.vue'),
          meta: { role: 'farmer' },
        },
        {
          path: 'farms',
          name: 'farm-manager',
          component: () => import('../pages/dashboard/FarmManager.vue'),
        },
        {
          path: 'farms/:farmId/plots',
          name: 'plot-planner',
          component: () => import('../pages/dashboard/PlotPlanner.vue'),
        },
        {
          path: 'recommendations',
          name: 'recommendations',
          component: () => import('../pages/RecommendationsPage.vue'),
        },
        {
          path: 'plots/:id/recommendations',
          name: 'crop-recommendations',
          component: () => import('../pages/RecommendationsPage.vue'),
        },
        {
          path: 'contracts',
          name: 'farmer-contracts',
          component: () => import('@/pages/dashboard/farmer/MyContractsPage.vue'),
          meta: { role: 'farmer' },
        },
        {
          path: 'buyer',
          name: 'buyer-dashboard',
          component: () => import('@/pages/dashboard/buyer/BuyerDashboard.vue'),
          meta: { role: 'buyer' },
        },
        {
          path: 'buyer/purchases',
          name: 'buyer-purchases',
          component: () => import('@/pages/dashboard/buyer/MyPurchasesPage.vue'),
          meta: { role: 'buyer' },
        },
        {
          path: 'buyer/marketplace',
          name: 'buyer-marketplace',
          component: () => import('@/pages/public/MarketplacePage.vue'),
          meta: { role: 'buyer' },
        },
      ],
    },
  ],
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  // Try to fetch user if token exists but user isn't loaded
  if (authStore.token && !authStore.user) {
    await authStore.fetchUser()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login' }
  } else if ((to.name === 'home' || to.meta.requiresGuest) && authStore.isAuthenticated) {
    if (authStore.userRole === 'buyer') {
      return { name: 'buyer-dashboard' }
    } else {
      return { name: 'farmer-dashboard' }
    }
  }

  return true
})

export default router
