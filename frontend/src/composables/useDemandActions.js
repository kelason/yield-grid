import { computed, ref } from 'vue'
import { useDemandStore } from '@/stores/demandStore'
import { useNotificationStore } from '@/stores/notificationStore'
import { usePayment } from '@/composables/usePayment'
import { useChatEntry } from '@/composables/useChatEntry'
import { usePendingConfirmation } from '@/composables/useConfirmModal'
import { PAYMENT_OPTION } from '@/constants/payment'

export const DEMAND_TABS = [
  { id: 'all', name: 'All Demands', status: null },
  { id: 'open', name: 'Open', status: 'open' },
  { id: 'fully_allocated', name: 'Fully Allocated', status: 'fully_allocated' },
  { id: 'fulfilled', name: 'Fulfilled', status: 'fulfilled' },
  { id: 'cancelled', name: 'Cancelled', status: 'cancelled' },
  { id: 'expired', name: 'Expired', status: 'expired' },
]
const CONFIRMATION_COPY = {
  'accept-offer': ({ offerSummary }) => ({
    title: 'Accept this offer?',
    message: `Accept the offer of ${offerSummary}? The farmer will be notified to proceed.`,
    confirmText: 'Accept offer',
    type: 'primary',
  }),
  'reject-offer': ({ offerSummary }) => ({
    title: 'Reject this offer?',
    message: `Reject the offer of ${offerSummary}? This cannot be undone.`,
    confirmText: 'Reject offer',
    type: 'danger',
  }),
  'cancel-offer': ({ offerSummary }) => ({
    title: 'Cancel this offer?',
    message: `Cancel the offer of ${offerSummary}? This cannot be undone.`,
    confirmText: 'Cancel offer',
    type: 'danger',
  }),
  'confirm-completed': ({ offerSummary }) => ({
    title: 'Confirm delivery?',
    message: `Confirm you received the full ${offerSummary} delivery? This releases the order as complete.`,
    confirmText: 'Confirm delivery',
    type: 'primary',
  }),
  'cancel-demand': ({ demand }) => ({
    title: 'Cancel this demand?',
    message: `Cancel your demand "${demand?.title || ''}"? All pending offers on it will be closed. This cannot be undone.`,
    confirmText: 'Cancel demand',
    type: 'danger',
  }),
  pay: ({ offer, paymentOption, offerSummary }) => ({
    title: paymentOption === PAYMENT_OPTION.CASH ? 'Request cash payment?' : 'Pay online now?',
    message:
      paymentOption === PAYMENT_OPTION.CASH
        ? `Request to pay ${offerSummary} in cash? The farmer must approve before delivery.`
        : `Pay ₱${Number(offer?.total_price || 0).toLocaleString('en-PH')} online now for ${offerSummary}?`,
    confirmText: paymentOption === PAYMENT_OPTION.CASH ? 'Request cash payment' : 'Pay now',
    type: 'primary',
  }),
  message: () => ({
    title: 'Open conversation?',
    message: 'Open a conversation with this farmer?',
    confirmText: 'Open conversation',
    type: 'primary',
  }),
}
function confirmationDetails(pending) {
  if (!pending) return null
  const offerSummary =
    pending.offer != null
      ? `${pending.offer.quantity_kg} kg at ₱${pending.offer.price_per_kg}/kg`
      : ''
  return CONFIRMATION_COPY[pending.action]?.({ ...pending, offerSummary }) || null
}

function createState() {
  const currentTab = ref('all')
  const pendingConfirm = ref(null)
  return {
    demandStore: useDemandStore(),
    notificationStore: useNotificationStore(),
    ...usePayment(),
    ...useChatEntry(),
    currentTab,
    pendingConfirm,
    expandedId: ref(null),
    showPayModal: ref(false),
    payingOffer: ref(null),
    actingId: ref(null),
    payError: ref(''),
    activeTab: computed(() => DEMAND_TABS.find((tab) => tab.id === currentTab.value)),
    confirmConfig: computed(() => confirmationDetails(pendingConfirm.value)),
  }
}
function refreshDemands(s) {
  return s.demandStore.fetchMyDemands(s.activeTab.value?.status || null)
}
function handleTabChange(s, tabId) {
  if (tabId === s.currentTab.value) return
  s.currentTab.value = tabId
  s.expandedId.value = null
  refreshDemands(s)
}
async function toggleOffers(s, demand) {
  if (s.expandedId.value === demand.id) {
    s.expandedId.value = null
    return
  }
  s.expandedId.value = demand.id
  try {
    await s.demandStore.fetchDemandOffers(demand.id)
  } catch {
    s.notificationStore.error('Failed to load offers.')
  }
}
async function runAction(s, offer, action, successMessage) {
  s.actingId.value = offer.id
  try {
    const updated = await action()
    s.notificationStore.success(successMessage)
    if (['accepted', 'rejected', 'cancelled'].includes(updated.status)) refreshDemands(s)
  } catch (err) {
    s.notificationStore.error(err.response?.data?.message || 'Action failed. Please try again.')
  } finally {
    s.actingId.value = null
  }
}
function openPayModal(s, offer) {
  s.payingOffer.value = offer
  s.payError.value = ''
  s.showPayModal.value = true
}
async function handlePay(s, pending) {
  s.payError.value = ''
  try {
    await s.startOfferCheckout(pending.offer.id, pending.paymentOption)
    if (pending.paymentOption === PAYMENT_OPTION.CASH) {
      s.notificationStore.success('Cash payment request sent! Waiting for farmer approval.')
      s.showPayModal.value = false
      refreshDemands(s)
      if (s.expandedId.value) s.demandStore.fetchDemandOffers(s.expandedId.value)
    }
  } catch (err) {
    s.payError.value = err.response?.data?.message || err.message || 'Payment failed.'
    s.showPayModal.value = true
  }
}
const OFFER_ACTIONS = {
  'accept-offer': (store, id) => store.decideOffer(id, 'accept'),
  'reject-offer': (store, id) => store.decideOffer(id, 'reject'),
  'cancel-offer': (store, id) => store.cancelOffer(id, false),
  'confirm-completed': (store, id) => store.confirmCompleted(id),
  'cancel-demand': (store, id) => store.cancelDemand(id),
}
const ACTION_MESSAGES = {
  'accept-offer': 'Offer accepted!',
  'reject-offer': 'Offer rejected.',
  'cancel-offer': 'Offer cancelled.',
  'confirm-completed': 'Delivery confirmed. Thank you!',
  'cancel-demand': 'Demand cancelled.',
}
async function performPending(s, pending) {
  if (pending.action === 'pay') return handlePay(s, pending)
  if (pending.action === 'message') return s.openChat(pending.offer?.farmer?.id)
  const id = pending.demand?.id || pending.offer?.id
  const target = pending.demand ? { id: `demand-${id}` } : pending.offer
  return runAction(
    s,
    target,
    () => OFFER_ACTIONS[pending.action](s.demandStore, id),
    ACTION_MESSAGES[pending.action],
  )
}
function actionHandlers(s) {
  const ask = (action, offer) => {
    s.pendingConfirm.value = { action, offer }
  }
  return {
    handleAccept: (offer) => ask('accept-offer', offer),
    handleReject: (offer) => ask('reject-offer', offer),
    handleCancelOffer: (offer) => ask('cancel-offer', offer),
    handleConfirmCompleted: (offer) => ask('confirm-completed', offer),
    handleMessage: (offer) => ask('message', offer),
    handleCancelDemand: (demand) => {
      s.pendingConfirm.value = { action: 'cancel-demand', demand }
    },
    openPayModal: (offer) => openPayModal(s, offer),
    requestPay: (paymentOption) => {
      s.pendingConfirm.value = { action: 'pay', offer: s.payingOffer.value, paymentOption }
    },
  }
}
export function useDemandActions() {
  const s = createState()
  const { isExecuting, execute, cancel } = usePendingConfirmation(s.pendingConfirm)
  return {
    ...s,
    ...actionHandlers(s),
    isExecuting,
    cancel,
    checkoutLoading: s.loading,
    emptyTitle: computed(() =>
      s.currentTab.value === 'all'
        ? 'No demands yet'
        : `No ${s.activeTab.value.name.toLowerCase()} demands`,
    ),
    emptyDescription: computed(() =>
      s.currentTab.value === 'all'
        ? 'Post your first crop demand and let farmers compete for it.'
        : 'Demands with this status will appear here.',
    ),
    refreshDemands: () => refreshDemands(s),
    handleTabChange: (tabId) => handleTabChange(s, tabId),
    isActing: (offer) => s.actingId.value === offer.id,
    toggleOffers: (demand) => toggleOffers(s, demand),
    offersFor: (demand) => (s.expandedId.value === demand.id ? s.demandStore.demandOffers : []),
    confirmPending: () => execute((pending) => performPending(s, pending)),
  }
}
