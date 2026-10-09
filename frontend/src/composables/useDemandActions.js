import { computed, ref } from 'vue'
import i18n from '@/i18n'
import { useDemandStore } from '@/stores/demandStore'
import { useNotificationStore } from '@/stores/notificationStore'
import { usePayment } from '@/composables/usePayment'
import { useChatEntry } from '@/composables/useChatEntry'
import { usePendingConfirmation } from '@/composables/useConfirmModal'
import { PAYMENT_OPTION } from '@/constants/payment'

export const DEMAND_TABS = [
  { id: 'all', name: 'All Demands', labelKey: 'buyer.demands.tab_all', status: null },
  { id: 'open', name: 'Open', labelKey: 'buyer.demands.tab_open', status: 'open' },
  {
    id: 'fully_allocated',
    name: 'Fully Allocated',
    labelKey: 'buyer.demands.tab_fully_allocated',
    status: 'fully_allocated',
  },
  {
    id: 'fulfilled',
    name: 'Fulfilled',
    labelKey: 'buyer.demands.tab_fulfilled',
    status: 'fulfilled',
  },
  {
    id: 'cancelled',
    name: 'Cancelled',
    labelKey: 'buyer.demands.tab_cancelled',
    status: 'cancelled',
  },
  { id: 'expired', name: 'Expired', labelKey: 'buyer.demands.tab_expired', status: 'expired' },
]
const CONFIRMATION_COPY = {
  'accept-offer': ({ offerSummary, t }) => ({
    title: t('buyer.demands.accept_title'),
    message: t('buyer.demands.accept_msg', { summary: offerSummary }),
    confirmText: t('buyer.demands.accept_confirm'),
    type: 'primary',
  }),
  'reject-offer': ({ offerSummary, t }) => ({
    title: t('buyer.demands.reject_title'),
    message: t('buyer.demands.reject_msg', { summary: offerSummary }),
    confirmText: t('buyer.demands.reject_confirm'),
    type: 'danger',
  }),
  'cancel-offer': ({ offerSummary, t }) => ({
    title: t('buyer.demands.cancel_offer_title'),
    message: t('buyer.demands.cancel_offer_msg', { summary: offerSummary }),
    confirmText: t('buyer.demands.cancel_offer_confirm'),
    type: 'danger',
  }),
  'confirm-completed': ({ offerSummary, t }) => ({
    title: t('buyer.demands.delivered_title'),
    message: t('buyer.demands.delivered_msg', { summary: offerSummary }),
    confirmText: t('buyer.demands.delivered_confirm'),
    type: 'primary',
  }),
  'cancel-demand': ({ demand, t }) => ({
    title: t('buyer.demands.cancel_demand_title'),
    message: t('buyer.demands.cancel_demand_msg', { title: demand?.title || '' }),
    confirmText: t('buyer.demands.cancel_demand_confirm'),
    type: 'danger',
  }),
  pay: ({ offer, paymentOption, offerSummary, t }) => {
    const isCash = paymentOption === PAYMENT_OPTION.CASH
    return {
      title: t(isCash ? 'buyer.demands.pay_cash_title' : 'buyer.demands.pay_online_title'),
      message: isCash
        ? t('buyer.demands.pay_cash_msg', { summary: offerSummary })
        : t('buyer.demands.pay_online_msg', {
            amount: Number(offer?.total_price || 0).toLocaleString('en-PH'),
            summary: offerSummary,
          }),
      confirmText: t(
        isCash ? 'buyer.demands.pay_cash_confirm' : 'buyer.demands.pay_online_confirm',
      ),
      type: 'primary',
    }
  },
  message: ({ t }) => ({
    title: t('farmer.offers.msg_title'),
    message: t('buyer.demands.msg_message'),
    confirmText: t('buyer.demands.msg_confirm'),
    type: 'primary',
  }),
}
function confirmationDetails(pending, t) {
  if (!pending) return null
  const offerSummary =
    pending.offer != null
      ? t('buyer.demands.offer_summary', {
          qty: pending.offer.quantity_kg,
          price: pending.offer.price_per_kg,
        })
      : ''
  return CONFIRMATION_COPY[pending.action]?.({ ...pending, offerSummary, t }) || null
}

function createState() {
  const currentTab = ref('all')
  const pendingConfirm = ref(null)
  const t = (...args) => i18n.global.t(...args)
  return {
    demandStore: useDemandStore(),
    notificationStore: useNotificationStore(),
    ...usePayment(),
    ...useChatEntry(),
    t,
    currentTab,
    pendingConfirm,
    expandedId: ref(null),
    showPayModal: ref(false),
    payingOffer: ref(null),
    actingId: ref(null),
    payError: ref(''),
    activeTab: computed(() => DEMAND_TABS.find((tab) => tab.id === currentTab.value)),
    confirmConfig: computed(() => confirmationDetails(pendingConfirm.value, t)),
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
    s.notificationStore.addNotification({
      type: 'error',
      messageKey: 'buyer.demands.load_offers_error',
    })
  }
}
async function runAction(s, offer, action, successMessageKey) {
  s.actingId.value = offer.id
  try {
    const updated = await action()
    s.notificationStore.addNotification({ type: 'success', messageKey: successMessageKey })
    if (['accepted', 'rejected', 'cancelled'].includes(updated.status)) refreshDemands(s)
  } catch (err) {
    if (err.response?.data?.message) {
      s.notificationStore.error(err.response.data.message)
    } else {
      s.notificationStore.addNotification({
        type: 'error',
        messageKey: 'buyer.demands.action_failed',
      })
    }
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
      s.notificationStore.addNotification({
        type: 'success',
        messageKey: 'buyer.demands.cash_sent',
      })
      s.showPayModal.value = false
      refreshDemands(s)
      if (s.expandedId.value) s.demandStore.fetchDemandOffers(s.expandedId.value)
    }
  } catch (err) {
    s.payError.value = err.response?.data?.message || err.message || s.t('buyer.demands.pay_failed')
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
  'accept-offer': 'buyer.demands.toast_accepted',
  'reject-offer': 'buyer.demands.toast_rejected',
  'cancel-offer': 'buyer.demands.toast_cancelled',
  'confirm-completed': 'buyer.demands.toast_delivered',
  'cancel-demand': 'buyer.demands.toast_demand_cancelled',
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
        ? s.t('buyer.demands.empty_all')
        : s.t(`buyer.demands.empty_${s.currentTab.value}`),
    ),
    emptyDescription: computed(() =>
      s.currentTab.value === 'all'
        ? s.t('buyer.demands.empty_all_desc')
        : s.t('buyer.demands.empty_tab_desc'),
    ),
    refreshDemands: () => refreshDemands(s),
    handleTabChange: (tabId) => handleTabChange(s, tabId),
    isActing: (offer) => s.actingId.value === offer.id,
    toggleOffers: (demand) => toggleOffers(s, demand),
    offersFor: (demand) => (s.expandedId.value === demand.id ? s.demandStore.demandOffers : []),
    confirmPending: () => execute((pending) => performPending(s, pending)),
  }
}
