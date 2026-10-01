<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useDemandStore } from '@/stores/demandStore'
import { useNotificationStore } from '@/stores/notificationStore'
import { usePayment } from '@/composables/usePayment'
import { useChatEntry } from '@/composables/useChatEntry'
import { PAYMENT_OPTION } from '@/constants/payment'
import OfferCard from '@/components/molecules/OfferCard.vue'
import DeliveryAddressCard from '@/components/molecules/DeliveryAddressCard.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import AppModal from '@/components/molecules/AppModal.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppAlert from '@/components/atoms/AppAlert.vue'
import SkeletonCard from '@/components/atoms/SkeletonCard.vue'
import StatusBadge from '@/components/atoms/StatusBadge.vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const demandStore = useDemandStore()
const notificationStore = useNotificationStore()
const { startOfferCheckout, loading: checkoutLoading } = usePayment()
const { openChat } = useChatEntry()

const DEMAND_TABS = [
  { id: 'all', name: 'All Demands', status: null },
  { id: 'open', name: 'Open', status: 'open' },
  { id: 'fully_allocated', name: 'Fully Allocated', status: 'fully_allocated' },
  { id: 'fulfilled', name: 'Fulfilled', status: 'fulfilled' },
  { id: 'cancelled', name: 'Cancelled', status: 'cancelled' },
  { id: 'expired', name: 'Expired', status: 'expired' },
]

const expandedId = ref(null)
const showPayModal = ref(false)
const payingOffer = ref(null)
const actingId = ref(null)
const payError = ref('')
const currentTab = ref('all')
const pendingConfirm = ref(null)

const confirmConfig = computed(() => {
  if (!pendingConfirm.value) return null
  const { action, offer, demand, paymentOption } = pendingConfirm.value
  const offerSummary = offer != null ? `${offer.quantity_kg} kg at ₱${offer.price_per_kg}/kg` : ''
  switch (action) {
    case 'accept-offer':
      return {
        title: 'Accept this offer?',
        message: `Accept the offer of ${offerSummary}? The farmer will be notified to proceed.`,
        confirmText: 'Accept offer',
        type: 'primary',
      }
    case 'reject-offer':
      return {
        title: 'Reject this offer?',
        message: `Reject the offer of ${offerSummary}? This cannot be undone.`,
        confirmText: 'Reject offer',
        type: 'danger',
      }
    case 'cancel-offer':
      return {
        title: 'Cancel this offer?',
        message: `Cancel the offer of ${offerSummary}? This cannot be undone.`,
        confirmText: 'Cancel offer',
        type: 'danger',
      }
    case 'confirm-completed':
      return {
        title: 'Confirm delivery?',
        message: `Confirm you received the full ${offerSummary} delivery? This releases the order as complete.`,
        confirmText: 'Confirm delivery',
        type: 'primary',
      }
    case 'cancel-demand':
      return {
        title: 'Cancel this demand?',
        message: `Cancel your demand "${demand?.title || ''}"? All pending offers on it will be closed. This cannot be undone.`,
        confirmText: 'Cancel demand',
        type: 'danger',
      }
    case 'pay':
      return {
        title: paymentOption === PAYMENT_OPTION.CASH ? 'Request cash payment?' : 'Pay online now?',
        message:
          paymentOption === PAYMENT_OPTION.CASH
            ? `Request to pay ${offerSummary} in cash? The farmer must approve before delivery.`
            : `Pay ₱${Number(offer?.total_price || 0).toLocaleString('en-PH')} online now for ${offerSummary}?`,
        confirmText: paymentOption === PAYMENT_OPTION.CASH ? 'Request cash payment' : 'Pay now',
        type: 'primary',
      }
    default:
      return null
  }
})

const activeTab = computed(() => DEMAND_TABS.find((tab) => tab.id === currentTab.value))

const emptyTitle = computed(() =>
  currentTab.value === 'all'
    ? 'No demands yet'
    : `No ${activeTab.value.name.toLowerCase()} demands`,
)

const emptyDescription = computed(() =>
  currentTab.value === 'all'
    ? 'Post your first crop demand and let farmers compete for it.'
    : 'Demands with this status will appear here.',
)

onMounted(() => {
  demandStore.fetchMyDemands()
})

function refreshDemands() {
  demandStore.fetchMyDemands(activeTab.value?.status || null)
}

function handleTabChange(tabId) {
  if (tabId === currentTab.value) return
  currentTab.value = tabId
  expandedId.value = null
  refreshDemands()
}

function isActing(offer) {
  return actingId.value === offer.id
}

async function toggleOffers(demand) {
  if (expandedId.value === demand.id) {
    expandedId.value = null
    return
  }
  expandedId.value = demand.id
  try {
    await demandStore.fetchDemandOffers(demand.id)
  } catch {
    notificationStore.error('Failed to load offers.')
  }
}

async function runAction(offer, action, successMessage) {
  actingId.value = offer.id
  try {
    const updated = await action()
    notificationStore.success(successMessage)
    if (['accepted', 'rejected', 'cancelled'].includes(updated.status)) {
      refreshDemands()
    }
  } catch (err) {
    notificationStore.error(err.response?.data?.message || 'Action failed. Please try again.')
  } finally {
    actingId.value = null
  }
}

function handleAccept(offer) {
  pendingConfirm.value = { action: 'accept-offer', offer }
}

function handleReject(offer) {
  pendingConfirm.value = { action: 'reject-offer', offer }
}

function handleCancelOffer(offer) {
  pendingConfirm.value = { action: 'cancel-offer', offer }
}

function handleConfirmCompleted(offer) {
  pendingConfirm.value = { action: 'confirm-completed', offer }
}

function handleCancelDemand(demand) {
  pendingConfirm.value = { action: 'cancel-demand', demand }
}

function requestPay(paymentOption) {
  pendingConfirm.value = { action: 'pay', offer: payingOffer.value, paymentOption }
  showPayModal.value = false
}

async function confirmPending() {
  const pending = pendingConfirm.value
  pendingConfirm.value = null
  if (!pending) return
  switch (pending.action) {
    case 'accept-offer':
      await runAction(
        pending.offer,
        () => demandStore.decideOffer(pending.offer.id, 'accept'),
        'Offer accepted!',
      )
      break
    case 'reject-offer':
      await runAction(
        pending.offer,
        () => demandStore.decideOffer(pending.offer.id, 'reject'),
        'Offer rejected.',
      )
      break
    case 'cancel-offer':
      await runAction(
        pending.offer,
        () => demandStore.cancelOffer(pending.offer.id, false),
        'Offer cancelled.',
      )
      break
    case 'confirm-completed':
      await runAction(
        pending.offer,
        () => demandStore.confirmCompleted(pending.offer.id),
        'Delivery confirmed. Thank you!',
      )
      break
    case 'cancel-demand':
      await runAction(
        { id: `demand-${pending.demand.id}` },
        () => demandStore.cancelDemand(pending.demand.id),
        'Demand cancelled.',
      )
      break
    case 'pay':
      payingOffer.value = pending.offer
      await handlePay(pending.paymentOption)
      break
    default:
      break
  }
}

function openPayModal(offer) {
  payingOffer.value = offer
  payError.value = ''
  showPayModal.value = true
}

async function handlePay(paymentOption) {
  payError.value = ''
  try {
    await startOfferCheckout(payingOffer.value.id, paymentOption)
    if (paymentOption === PAYMENT_OPTION.CASH) {
      notificationStore.success('Cash payment request sent! Waiting for farmer approval.')
      showPayModal.value = false
      refreshDemands()
      if (expandedId.value) demandStore.fetchDemandOffers(expandedId.value)
    }
  } catch (err) {
    payError.value = err.response?.data?.message || err.message || 'Payment failed.'
  }
}

function handleMessage(offer) {
  openChat(offer.farmer?.id)
}

function offersFor(demand) {
  if (expandedId.value !== demand.id) return []
  return demandStore.demandOffers
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="font-serif text-3xl font-bold text-stone-900">My Demands</h1>
        <p class="text-base text-stone-600 font-light mt-1">
          Review competing offers and pay for the ones you accept.
        </p>
      </div>
      <RouterLink :to="{ name: 'buyer-post-demand' }">
        <AppButton variant="primary">Post a demand</AppButton>
      </RouterLink>
    </div>

    <div class="bg-white shadow-soft rounded-2xl border border-stone-200 overflow-hidden">
      <div class="border-b border-stone-200">
        <nav
          class="-mb-px flex space-x-8 px-6 overflow-x-auto"
          aria-label="Filter demands by status"
        >
          <button
            v-for="tab in DEMAND_TABS"
            :key="tab.id"
            type="button"
            @click="handleTabChange(tab.id)"
            :class="[
              currentTab === tab.id
                ? 'border-moss-500 text-moss-700'
                : 'border-transparent text-stone-500 hover:text-stone-700 hover:border-stone-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
            ]"
          >
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <div v-if="demandStore.loading.demands" class="p-5 space-y-5">
        <SkeletonCard v-for="n in 3" :key="n" />
      </div>

      <EmptyState
        v-else-if="demandStore.myDemands.length === 0"
        :title="emptyTitle"
        :description="emptyDescription"
      />

      <div v-else class="divide-y divide-stone-200">
        <div
          v-for="demand in demandStore.myDemands"
          :key="demand.id"
          class="p-5 sm:p-6 hover:bg-stone-50 transition-colors duration-200"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <h2 class="font-serif text-2xl font-bold text-stone-900">
                {{ demand.title }}
              </h2>
              <p class="text-sm text-stone-500 mt-0.5">
                {{ demand.crop_name }} · {{ demand.remaining_quantity_kg }} of
                {{ demand.quantity_kg }} kg remaining ·
                {{ demand.pending_offers_count ?? 0 }} pending offers
              </p>
            </div>
            <div class="flex flex-col items-end gap-2 flex-shrink-0">
              <div class="flex items-center gap-2">
                <StatusBadge :status="demand.status" size="sm" />
                <button
                  type="button"
                  @click="toggleOffers(demand)"
                  :aria-expanded="expandedId === demand.id"
                  :aria-label="expandedId === demand.id ? 'Hide offers' : 'Show offers'"
                  class="w-8 h-8 rounded-full bg-stone-100 hover:bg-moss-100 text-stone-500 hover:text-moss-700 flex items-center justify-center transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500"
                >
                  <ChevronDownIcon
                    class="h-5 w-5 transition-transform duration-300"
                    :class="{ 'rotate-180': expandedId === demand.id }"
                    aria-hidden="true"
                  />
                </button>
              </div>
              <AppButton
                v-if="['open', 'fully_allocated'].includes(demand.status)"
                size="sm"
                variant="ghost"
                :loading="actingId === `demand-${demand.id}`"
                @click="handleCancelDemand(demand)"
              >
                Cancel demand
              </AppButton>
            </div>
          </div>

          <div v-if="demand.delivery_address" class="mt-4">
            <DeliveryAddressCard :address="demand.delivery_address" title="Your delivery address" />
          </div>

          <div v-if="expandedId === demand.id" class="mt-4 pt-4 border-t border-stone-200">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
              <h3 class="font-serif text-lg font-bold text-stone-900 flex items-center gap-2">
                Competing offers
                <span
                  v-if="!demandStore.loading.offers"
                  class="rounded-full bg-moss-100 text-moss-800 text-xs font-semibold px-2.5 py-0.5"
                >
                  {{ offersFor(demand).length }}
                </span>
              </h3>
              <p class="text-xs text-stone-500">
                Compare prices side by side, then accept the best.
              </p>
            </div>
            <div
              v-if="demandStore.loading.offers"
              class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"
            >
              <SkeletonCard v-for="n in 3" :key="n" />
            </div>
            <EmptyState
              v-else-if="offersFor(demand).length === 0"
              title="No offers yet"
              description="Farmers have not responded to this demand yet."
            />
            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
              <OfferCard
                v-for="offer in offersFor(demand)"
                :key="offer.id"
                :offer="offer"
                viewer-role="buyer"
                :loading="isActing(offer)"
                @accept="handleAccept"
                @reject="handleReject"
                @pay="openPayModal"
                @cancel="handleCancelOffer"
                @confirm-completed="handleConfirmCompleted"
                @message="handleMessage"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <AppModal :is-open="showPayModal" @close="showPayModal = false">
      <div v-if="payingOffer" class="space-y-5">
        <div>
          <h2 class="font-serif text-2xl font-bold text-stone-900">Pay for this offer</h2>
          <p class="text-stone-600 text-sm mt-1">
            {{ payingOffer.quantity_kg }} kg × ₱{{ payingOffer.price_per_kg }}/kg =
            <span class="font-semibold text-stone-900">
              ₱{{ Number(payingOffer.total_price).toLocaleString('en-PH') }}
            </span>
          </p>
        </div>
        <AppAlert v-if="payError" type="error">{{ payError }}</AppAlert>
        <div class="flex flex-col sm:flex-row justify-end gap-3">
          <AppButton variant="ghost" @click="showPayModal = false">Cancel</AppButton>
          <AppButton
            variant="secondary"
            :loading="checkoutLoading"
            @click="requestPay(PAYMENT_OPTION.CASH)"
          >
            Pay with cash
          </AppButton>
          <AppButton
            variant="primary"
            :loading="checkoutLoading"
            @click="requestPay(PAYMENT_OPTION.PAYMONGO)"
          >
            Pay online
          </AppButton>
        </div>
      </div>
    </AppModal>

    <ConfirmModal
      v-if="confirmConfig"
      :is-open="pendingConfirm !== null"
      :title="confirmConfig.title"
      :message="confirmConfig.message"
      :confirm-text="confirmConfig.confirmText"
      :type="confirmConfig.type"
      @confirm="confirmPending"
      @cancel="pendingConfirm = null"
    />
  </div>
</template>
