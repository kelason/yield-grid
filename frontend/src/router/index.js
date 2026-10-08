import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { USER_ROLES, roleHomeTarget } from '../constants/roles'

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
      children: [
        {
          path: 'login',
          name: 'login',
          component: () => import('../pages/auth/LoginPage.vue'),
          meta: { requiresGuest: true },
        },
        {
          path: 'register',
          name: 'register',
          component: () => import('../pages/auth/RegisterPage.vue'),
          meta: { requiresGuest: true },
        },
        {
          path: 'verify-email',
          name: 'verify-email',
          component: () => import('../pages/auth/EmailVerificationCallback.vue'),
        },
        {
          path: 'verification-required',
          name: 'verification-required',
          component: () => import('../pages/auth/VerifyEmailPage.vue'),
          meta: { requiresAuth: true },
        },
        {
          path: 'forgot-password',
          name: 'forgot-password',
          component: () => import('../pages/auth/ForgotPasswordPage.vue'),
          meta: { requiresGuest: true },
        },
        {
          path: 'reset-password',
          name: 'reset-password',
          component: () => import('../pages/auth/ResetPasswordPage.vue'),
          meta: { requiresGuest: true },
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
          return roleHomeTarget(useAuthStore().userRole)
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
          meta: { role: 'farmer' },
        },
        {
          path: 'farms/:farmId/plots',
          name: 'plot-planner',
          component: () => import('../pages/dashboard/PlotPlanner.vue'),
          meta: { role: 'farmer' },
        },
        {
          path: 'recommendations',
          name: 'recommendations',
          component: () => import('../pages/RecommendationsPage.vue'),
          meta: { role: 'farmer' },
        },
        {
          path: 'plots/:id/recommendations',
          name: 'crop-recommendations',
          component: () => import('../pages/RecommendationsPage.vue'),
          meta: { role: 'farmer' },
        },
        {
          path: 'compatibility',
          name: 'crop-compatibility',
          component: () => import('../pages/CompatibilityPage.vue'),
          meta: { role: 'farmer' },
        },
        {
          path: 'contracts',
          name: 'farmer-contracts',
          component: () => import('@/pages/dashboard/farmer/MyContractsPage.vue'),
          meta: { role: 'farmer' },
        },
        {
          path: 'contracts/new-listing',
          name: 'farmer-new-listing',
          component: () => import('@/pages/dashboard/farmer/ManualListingPage.vue'),
          meta: { role: 'farmer', requiresVerification: true },
        },
        {
          path: 'cash-approvals',
          name: 'farmer-cash-approvals',
          component: () => import('@/pages/dashboard/farmer/CashPaymentApprovalsPage.vue'),
          meta: { role: 'farmer', requiresVerification: true },
        },
        {
          path: 'demands/browse',
          name: 'farmer-browse-demands',
          component: () => import('@/pages/dashboard/farmer/BrowseDemandsPage.vue'),
          meta: { role: 'farmer' },
        },
        {
          path: 'farmer/offers',
          name: 'farmer-offers',
          component: () => import('@/pages/dashboard/farmer/MyOffersPage.vue'),
          meta: { role: 'farmer', requiresVerification: true },
        },
        {
          path: 'credit-score',
          name: 'credit-score',
          component: () => import('@/pages/dashboard/farmer/CreditScorePage.vue'),
          meta: { role: 'farmer', title: 'Trust Score' },
        },
        {
          path: 'insurance',
          name: 'insurance',
          component: () => import('@/pages/dashboard/farmer/InsurancePage.vue'),
          meta: { role: 'farmer', title: 'Crop Insurance' },
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
        {
          path: 'buyer/demands',
          name: 'buyer-demands',
          component: () => import('@/pages/dashboard/buyer/MyDemandsPage.vue'),
          meta: { role: 'buyer', requiresVerification: true },
        },
        {
          path: 'buyer/demands/new',
          name: 'buyer-post-demand',
          component: () => import('@/pages/dashboard/buyer/PostDemandPage.vue'),
          meta: { role: 'buyer', requiresVerification: true },
        },
        {
          path: 'community',
          name: 'community-forum',
          component: () => import('@/pages/dashboard/shared/ForumPage.vue'),
          meta: { requiresVerification: true },
        },
        {
          path: 'community/thread/:id',
          name: 'forum-thread',
          component: () => import('@/pages/dashboard/shared/ThreadPage.vue'),
          meta: { requiresVerification: true },
        },
        {
          path: 'chat',
          name: 'chat',
          component: () => import('@/pages/dashboard/shared/ChatPage.vue'),
          meta: { requiresVerification: true },
        },
        {
          path: 'users/:userId',
          name: 'user-profile',
          component: () => import('@/pages/dashboard/shared/ProfilePage.vue'),
        },
      ],
    },
    {
      path: '/admin',
      component: () => import('../components/templates/DashboardLayout.vue'),
      meta: { requiresAuth: true },
      beforeEnter: (to) => {
        if (to.path === '/admin' || to.path === '/admin/') {
          return { name: 'admin-users' }
        }
        return true
      },
      children: [
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('../pages/dashboard/admin/AdminUsersPage.vue'),
          meta: { role: 'admin', requiresVerification: true },
        },
        {
          path: 'inquiries',
          name: 'admin-inquiries',
          component: () => import('../pages/dashboard/admin/AdminInquiriesPage.vue'),
          meta: { role: 'admin', requiresVerification: true },
        },
        {
          path: 'content',
          name: 'admin-content',
          component: () => import('../pages/dashboard/admin/AdminContentPage.vue'),
          meta: { role: 'admin', requiresVerification: true },
        },
        {
          path: 'reports',
          name: 'admin-reports',
          component: () => import('../pages/dashboard/admin/AdminReportsPage.vue'),
          meta: { role: 'admin', requiresVerification: true },
        },
      ],
    },
    {
      path: '/500',
      name: 'server-error',
      component: () => import('@/pages/error/ServerErrorPage.vue'),
    },
    {
      path: '/403',
      name: 'forbidden',
      component: () => import('@/pages/error/ForbiddenPage.vue'),
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/pages/error/NotFoundPage.vue'),
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
    return roleHomeTarget(authStore.userRole)
  }

  // Block cross-role access (e.g. a buyer opening a farmer-only page)
  if (to.meta.role && authStore.userRole && authStore.userRole !== to.meta.role) {
    return { name: 'forbidden' }
  }

  // Prevent access to features that require email verification
  if (to.meta.requiresVerification && !authStore.isEmailVerified) {
    import('../stores/notificationStore').then(({ useNotificationStore }) => {
      const notificationStore = useNotificationStore()
      notificationStore.warning('Please verify your email to access this feature.')
    })

    // Unverified admins land on the standalone verification page so they never
    // bounce between /admin and a dashboard they cannot open.
    if (authStore.userRole === USER_ROLES.ADMIN) {
      return { name: 'verification-required' }
    }
    if (authStore.userRole === USER_ROLES.BUYER) {
      return { name: 'buyer-dashboard' }
    }
    if (authStore.userRole === USER_ROLES.FARMER) {
      return { name: 'farmer-dashboard' }
    }
    return { name: 'forbidden' }
  }

  return true
})

export default router
