<script setup>
import AppTextarea from '@/components/atoms/AppTextarea.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import { usePendingConfirmation } from '@/composables/useConfirmModal'
import { useContentReport } from '@/composables/useContentReport'
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useDemandStore } from '@/stores/demandStore'
import { useAddressStore } from '@/stores/addressStore'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notificationStore'
import { usePriceGuide } from '@/composables/usePriceGuide'
import DemandFilter from '@/components/molecules/DemandFilter.vue'
import DemandCard from '@/components/molecules/DemandCard.vue'
import ContentReportForm from '@/components/organisms/ContentReportForm.vue'
import PriceGuideHint from '@/components/molecules/PriceGuideHint.vue'
import PaginationControls from '@/components/molecules/PaginationControls.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import AppModal from '@/components/molecules/AppModal.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import FormField from '@/components/molecules/FormField.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppAlert from '@/components/atoms/AppAlert.vue'
import SkeletonCard from '@/components/atoms/SkeletonCard.vue'
import { GEO_CONSTANTS } from '@/constants/geo'

const OFFER_QTY_MIN_KG = 0.01
const OFFER_PRICE_MIN = 0.01
const OFFER_PRICE_MAX_DIGITS = 8
const OFFER_PRICE_MAX = 99999999
const OFFER_MESSAGE_MAX_LENGTH = 1000
const OFFER_TOTAL_MAX = 9999999999.99

const demandStore = useDemandStore()
const { prefetchCrops } = usePriceGuide()

watch(
  () => demandStore.demands,
  (demands) => {
    prefetchCrops((demands ?? []).map((demand) => demand.crop_name))
  },
  { immediate: true },
)
const addressStore = useAddressStore()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const router = useRouter()

const showOfferModal = ref(false)
const offerForm = ref({ quantity_kg: '', price_per_kg: '', message: '' })
const offerError = ref('')
const submitting = ref(false)
const pendingConfirm = ref(null)
const { isExecuting, execute, cancel } = usePendingConfirmation(pendingConfirm)
const showReportModal = ref(false)
const {
  target: reportTarget,
  reason: reportReason,
  description: reportDescription,
  busy: reportBusy,
  error: reportError,
  fieldError: reportFieldError,
  openReport,
  closeReport,
  validate: validateReport,
  submit: submitContentReport,
} = useContentReport()
const reportAccess = computed(() => {
  if (!authStore.isAuthenticated) return 'signin'
  if (!authStore.isEmailVerified) return 'verify'
  return 'ok'
})

const confirmConfig = computed(() => {
  if (!pendingConfirm.value) return null
  if (pendingConfirm.value.kind === 'report') {
    return {
      title: 'Submit this report?',
      message: 'Your report will be sent to the moderation team.',
      confirmText: 'Send report',
      type: 'primary',
    }
  }
  const { qty, price } = pendingConfirm.value
  return {
    title: 'Send this offer?',
    message: `Offer ${qty} kg at ₱${price}/kg (₱${(qty * price).toLocaleString('en-PH')} total)? The buyer will review it and can accept or reject.`,
    confirmText: 'Send offer',
    type: 'primary',
  }
})

onMounted(async () => {
  if (authStore.isAuthenticated) {
    try {
      await addressStore.fetchAddresses()
      const fallback = addressStore.defaultAddress
      if (fallback?.latitude != null && fallback?.longitude != null) {
        demandStore.viewerLocation = {
          lat: parseFloat(fallback.latitude),
          lng: parseFloat(fallback.longitude),
        }
      }
    } catch {
      // Browsing still works without a saved address.
    }
  }
  demandStore.fetchDemands()
})

function handleSearch() {
  demandStore.fetchDemands(1)
}

function handlePageChange(page) {
  demandStore.fetchDemands(page)
}

async function openOfferModal(demand) {
  await demandStore.fetchDemandDetail(demand.id)
  offerForm.value = {
    quantity_kg: demand.remaining_quantity_kg,
    price_per_kg: demand.target_price_per_kg,
    message: '',
  }
  offerError.value = ''
  showOfferModal.value = true
}

function validateOfferForm() {
  const remaining = parseFloat(demandStore.activeDemand?.remaining_quantity_kg ?? 0)
  const qty = parseFloat(offerForm.value.quantity_kg)
  const price = parseFloat(offerForm.value.price_per_kg)
  const message = (offerForm.value.message || '').trim()
  if (!qty || qty < OFFER_QTY_MIN_KG) {
    return 'Please enter a quantity greater than zero.'
  }
  if (qty > remaining) {
    return `Quantity cannot exceed the ${remaining} kg still needed.`
  }
  if (!price || price < OFFER_PRICE_MIN) {
    return 'Please enter a price greater than zero.'
  }
  if (price > OFFER_PRICE_MAX) {
    return `Price cannot exceed ${OFFER_PRICE_MAX_DIGITS} digits (₱${OFFER_PRICE_MAX.toLocaleString()}).`
  }
  if (message.length > OFFER_MESSAGE_MAX_LENGTH) {
    return `Message cannot exceed ${OFFER_MESSAGE_MAX_LENGTH} characters.`
  }
  if (qty * price > OFFER_TOTAL_MAX) {
    return 'The combined quantity and price exceed the maximum order total.'
  }
  return { qty, price, message }
}

function askOfferConfirm() {
  offerError.value = ''
  const validated = validateOfferForm()
  if (typeof validated === 'string') {
    offerError.value = validated
    return
  }
  pendingConfirm.value = { kind: 'offer', ...validated }
}

const confirmPendingOffer = () =>
  execute((pending) =>
    pending.kind === 'report' ? submitReport() : performConfirmedAction(pending),
  )

function openReportModal(payload) {
  openReport(payload)
  showReportModal.value = true
}

function closeReportModal() {
  if (reportBusy.value || isExecuting.value) return
  showReportModal.value = false
  closeReport()
}

function requestReportSubmit() {
  if (!reportTarget.value || reportBusy.value) return
  if (!validateReport()) return
  pendingConfirm.value = { kind: 'report' }
}

async function submitReport() {
  try {
    await submitContentReport()
    showReportModal.value = false
  } catch {
    // The inline form error and draft stay visible; the modal remains open.
  }
}

function goLogin() {
  closeReportModal()
  router.push({ name: 'login' })
}

async function performConfirmedAction(pending) {
  submitting.value = true
  try {
    await demandStore.submitOffer(demandStore.activeDemand.id, {
      quantity_kg: pending.qty,
      price_per_kg: pending.price,
      message: pending.message || null,
    })
    notificationStore.success('Offer sent! The buyer will review it soon.')
    showOfferModal.value = false
    demandStore.fetchDemands(demandStore.pagination.currentPage)
  } catch (err) {
    if (err.response?.data?.error_code === GEO_CONSTANTS.ERROR_ADDRESS_REQUIRED) {
      offerError.value = 'Please add an address in your profile before submitting offers.'
    } else {
      offerError.value = err.response?.data?.message || 'Failed to submit offer.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      title="Buyer Demands"
      description="Browse what buyers are looking for and send your best offer."
    />

    <DemandFilter v-model="demandStore.filters" @search="handleSearch" />

    <div v-if="demandStore.loading.demands" class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <SkeletonCard v-for="n in 4" :key="n" />
    </div>

    <EmptyState
      v-else-if="demandStore.demands.length === 0"
      title="No demands right now"
      description="Check back later — new buyer requests appear here."
    />

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <DemandCard
        v-for="demand in demandStore.demands"
        :key="demand.id"
        :demand="demand"
        @view="openOfferModal"
        @report="openReportModal"
      />
    </div>

    <PaginationControls
      v-if="!demandStore.loading.demands && demandStore.demands.length > 0"
      :current-page="demandStore.pagination.currentPage"
      :last-page="demandStore.pagination.lastPage"
      :total="demandStore.pagination.total"
      @page-change="handlePageChange"
      class="mt-6"
    />

    <AppModal title="Make an offer" :is-open="showOfferModal" @close="showOfferModal = false">
      <div v-if="demandStore.activeDemand" class="space-y-5">
        <div>
          <h2 class="font-serif text-2xl font-bold text-stone-900">
            {{ demandStore.activeDemand.title }}
          </h2>
          <p class="text-stone-600 text-sm mt-1">
            {{ demandStore.activeDemand.crop_name }} ·
            {{ demandStore.activeDemand.remaining_quantity_kg }} kg still needed · target ₱{{
              demandStore.activeDemand.target_price_per_kg
            }}/kg
          </p>
          <p
            v-if="demandStore.activeDemand.description"
            class="text-stone-600 text-sm mt-2 leading-relaxed"
          >
            {{ demandStore.activeDemand.description }}
          </p>
        </div>

        <AppAlert v-if="offerError" type="error">{{ offerError }}</AppAlert>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <FormField
            id="offer-qty"
            :label="`Your quantity in kg (max ${demandStore.activeDemand.remaining_quantity_kg})`"
            type="number"
            v-model="offerForm.quantity_kg"
            :required="true"
            :min="OFFER_QTY_MIN_KG"
            :max="demandStore.activeDemand.remaining_quantity_kg"
          />
          <FormField
            id="offer-price"
            label="Your price per kg (₱, max 8 digits)"
            type="number"
            v-model="offerForm.price_per_kg"
            :required="true"
            :min="OFFER_PRICE_MIN"
            :maxlength="OFFER_PRICE_MAX_DIGITS"
          />
        </div>

        <PriceGuideHint
          :crop-name="demandStore.activeDemand.crop_name"
          :current-price="Number(offerForm.price_per_kg) || null"
        />
        <div>
          <label for="offer-message" class="block text-sm font-medium text-soil-700 mb-1">
            Message to buyer (optional)
          </label>
          <AppTextarea
            minlength="0"
            id="offer-message"
            aria-describedby="offer-message-counter"
            v-model="offerForm.message"
            rows="3"
            :maxlength="OFFER_MESSAGE_MAX_LENGTH"
            placeholder="e.g. Fresh harvest, can deliver this week"
          />
          <p class="text-xs text-stone-500 mt-1 text-right" id="offer-message-counter">
            {{ (offerForm.message || '').length }} / {{ OFFER_MESSAGE_MAX_LENGTH }}
          </p>
        </div>

        <div class="flex justify-end gap-3">
          <AppButton variant="ghost" @click="showOfferModal = false">Cancel</AppButton>
          <AppButton variant="primary" :loading="submitting" @click="askOfferConfirm">
            Send offer
          </AppButton>
        </div>
      </div>
    </AppModal>

    <AppModal
      title="Report content"
      :is-open="showReportModal"
      :busy="reportBusy || isExecuting"
      @close="closeReportModal"
    >
      <ContentReportForm
        v-if="reportAccess === 'ok' && reportTarget"
        :target="reportTarget"
        :reason="reportReason"
        :description="reportDescription"
        :busy="reportBusy || isExecuting"
        :error="reportError"
        :field-error="reportFieldError"
        @update:reason="reportReason = $event"
        @update:description="reportDescription = $event"
        @submit="requestReportSubmit"
        @cancel="closeReportModal"
      />
      <div v-else-if="reportAccess === 'signin'" class="space-y-4">
        <p class="text-base leading-relaxed text-stone-600">
          Sign in to report this content to the moderation team.
        </p>
        <div class="flex justify-end gap-3">
          <AppButton variant="secondary" @click="closeReportModal">Cancel</AppButton>
          <AppButton variant="primary" @click="goLogin">Sign in</AppButton>
        </div>
      </div>
      <div v-else class="space-y-4">
        <p class="text-base leading-relaxed text-stone-600">
          Verify your email address to report content to the moderation team.
        </p>
        <div class="flex justify-end">
          <AppButton variant="secondary" @click="closeReportModal">Close</AppButton>
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
      @confirm="confirmPendingOffer"
      :loading="isExecuting"
      @cancel="cancel"
    />
  </div>
</template>
