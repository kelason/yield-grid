<script setup>
import { usePendingConfirmation } from '@/composables/useConfirmModal'
import { onMounted, ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
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
const { t } = useI18n()

const OFFER_TABS = [
  {
    id: 'all',
    name: 'All Offers',
    labelKey: 'farmer.offers.tab_all',
    emptyKey: 'farmer.offers.empty_all',
    status: null,
  },
  {
    id: 'pending',
    name: 'Pending',
    labelKey: 'farmer.offers.tab_pending',
    emptyKey: 'farmer.offers.empty_pending',
    status: 'pending',
  },
  {
    id: 'accepted',
    name: 'Accepted',
    labelKey: 'farmer.offers.tab_accepted',
    emptyKey: 'farmer.offers.empty_accepted',
    status: 'accepted',
  },
  {
    id: 'paid',
    name: 'Paid',
    labelKey: 'farmer.offers.tab_paid',
    emptyKey: 'farmer.offers.empty_paid',
    status: 'partially_paid,paid',
  },
  {
    id: 'delivered',
    name: 'Delivered',
    labelKey: 'farmer.offers.tab_delivered',
    emptyKey: 'farmer.offers.empty_delivered',
    status: 'delivered',
  },
  {
    id: 'completed',
    name: 'Completed',
    labelKey: 'farmer.offers.tab_completed',
    emptyKey: 'farmer.offers.empty_completed',
    status: 'completed',
  },
  {
    id: 'closed',
    name: 'Closed',
    labelKey: 'farmer.offers.tab_closed',
    emptyKey: 'farmer.offers.empty_closed',
    status: 'rejected,withdrawn,cancelled,expired',
  },
]

const actingId = ref(null)
const pendingConfirm = ref(null)
const { isExecuting, execute, cancel } = usePendingConfirmation(pendingConfirm)
const currentTab = ref('all')
const expandedAddressId = ref(null)

const activeTab = computed(() => OFFER_TABS.find((tab) => tab.id === currentTab.value))

const emptyTitle = computed(() => t(activeTab.value?.emptyKey || 'farmer.offers.empty_all'))

const emptyDescription = computed(() =>
  currentTab.value === 'all'
    ? t('farmer.offers.empty_all_desc')
    : t('farmer.offers.empty_tab_desc'),
)

const OFFER_CONFIRMATIONS = {
  message: {
    titleKey: 'farmer.offers.msg_title',
    messageKey: 'farmer.offers.msg_message',
    params: () => ({}),
    confirmTextKey: 'farmer.offers.msg_confirm',
    type: 'primary',
  },
  withdraw: {
    titleKey: 'farmer.offers.withdraw_title',
    messageKey: 'farmer.offers.withdraw_message',
    params: (offer) => ({ qty: offer.quantity_kg }),
    confirmTextKey: 'farmer.offers.withdraw_confirm',
    type: 'danger',
  },
  cancel: {
    titleKey: 'farmer.offers.cancel_title',
    messageKey: 'farmer.offers.cancel_message',
    params: (offer) => ({ qty: offer.quantity_kg }),
    confirmTextKey: 'farmer.offers.cancel_confirm',
    type: 'danger',
  },
  'mark-delivered': {
    titleKey: 'farmer.offers.delivered_title',
    messageKey: 'farmer.offers.delivered_message',
    params: (offer) => ({ qty: offer.quantity_kg }),
    confirmTextKey: 'farmer.offers.delivered_confirm',
    type: 'primary',
  },
  'settle-balance': {
    titleKey: 'farmer.offers.settle_title',
    messageKey: 'farmer.offers.settle_message',
    params: (offer) => ({
      balance: remainingBalance(offer).toLocaleString('en-PH'),
      qty: offer.quantity_kg,
    }),
    confirmTextKey: 'farmer.offers.settle_confirm',
    type: 'primary',
  },
}
const confirmConfig = computed(() => {
  if (!pendingConfirm.value) return null
  const { action, offer } = pendingConfirm.value
  const config = OFFER_CONFIRMATIONS[action]
  if (!config) return null
  return {
    title: t(config.titleKey),
    message: t(config.messageKey, config.params(offer)),
    confirmText: t(config.confirmTextKey),
    type: config.type,
  }
})

function remainingBalance(offer) {
  const purchase = offer.purchase || {}
  return Number(purchase.total_contract_amount || 0) - Number(purchase.amount_paid || 0)
}

function askConfirm(action, offer) {
  pendingConfirm.value = { action, offer }
}

const confirmPending = () => execute(performConfirmedAction)

const OFFER_ACTIONS = {
  withdraw: {
    run: (id) => demandStore.withdrawOffer(id),
    messageKey: 'farmer.offers.withdrawn_toast',
  },
  cancel: {
    run: (id) => demandStore.cancelOffer(id, true),
    messageKey: 'farmer.offers.cancelled_toast',
  },
  'mark-delivered': {
    run: (id) => demandStore.markDelivered(id),
    messageKey: 'farmer.offers.delivered_toast',
  },
  'settle-balance': {
    run: (id) => demandStore.settleBalance(id),
    messageKey: 'farmer.offers.settled_toast',
  },
}
async function performConfirmedAction(pending) {
  if (pending.action === 'message') return openChat(pending.offer.demand?.buyer?.id)
  const action = OFFER_ACTIONS[pending.action]
  await runAction(pending.offer, () => action.run(pending.offer.id), action.messageKey)
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

async function runAction(offer, action, successMessageKey) {
  actingId.value = offer.id
  try {
    await action()
    notificationStore.addNotification({ type: 'success', messageKey: successMessageKey })
  } catch (err) {
    notificationStore.addNotification({
      type: 'error',
      message: err.response?.data?.message || t('farmer.offers.action_failed'),
    })
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
  askConfirm('message', offer)
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="font-serif text-3xl font-bold text-stone-900">{{ t('farmer.offers.title') }}</h1>
      <p class="text-base text-stone-600 font-normal mt-1">
        {{ t('farmer.offers.description') }}
      </p>
    </div>

    <div class="bg-white shadow-soft rounded-2xl border border-stone-200 overflow-hidden">
      <div class="border-b border-stone-200">
        <nav
          class="-mb-px flex space-x-8 px-6 overflow-x-auto"
          :aria-label="t('farmer.offers.filter_aria')"
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
            {{ t(tab.labelKey) }}
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
                {{ offer.demand?.title || t('farmer.offers.crop_demand') }}
              </h2>
              <p class="text-sm text-stone-500 mt-0.5">
                {{
                  t('farmer.offers.buyer_line', {
                    crop: offer.demand?.crop_name,
                    buyer: offer.demand?.buyer?.name || '—',
                  })
                }}
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
                isAddressExpanded(offer)
                  ? t('farmer.offers.hide_address')
                  : t('farmer.offers.show_address')
              "
              class="inline-flex items-center gap-1.5 text-sm font-medium text-moss-700 hover:text-moss-800 transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500 rounded-xl"
            >
              <ChevronDownIcon
                class="h-4 w-4 transition-transform duration-300"
                :class="{ 'rotate-180': isAddressExpanded(offer) }"
                aria-hidden="true"
              />
              {{
                isAddressExpanded(offer)
                  ? t('farmer.offers.hide_address')
                  : t('farmer.offers.show_address')
              }}
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
      :title="confirmConfig?.title || ''"
      :message="confirmConfig?.message || ''"
      :confirm-text="confirmConfig?.confirmText || ''"
      :type="confirmConfig?.type || 'primary'"
      @confirm="confirmPending"
      :loading="isExecuting"
      @cancel="cancel"
    />
  </div>
</template>
