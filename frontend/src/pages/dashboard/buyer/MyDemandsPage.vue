<script setup>
import { onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDemandActions, DEMAND_TABS } from '@/composables/useDemandActions'
import OfferPaymentPanel from '@/components/organisms/OfferPaymentPanel.vue'
import { RouterLink } from 'vue-router'
import { useDemandStore } from '@/stores/demandStore'
import { usePriceGuide } from '@/composables/usePriceGuide'
import OfferCard from '@/components/molecules/OfferCard.vue'
import DeliveryAddressCard from '@/components/molecules/DeliveryAddressCard.vue'
import EmptyState from '@/components/molecules/EmptyState.vue'
import AppModal from '@/components/molecules/AppModal.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import SkeletonCard from '@/components/atoms/SkeletonCard.vue'
import StatusBadge from '@/components/atoms/StatusBadge.vue'
import PriceFairnessBadge from '@/components/atoms/PriceFairnessBadge.vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const demandStore = useDemandStore()
const { prefetchCrops } = usePriceGuide()
const { t } = useI18n()

watch(
  () => demandStore.myDemands,
  (demands) => {
    prefetchCrops((demands ?? []).map((demand) => demand.crop_name))
  },
  { immediate: true },
)
const {
  expandedId,
  showPayModal,
  payingOffer,
  actingId,
  payError,
  currentTab,
  pendingConfirm,
  confirmConfig,
  emptyTitle,
  emptyDescription,
  isExecuting,
  cancel,
  handleTabChange,
  isActing,
  toggleOffers,
  handleAccept,
  handleReject,
  handleCancelOffer,
  handleConfirmCompleted,
  handleCancelDemand,
  requestPay,
  confirmPending,
  openPayModal,
  handleMessage,
  offersFor,
} = useDemandActions()
onMounted(() => demandStore.fetchMyDemands())
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="font-serif text-3xl font-bold text-stone-900">
          {{ t('buyer.demands.title') }}
        </h1>
        <p class="text-base text-stone-600 font-normal mt-1">
          {{ t('buyer.demands.description') }}
        </p>
      </div>
      <RouterLink :to="{ name: 'buyer-post-demand' }">
        <AppButton variant="primary">{{ t('buyer.demands.post') }}</AppButton>
      </RouterLink>
    </div>

    <div class="bg-white shadow-soft rounded-2xl border border-stone-200 overflow-hidden">
      <div class="border-b border-stone-200">
        <nav
          class="-mb-px flex space-x-8 px-6 overflow-x-auto"
          :aria-label="t('buyer.demands.filter_aria')"
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
            {{ t(tab.labelKey) }}
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
                {{
                  t('buyer.demands.summary', {
                    crop: demand.crop_name,
                    remaining: demand.remaining_quantity_kg,
                    total: demand.quantity_kg,
                    pending: demand.pending_offers_count ?? 0,
                  })
                }}
              </p>
              <PriceFairnessBadge
                :listing-price="Number(demand.target_price_per_kg)"
                :crop-name="demand.crop_name"
                class="mt-1.5"
              />
            </div>
            <div class="flex flex-col items-end gap-2 flex-shrink-0">
              <div class="flex items-center gap-2">
                <StatusBadge :status="demand.status" size="sm" />
                <button
                  type="button"
                  @click="toggleOffers(demand)"
                  :aria-expanded="expandedId === demand.id"
                  :aria-label="
                    expandedId === demand.id
                      ? t('buyer.demands.hide_offers')
                      : t('buyer.demands.show_offers')
                  "
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
                {{ t('buyer.demands.cancel_demand') }}
              </AppButton>
            </div>
          </div>

          <div v-if="demand.delivery_address" class="mt-4">
            <DeliveryAddressCard
              :address="demand.delivery_address"
              :title="t('buyer.demands.delivery_title')"
            />
          </div>

          <div v-if="expandedId === demand.id" class="mt-4 pt-4 border-t border-stone-200">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
              <h3 class="font-serif text-lg font-bold text-stone-900 flex items-center gap-2">
                {{ t('buyer.demands.competing') }}
                <span
                  v-if="!demandStore.loading.offers"
                  class="rounded-full bg-moss-100 text-moss-800 text-xs font-semibold px-2.5 py-0.5"
                >
                  {{ offersFor(demand).length }}
                </span>
              </h3>
              <p class="text-xs text-stone-500">
                {{ t('buyer.demands.compare_hint') }}
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
              :title="t('buyer.demands.no_offers')"
              :description="t('buyer.demands.no_offers_desc')"
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

    <AppModal
      :title="t('buyer.demands.pay_title')"
      :is-open="showPayModal"
      :busy="isExecuting"
      @close="showPayModal = false"
    >
      <OfferPaymentPanel
        :offer="payingOffer"
        :error="payError"
        :loading="isExecuting"
        @pay="requestPay"
        @cancel="showPayModal = false"
      />
    </AppModal>

    <ConfirmModal
      v-if="confirmConfig"
      :is-open="pendingConfirm !== null"
      :title="confirmConfig.title"
      :message="confirmConfig.message"
      :confirm-text="confirmConfig.confirmText"
      :type="confirmConfig.type"
      @confirm="confirmPending"
      :loading="isExecuting"
      @cancel="cancel"
    />
  </div>
</template>
