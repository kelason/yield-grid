<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import PriceTag from '../atoms/PriceTag.vue'
import AppAlert from '../atoms/AppAlert.vue'
import AppButton from '../atoms/AppButton.vue'
import AppInput from '../atoms/AppInput.vue'
import {
  CalendarIcon,
  MapPinIcon,
  UserIcon,
  CheckCircleIcon,
  InformationCircleIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { PAYMENT_CONSTANTS, PAYMENT_OPTION } from '@/constants/payment'

const props = defineProps({
  contract: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['confirm', 'cancel'])

const authStore = useAuthStore()
const { t } = useI18n()

const pricePerKg = computed(() => {
  return props.contract.price_per_kg || props.contract.total_price / props.contract.quantity_kg
})

const QUANTITY_MAX_LENGTH = 8
const CHECKOUT_QTY_MIN_KG = 1
const CHECKOUT_QTY_MAX_KG = 99999
const CHECKOUT_QTY_SLIDER_STEP_KG = 5
const CHECKOUT_QTY_SLIDER_MIN_KG = 0

const maxOrderQty = computed(() =>
  Math.min(Number(props.contract.quantity_kg) || CHECKOUT_QTY_MAX_KG, CHECKOUT_QTY_MAX_KG),
)

// Start clamped to the slider max so listings above the per-order cap
// open confirmable instead of snapping down on first slider touch.
const quantityKg = ref(maxOrderQty.value)
const paymentOption = ref(PAYMENT_OPTION.PAYMONGO)

// Reset if contract changes
watch(
  () => props.contract.id,
  () => {
    quantityKg.value = maxOrderQty.value
    paymentOption.value = PAYMENT_OPTION.PAYMONGO
  },
)

const qtyError = ref('')

const totalPriceForQuantity = computed(() => quantityKg.value * pricePerKg.value)

const isDownpayment = computed(() => {
  if (props.contract.type === 'contract') {
    return new Date(props.contract.estimated_harvest_date) > new Date()
  }
  return !props.contract.is_harvest_available
})

const amountToPay = computed(() => {
  return isDownpayment.value
    ? totalPriceForQuantity.value * PAYMENT_CONSTANTS.DOWNPAYMENT_PERCENTAGE
    : totalPriceForQuantity.value
})

const handleConfirm = () => {
  if (props.loading) return
  qtyError.value = ''
  const qty = parseFloat(quantityKg.value)
  if (!qty || qty < CHECKOUT_QTY_MIN_KG) {
    qtyError.value = t('market.checkout.err_qty_zero')
    return
  }
  if (qty > maxOrderQty.value) {
    qtyError.value = t('market.checkout.err_qty_max', {
      max: maxOrderQty.value.toLocaleString(),
    })
    return
  }
  emit('confirm', {
    contractId: props.contract.id,
    type: props.contract.type,
    quantityKg: qty,
    paymentOption: paymentOption.value,
  })
}
</script>

<template>
  <div class="w-full max-w-2xl mx-auto">
    <div class="mb-8">
      <h2 class="font-serif text-2xl font-bold text-stone-900 mb-2">
        {{ t('market.checkout.review_title') }}
      </h2>
      <AppAlert v-if="qtyError" type="error" class="mt-3">{{ qtyError }}</AppAlert>
      <p class="text-stone-500 text-sm">
        {{ t('market.checkout.review_desc') }}
      </p>
    </div>

    <div class="bg-stone-50 rounded-xl p-6 mb-8 border border-stone-200 shadow-soft">
      <h3 class="font-serif text-lg font-bold text-stone-900 mb-4">{{ contract.title }}</h3>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6 text-sm">
        <div class="flex items-start">
          <CheckCircleIcon class="h-5 w-5 mr-2 text-moss-500 flex-shrink-0" />
          <div>
            <span class="block font-medium text-stone-900">{{ t('market.checkout.crop') }}</span>
            <span class="text-stone-600"
              >{{ contract.crop_name }} ({{
                t('market.checkout.available', { qty: contract.quantity_kg })
              }})</span
            >
          </div>
        </div>

        <div class="flex items-start">
          <CalendarIcon class="h-5 w-5 mr-2 text-moss-500 flex-shrink-0" />
          <div>
            <span class="block font-medium text-stone-900">{{ t('market.checkout.harvest') }}</span>
            <span class="text-stone-600">{{ contract.estimated_harvest_date }}</span>
          </div>
        </div>

        <div class="flex items-start">
          <UserIcon class="h-5 w-5 mr-2 text-moss-500 flex-shrink-0" />
          <div>
            <span class="block font-medium text-stone-900">{{ t('market.checkout.farmer') }}</span>
            <span class="text-stone-600">{{ contract.farmer?.name }}</span>
          </div>
        </div>

        <div class="flex items-start">
          <MapPinIcon class="h-5 w-5 mr-2 text-moss-500 flex-shrink-0" />
          <div>
            <span class="block font-medium text-stone-900">{{
              t('market.checkout.location')
            }}</span>
            <span class="text-stone-600">{{
              contract.farmer?.location || contract.farmer?.farm_name
            }}</span>
          </div>
        </div>
      </div>

      <div v-if="contract.description" class="mt-4 pt-4 border-t border-stone-200">
        <h4 class="text-xs font-medium text-stone-500 uppercase tracking-wider mb-1">
          {{ t('market.checkout.description') }}
        </h4>
        <p class="text-stone-700 text-sm">{{ contract.description }}</p>
      </div>
    </div>

    <div class="mb-8 p-6 bg-white rounded-xl border border-stone-200 shadow-soft space-y-6">
      <div>
        <label for="quantity" class="block text-sm font-bold text-soil-700 mb-2">{{
          t('market.checkout.qty_label')
        }}</label>
        <div class="flex items-center gap-4">
          <input
            type="range"
            id="quantity-slider"
            :aria-label="t('market.checkout.qty_slider_aria')"
            v-model.number="quantityKg"
            :min="CHECKOUT_QTY_SLIDER_MIN_KG"
            :max="maxOrderQty"
            :step="CHECKOUT_QTY_SLIDER_STEP_KG"
            class="flex-1 h-2 bg-stone-200 rounded-xl appearance-none cursor-pointer accent-moss-600"
          />
          <div class="relative w-24">
            <AppInput
              type="number"
              id="quantity"
              v-model="quantityKg"
              :min="CHECKOUT_QTY_MIN_KG"
              :max="maxOrderQty"
              :maxlength="QUANTITY_MAX_LENGTH"
              class="block w-full rounded-xl border-stone-300 shadow-soft focus:border-moss-500 focus:ring-moss-500 sm:text-sm pr-8"
            />
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
              <span class="text-stone-500 sm:text-sm">kg</span>
            </div>
          </div>
        </div>
      </div>

      <fieldset class="border-t border-stone-100 pt-6">
        <legend class="text-sm font-bold text-soil-700 mb-3">
          {{ t('market.checkout.pay_method') }}
        </legend>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <label
            class="relative flex cursor-pointer rounded-2xl border bg-white p-4 shadow-soft focus:outline-none"
            :class="
              paymentOption === PAYMENT_OPTION.PAYMONGO
                ? 'border-moss-500 ring-1 ring-moss-500'
                : 'border-stone-300'
            "
          >
            <input
              type="radio"
              v-model="paymentOption"
              :value="PAYMENT_OPTION.PAYMONGO"
              name="payment-method"
              class="h-4 w-4 accent-moss-600 mr-3"
            />
            <div class="flex w-full items-center justify-between">
              <div class="flex items-center">
                <div class="text-sm">
                  <p class="font-medium text-stone-900">{{ t('market.checkout.pay_online') }}</p>
                  <p class="text-stone-500">{{ t('market.checkout.pay_online_desc') }}</p>
                </div>
              </div>
              <CheckCircleIcon
                v-if="paymentOption === PAYMENT_OPTION.PAYMONGO"
                class="h-5 w-5 text-moss-600"
              />
            </div>
          </label>

          <label
            class="relative flex cursor-pointer rounded-2xl border bg-white p-4 shadow-soft focus:outline-none"
            :class="
              paymentOption === PAYMENT_OPTION.CASH
                ? 'border-moss-500 ring-1 ring-moss-500'
                : 'border-stone-300'
            "
          >
            <input
              type="radio"
              v-model="paymentOption"
              :value="PAYMENT_OPTION.CASH"
              name="payment-method"
              class="h-4 w-4 accent-moss-600 mr-3"
            />
            <div class="flex w-full items-center justify-between">
              <div class="flex items-center">
                <div class="text-sm">
                  <p class="font-medium text-stone-900">{{ t('market.checkout.pay_cash') }}</p>
                  <p class="text-stone-500">{{ t('market.checkout.pay_cash_desc') }}</p>
                </div>
              </div>
              <CheckCircleIcon
                v-if="paymentOption === PAYMENT_OPTION.CASH"
                class="h-5 w-5 text-moss-600"
              />
            </div>
          </label>
        </div>
      </fieldset>
    </div>

    <div class="flex flex-col sm:flex-row justify-between gap-4 mb-8">
      <div class="mb-4 sm:mb-0 w-full sm:w-auto">
        <h4 class="text-sm font-medium text-stone-500 mb-2">
          {{ t('market.checkout.total_value') }}
        </h4>
        <PriceTag
          :amount="totalPriceForQuantity"
          :currency="contract.currency"
          size="md"
          class="text-stone-600"
        />
      </div>

      <div class="text-right w-full sm:w-auto">
        <div class="text-sm font-bold text-stone-900 mb-1">
          {{
            isDownpayment
              ? t('market.checkout.downpayment', {
                  pct: PAYMENT_CONSTANTS.DOWNPAYMENT_PERCENTAGE * 100,
                })
              : t('market.checkout.total_pay')
          }}
        </div>
        <PriceTag
          :amount="amountToPay"
          :currency="contract.currency"
          size="lg"
          class="text-moss-700"
        />
      </div>
    </div>

    <div
      v-if="isDownpayment"
      class="bg-dew-50 text-dew-800 p-4 rounded-xl flex items-start mb-8 text-sm border border-dew-100"
    >
      <InformationCircleIcon class="h-5 w-5 mr-3 flex-shrink-0 text-dew-500 mt-0.5" />
      <p>
        {{
          t('market.checkout.downpayment_note', {
            pct: PAYMENT_CONSTANTS.DOWNPAYMENT_PERCENTAGE * 100,
          })
        }}
      </p>
    </div>

    <div class="flex flex-col-reverse sm:flex-row justify-end gap-3">
      <AppButton variant="ghost" :disabled="loading" @click="$emit('cancel')">{{
        t('shell.cancel')
      }}</AppButton>
      <AppButton :loading="loading" :disabled="!authStore.isEmailVerified" @click="handleConfirm">
        {{
          loading
            ? t('market.checkout.preparing')
            : !authStore.isEmailVerified
              ? t('market.checkout.verify_email')
              : t('market.checkout.proceed')
        }}
      </AppButton>
    </div>
  </div>
</template>
