<script setup>
import { computed, ref, watch } from 'vue'
import PriceTag from '../atoms/PriceTag.vue'
import {
  CalendarIcon,
  MapPinIcon,
  UserIcon,
  CheckCircleIcon,
  InformationCircleIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { PAYMENT_CONSTANTS } from '@/constants/payment'

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

const pricePerKg = computed(() => {
  return props.contract.price_per_kg || props.contract.total_price / props.contract.quantity_kg
})

const quantityKg = ref(props.contract.quantity_kg)
const paymentOption = ref('paymongo')

// Reset if contract changes
watch(
  () => props.contract.id,
  () => {
    quantityKg.value = props.contract.quantity_kg
    paymentOption.value = 'paymongo'
  },
)

const QUANTITY_MAX_LENGTH = 6

// Mirror AppInput: browsers ignore maxlength on number inputs, so clamp here.
// Writing back to both the element and the ref keeps display and v-model in
// sync regardless of listener order.
const clampQuantity = (event) => {
  const value = event.target.value
  if (value.length > QUANTITY_MAX_LENGTH) {
    const sliced = value.slice(0, QUANTITY_MAX_LENGTH)
    event.target.value = sliced
    const parsed = parseFloat(sliced)
    quantityKg.value = Number.isNaN(parsed) ? sliced : parsed
  }
}

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
  emit('confirm', {
    contractId: props.contract.id,
    type: props.contract.type,
    quantityKg: quantityKg.value,
    paymentOption: paymentOption.value,
  })
}
</script>

<template>
  <div class="w-full max-w-2xl mx-auto">
    <div class="mb-8">
      <h2 class="font-serif text-2xl font-bold text-stone-900 mb-2">Review Your Purchase</h2>
      <p class="text-stone-500 text-sm">
        Please confirm the details of this forward contract before proceeding to payment.
      </p>
    </div>

    <div class="bg-stone-50 rounded-xl p-6 mb-8 border border-stone-200 shadow-soft">
      <h3 class="font-serif text-lg font-bold text-stone-900 mb-4">{{ contract.title }}</h3>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6 text-sm">
        <div class="flex items-start">
          <CheckCircleIcon class="h-5 w-5 mr-2 text-moss-500 flex-shrink-0" />
          <div>
            <span class="block font-medium text-stone-900">Crop</span>
            <span class="text-stone-600"
              >{{ contract.crop_name }} ({{ contract.quantity_kg }}kg available)</span
            >
          </div>
        </div>

        <div class="flex items-start">
          <CalendarIcon class="h-5 w-5 mr-2 text-moss-500 flex-shrink-0" />
          <div>
            <span class="block font-medium text-stone-900">Est. Harvest</span>
            <span class="text-stone-600">{{ contract.estimated_harvest_date }}</span>
          </div>
        </div>

        <div class="flex items-start">
          <UserIcon class="h-5 w-5 mr-2 text-moss-500 flex-shrink-0" />
          <div>
            <span class="block font-medium text-stone-900">Farmer</span>
            <span class="text-stone-600">{{ contract.farmer?.name }}</span>
          </div>
        </div>

        <div class="flex items-start">
          <MapPinIcon class="h-5 w-5 mr-2 text-moss-500 flex-shrink-0" />
          <div>
            <span class="block font-medium text-stone-900">Location</span>
            <span class="text-stone-600">{{
              contract.farmer?.location || contract.farmer?.farm_name
            }}</span>
          </div>
        </div>
      </div>

      <div v-if="contract.description" class="mt-4 pt-4 border-t border-stone-200">
        <h4 class="text-xs font-medium text-stone-500 uppercase tracking-wider mb-1">
          Description
        </h4>
        <p class="text-stone-700 text-sm">{{ contract.description }}</p>
      </div>
    </div>

    <div class="mb-8 p-6 bg-white rounded-xl border border-stone-200 shadow-soft space-y-6">
      <div>
        <label for="quantity" class="block text-sm font-bold text-soil-700 mb-2"
          >Purchase Quantity (kg)</label
        >
        <div class="flex items-center gap-4">
          <input
            type="range"
            id="quantity-slider"
            v-model.number="quantityKg"
            min="1"
            :max="contract.quantity_kg"
            class="flex-1 h-2 bg-stone-200 rounded-xl appearance-none cursor-pointer accent-moss-600"
          />
          <div class="relative w-24">
            <input
              type="number"
              id="quantity"
              v-model.number="quantityKg"
              min="1"
              :max="contract.quantity_kg"
              :maxlength="QUANTITY_MAX_LENGTH"
              @input="clampQuantity"
              class="block w-full rounded-xl border-stone-300 shadow-soft focus:border-moss-500 focus:ring-moss-500 sm:text-sm pr-8"
            />
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
              <span class="text-stone-500 sm:text-sm">kg</span>
            </div>
          </div>
        </div>
      </div>

      <div class="border-t border-stone-100 pt-6">
        <label class="block text-sm font-bold text-soil-700 mb-3">Payment Method</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <label
            class="relative flex cursor-pointer rounded-2xl border bg-white p-4 shadow-soft focus:outline-none"
            :class="
              paymentOption === 'paymongo'
                ? 'border-moss-500 ring-1 ring-moss-500'
                : 'border-stone-300'
            "
          >
            <input type="radio" v-model="paymentOption" value="paymongo" class="sr-only" />
            <div class="flex w-full items-center justify-between">
              <div class="flex items-center">
                <div class="text-sm">
                  <p class="font-medium text-stone-900">Pay Online</p>
                  <p class="text-stone-500">Card, GCash, Maya</p>
                </div>
              </div>
              <CheckCircleIcon v-if="paymentOption === 'paymongo'" class="h-5 w-5 text-moss-600" />
            </div>
          </label>

          <label
            class="relative flex cursor-pointer rounded-2xl border bg-white p-4 shadow-soft focus:outline-none"
            :class="
              paymentOption === 'cash' ? 'border-moss-500 ring-1 ring-moss-500' : 'border-stone-300'
            "
          >
            <input type="radio" v-model="paymentOption" value="cash" class="sr-only" />
            <div class="flex w-full items-center justify-between">
              <div class="flex items-center">
                <div class="text-sm">
                  <p class="font-medium text-stone-900">Cash (Off-Site)</p>
                  <p class="text-stone-500">Pay directly to farmer</p>
                </div>
              </div>
              <CheckCircleIcon v-if="paymentOption === 'cash'" class="h-5 w-5 text-moss-600" />
            </div>
          </label>
        </div>
      </div>
    </div>

    <div class="flex flex-col sm:flex-row justify-between items-end mb-8">
      <div class="mb-4 sm:mb-0 w-full sm:w-auto">
        <h4 class="text-sm font-medium text-stone-500 mb-2">Total Contract Value</h4>
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
              ? `Required ${PAYMENT_CONSTANTS.DOWNPAYMENT_PERCENTAGE * 100}% Downpayment`
              : 'Total Amount to Pay'
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
        Because this harvest is scheduled in the future, only a
        {{ PAYMENT_CONSTANTS.DOWNPAYMENT_PERCENTAGE * 100 }}% downpayment is required today to
        reserve your supply.
      </p>
    </div>

    <div class="flex flex-col-reverse sm:flex-row justify-end gap-3">
      <button
        @click="$emit('cancel')"
        :disabled="loading"
        class="px-5 py-2.5 border border-stone-300 shadow-soft text-sm font-medium rounded-xl text-stone-700 bg-white hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-moss-500 transition-all duration-300 hover:scale-[1.02] w-full sm:w-auto text-center disabled:opacity-50"
      >
        Cancel
      </button>
      <button
        @click="handleConfirm"
        :disabled="loading || !authStore.isEmailVerified"
        class="px-5 py-2.5 border border-transparent shadow-soft text-sm font-medium rounded-xl text-white bg-gradient-to-r from-moss-600 to-moss-700 hover:from-moss-700 hover:to-moss-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-moss-500 transition-all duration-300 hover:scale-[1.02] hover:-translate-y-1 w-full sm:w-auto text-center flex justify-center items-center disabled:opacity-50 disabled:transform-none"
      >
        <svg
          v-if="loading"
          class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        {{
          loading
            ? 'Preparing Checkout...'
            : !authStore.isEmailVerified
              ? 'Verify Email to Purchase'
              : 'Proceed to Payment'
        }}
      </button>
    </div>
  </div>
</template>
