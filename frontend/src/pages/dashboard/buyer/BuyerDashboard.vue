<script setup>
import { onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import {
  ShoppingBagIcon,
  CheckCircleIcon,
  BanknotesIcon,
  ArrowUpRightIcon,
} from '@heroicons/vue/24/outline'
import { PAYMENT_STATUS } from '@/constants/payment'
import { useMarketStore } from '@/stores/marketStore'
import { useNotificationStore } from '@/stores/notificationStore'
import { useApi } from '@/composables/useApi'
import { useConfirmModal } from '@/composables/useConfirmModal'
import { useChatEntry } from '@/composables/useChatEntry'
import AppCard from '@/components/atoms/AppCard.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import SkeletonCard from '@/components/atoms/SkeletonCard.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import StatCard from '@/components/molecules/StatCard.vue'
import PurchaseCard from '@/components/molecules/PurchaseCard.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import LoadingState from '@/components/molecules/LoadingState.vue'
import PaginationControls from '@/components/molecules/PaginationControls.vue'
const marketStore = useMarketStore()
const notificationStore = useNotificationStore()
const { t } = useI18n()
const api = useApi()
const { isOpen, isExecuting, config, confirm, execute, cancel } = useConfirmModal()
const { openChat } = useChatEntry()
const OVERVIEW_FILTERS = { search: '', sort: 'newest', status: null }
const FIRST_PAGE = 1
const SKELETON_COUNT = 2
const purchases = computed(() => marketStore.buyerPurchases ?? [])
const loading = computed(() => marketStore.loading.purchases)
const error = computed(() => marketStore.buyerPurchasesError)
const totalSpent = computed(() =>
  purchases.value.reduce((sum, purchase) => sum + parseFloat(purchase.amount_paid || 0), 0),
)
const completedPayments = computed(
  () =>
    purchases.value.filter((purchase) => purchase.payment_status === PAYMENT_STATUS.COMPLETED)
      .length,
)
const currency = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' })
function fetchPurchases(page = FIRST_PAGE) {
  return marketStore.fetchBuyerPurchases(page, { filters: OVERVIEW_FILTERS })
}
async function performCancellation(purchase) {
  try {
    await api.post(`/checkout/${purchase.id}/cancel`)
    await fetchPurchases(marketStore.buyerPurchasesPagination.currentPage)
  } catch {
    notificationStore.addNotification({
      type: 'error',
      messageKey: 'buyer.dashboard.cancel_error',
    })
  }
}
function cancelPurchase(purchase) {
  confirm(
    {
      title: t('buyer.dashboard.cancel_title'),
      message: t('buyer.dashboard.cancel_message'),
      type: 'danger',
    },
    () => performCancellation(purchase),
  )
}
function messageFarmer(purchase) {
  const farmer = purchase.contract?.farmer || purchase.demand_offer?.farmer
  if (!farmer) return
  confirm(
    {
      title: t('farmer.offers.msg_title'),
      message: t('buyer.dashboard.msg_message', {
        name: farmer.name || t('buyer.dashboard.msg_farmer_fallback'),
      }),
      confirmText: t('buyer.dashboard.msg_chat'),
    },
    () => openChat(farmer.id),
  )
}
onMounted(() => fetchPurchases())
</script>
<template>
  <div class="space-y-8" :aria-busy="loading">
    <PageHeader :title="t('buyer.dashboard.title')" :description="t('buyer.dashboard.description')"
      ><template #actions
        ><RouterLink
          :to="{ name: 'buyer-marketplace' }"
          class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-gradient-to-br from-moss-500 to-moss-600 px-5 py-3 text-sm font-medium text-white transition-all duration-300 hover:scale-[1.02] hover:from-moss-600 hover:to-moss-700 motion-reduce:transform-none"
          >{{ t('buyer.dashboard.browse') }}
          <ArrowUpRightIcon class="h-4 w-4" aria-hidden="true" /></RouterLink></template
    ></PageHeader>
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
      <StatCard
        :label="t('buyer.dashboard.stat_purchases')"
        :value="loading || error ? null : purchases.length"
        :loading="loading"
        ><template #icon><ShoppingBagIcon class="h-5 w-5" /></template
      ></StatCard>
      <StatCard
        :label="t('buyer.dashboard.stat_completed')"
        :value="loading || error ? null : completedPayments"
        :loading="loading"
        ><template #icon><CheckCircleIcon class="h-5 w-5" /></template
      ></StatCard>
      <StatCard
        :label="t('buyer.dashboard.stat_amount')"
        :value="loading || error ? null : currency.format(totalSpent)"
        :loading="loading"
        tone="harvest"
        ><template #icon><BanknotesIcon class="h-5 w-5" /></template
      ></StatCard>
    </div>
    <section aria-labelledby="purchases-heading" class="space-y-5">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 id="purchases-heading" class="font-serif text-2xl font-bold text-stone-900">
          {{ t('buyer.dashboard.your_purchases') }}
        </h2>
        <RouterLink
          :to="{ name: 'buyer-purchases' }"
          class="inline-flex min-h-11 items-center rounded-xl text-sm font-medium text-moss-700 transition-colors hover:text-moss-900"
          >{{ t('buyer.dashboard.history') }}</RouterLink
        >
      </div>
      <LoadingState v-if="loading" :label="t('buyer.dashboard.loading')"
        ><div class="space-y-4">
          <SkeletonCard v-for="index in SKELETON_COUNT" :key="index" with-avatar with-action /></div
      ></LoadingState>
      <AppCard v-else-if="error" role="alert"
        ><h3 class="font-serif text-xl font-bold text-stone-900">
          {{ t('buyer.dashboard.load_error') }}
        </h3>
        <p class="mt-2 text-sm text-stone-600">{{ t('buyer.dashboard.retry_hint') }}</p>
        <AppButton variant="secondary" class="mt-4" @click="fetchPurchases()">{{
          t('shell.retry')
        }}</AppButton></AppCard
      >
      <EmptyState
        v-else-if="!purchases.length"
        :title="t('buyer.dashboard.empty_title')"
        :description="t('buyer.dashboard.empty_desc')"
        ><template #action
          ><RouterLink
            :to="{ name: 'buyer-marketplace' }"
            class="inline-flex min-h-11 items-center rounded-xl bg-moss-600 px-5 py-3 text-sm font-medium text-white transition-colors hover:bg-moss-700"
            >{{ t('buyer.dashboard.explore') }}</RouterLink
          ></template
        ></EmptyState
      >
      <div v-else class="space-y-4">
        <PurchaseCard
          v-for="purchase in purchases"
          :key="purchase.id"
          :purchase="purchase"
          @cancel="cancelPurchase"
          @message="messageFarmer"
        />
      </div>
      <PaginationControls
        v-if="!loading && !error"
        :current-page="marketStore.buyerPurchasesPagination.currentPage"
        :last-page="marketStore.buyerPurchasesPagination.lastPage"
        :total="marketStore.buyerPurchasesPagination.total"
        @page-change="fetchPurchases"
      />
    </section>
    <ConfirmModal
      :is-open="isOpen"
      :title="config.title"
      :message="config.message"
      :type="config.type"
      :confirm-text="config.confirmText"
      :loading="isExecuting"
      @confirm="execute"
      @cancel="cancel"
    />
  </div>
</template>
