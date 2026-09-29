<script setup>
import { onMounted, ref, computed } from 'vue'
import { useDemandStore } from '@/stores/demandStore'
import { useNotificationStore } from '@/stores/notificationStore'
import { useChatEntry } from '@/composables/useChatEntry'
import OfferCard from '@/components/molecules/OfferCard.vue'
import DeliveryAddressCard from '@/components/molecules/DeliveryAddressCard.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import SkeletonCard from '@/components/atoms/SkeletonCard.vue'
import StatusBadge from '@/components/atoms/StatusBadge.vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const demandStore = useDemandStore()
const notificationStore = useNotificationStore()
const { openChat } = useChatEntry()

const OFFER_TABS = [
  { id: 'all', name: 'All Offers', status: null },
  { id: 'pending', name: 'Pending', status: 'pending' },
  { id: 'accepted', name: 'Accepted', status: 'accepted' },
  { id: 'paid', name: 'Paid', status: 'partially_paid,paid' },
  { id: 'delivered', name: 'Delivered', status: 'delivered' },
  { id: 'completed', name: 'Completed', status: 'completed' },
  { id: 'closed', name: 'Closed', status: 'rejected,withdrawn,cancelled,expired' },
]

const actingId = ref(null)
const pendingConfirm = ref(null)
const currentTab = ref('all')
const expandedAddressId = ref(null)

const activeTab = computed(() => OFFER_TABS.find((tab) => tab.id === currentTab.value))

const emptyTitle = computed(() =>
  currentTab.value === 'all' ? 'No offers yet' : `No ${activeTab.value.name.toLowerCase()} offers`,
)

const emptyDescription = computed(() =>
  currentTab.value === 'all'
    ? 'Browse buyer demands and send your first offer.'
    : 'Offers with this status will appear here.',
)

const confirmConfig = computed(() => {
  if (!pendingConfirm.value) return null
  const { action, offer } = pendingConfirm.value
  const configs = {
    withdraw: {
      title: 'Withdraw offer?',
      message: `Withdraw your offer of ${offer.quantity_kg} kg? The buyer will no longer see it, but you can submit a new one while the demand is still open.`,
      confirmText: 'Withdraw',
      type: 'danger',
    },
    cancel: {
      title: 'Cancel this offer?',
      message: `Cancel your accepted offer of ${offer.quantity_kg} kg? This releases the reserved quantity back to the buyer and cannot be undone.`,
      confirmText: 'Cancel offer',
      type: 'danger',
    },
    'mark-delivered': {
      title: 'Mark as delivered?',
      message: `Confirm you have delivered ${offer.quantity_kg} kg to the buyer. The buyer will be asked to confirm receipt.`,
      confirmText: 'Mark delivered',
      type: 'primary',
    },
    'settle-balance': {
      title: 'Confirm full payment?',
      message: `Confirm the buyer paid the remaining ₱${remainingBalance(offer).toLocaleString('en-PH')} on delivery for ${offer.quantity_kg} kg? This marks the order as paid in full.`,
      confirmText: 'Confirm full payment',
      type: 'primary',
    },
  }
  return configs[action]
})

function remainingBalance(offer) {
  const purchase = offer.purchase || {}
  return Number(purchase.total_contract_amount || 0) - Number(purchase.amount_paid || 0)
}

function askConfirm(action, offer) {
  pendingConfirm.value = { action, offer }
}

async function confirmPending() {
  const pending = pendingConfirm.value
  pendingConfirm.value = null
  if (!pending) return
  if (pending.action === 'withdraw') {
    await runAction(
      pending.offer,
      () => demandStore.withdrawOffer(pending.offer.id),
      'Offer withdrawn.',
    )
  } else if (pending.action === 'cancel') {
    await runAction(
      pending.offer,
      () => demandStore.cancelOffer(pending.offer.id, true),
      'Offer cancelled.',
    )
  } else if (pending.action === 'mark-delivered') {
    await runAction(
      pending.offer,
      () => demandStore.markDelivered(pending.offer.id),
      'Marked as delivered. Waiting for buyer confirmation.',
    )
  } else if (pending.action === 'settle-balance') {
    await runAction(
      pending.offer,
      () => demandStore.settleBalance(pending.offer.id),
      'Full payment confirmed. The order is now settled.',
    )
  }
}

onMounted(() => {
  demandStore.fetchMyOffers()
})

function handleTabChange(tabId) {
  if (tabId === currentTab.value) return
  currentTab.value = tabId
  expandedAddressId.value = null
  demandStore.fetchMyOffers(activeTab.value?.status || null)
}

function toggleAddress(offer) {
  expandedAddressId.value = expandedAddressId.value === offer.id ? null : offer.id
}

function isAddressExpanded(offer) {
  return expandedAddressId.value === offer.id
}

function isActing(offer) {
  return actingId.value === offer.id
}

async function runAction(offer, action, successMessage) {
  actingId.value = offer.id
  try {
    await action()
    notificationStore.success(successMessage)
  } catch (err) {
    notificationStore.error(err.response?.data?.message || 'Action failed. Please try again.')
  } finally {
    actingId.value = null
  }
}

function handleWithdraw(offer) {
  askConfirm('withdraw', offer)
}

function handleCancel(offer) {
  askConfirm('cancel', offer)
}

function handleMarkDelivered(offer) {
  askConfirm('mark-delivered', offer)
}

function handleSettleBalance(offer) {
  askConfirm('settle-balance', offer)
}

function handleMessage(offer) {
  openChat(offer.demand?.buyer?.id)
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="font-serif text-3xl font-bold text-stone-900">My Offers</h1>
      <p class="text-base text-stone-600 font-light mt-1">
        Track your offers to buyers, from pending to delivered.
      </p>
    </div>

    <div class="bg-white shadow-soft rounded-2xl border border-stone-200 overflow-hidden">
      <div class="border-b border-stone-200">
        <nav
          class="-mb-px flex space-x-8 px-6 overflow-x-auto"
          aria-label="Filter offers by status"
        >
          <button
            v-for="tab in OFFER_TABS"
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

      <div v-if="demandStore.loading.offers" class="p-5 space-y-5">
        <SkeletonCard v-for="n in 3" :key="n" />
      </div>

      <EmptyState
        v-else-if="demandStore.myOffers.length === 0"
        :title="emptyTitle"
        :description="emptyDescription"
      />

      <div v-else class="divide-y divide-stone-200">
        <div
          v-for="offer in demandStore.myOffers"
          :key="offer.id"
          class="p-5 sm:p-6 hover:bg-stone-50 transition-colors duration-200"
        >
          <div class="flex items-start justify-between gap-3 mb-4">
            <div>
              <h2 class="font-serif text-2xl font-bold text-stone-900">
                {{ offer.demand?.title || 'Crop demand' }}
              </h2>
              <p class="text-sm text-stone-500 mt-0.5">
                {{ offer.demand?.crop_name }} · Buyer: {{ offer.demand?.buyer?.name || '—' }}
              </p>
            </div>
            <StatusBadge :status="offer.demand?.status || 'open'" size="sm" />
          </div>

          <OfferCard
            :offer="offer"
            viewer-role="farmer"
            :loading="isActing(offer)"
            @withdraw="handleWithdraw"
            @cancel="handleCancel"
            @mark-delivered="handleMarkDelivered"
            @settle-balance="handleSettleBalance"
            @message="handleMessage"
          />

          <div v-if="offer.demand?.delivery_address" class="mt-4">
            <button
              type="button"
              @click="toggleAddress(offer)"
              :aria-expanded="isAddressExpanded(offer)"
              :aria-label="
                isAddressExpanded(offer) ? 'Hide delivery address' : 'Show delivery address'
              "
              class="inline-flex items-center gap-1.5 text-sm font-medium text-moss-700 hover:text-moss-800 transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 rounded-lg"
            >
              <ChevronDownIcon
                class="h-4 w-4 transition-transform duration-300"
                :class="{ 'rotate-180': isAddressExpanded(offer) }"
                aria-hidden="true"
              />
              {{ isAddressExpanded(offer) ? 'Hide delivery address' : 'Show delivery address' }}
            </button>
            <DeliveryAddressCard
              v-if="isAddressExpanded(offer)"
              :address="offer.demand.delivery_address"
              class="mt-3"
            />
          </div>
        </div>
      </div>
    </div>

    <ConfirmModal
      :is-open="pendingConfirm !== null"
      :title="confirmConfig?.title || 'Confirm Action'"
      :message="confirmConfig?.message || ''"
      :confirm-text="confirmConfig?.confirmText || 'Confirm'"
      :type="confirmConfig?.type || 'primary'"
      @confirm="confirmPending"
      @cancel="pendingConfirm = null"
    />
  </div>
</template>
