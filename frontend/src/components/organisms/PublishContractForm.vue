<script setup>
import AppTextarea from '@/components/atoms/AppTextarea.vue'
import { reactive, computed, watch } from 'vue'
import PriceGuideHint from '@/components/molecules/PriceGuideHint.vue'
import AppInput from '@/components/atoms/AppInput.vue'
import AppButton from '@/components/atoms/AppButton.vue'

// Helper to format date as yyyy-MM-dd
function getFutureDateStr(daysAhead) {
  const date = new Date()
  date.setDate(date.getDate() + daysAhead)
  return date.toISOString().split('T')[0]
}

const CONTRACT_TITLE_MAX_LENGTH = 255
const CONTRACT_DESCRIPTION_MAX_LENGTH = 5000
const CONTRACT_QUANTITY_MIN_KG = 1
const CONTRACT_QUANTITY_MAX_KG = 99999999
const CONTRACT_PRICE_MIN = 0.01
const CONTRACT_PRICE_MAX = 99999999
const TODAY_ISO = new Date().toISOString().split('T')[0]
// Unit conversion factor, also used as the default yield assumption (kg) when parsing fails
const KILOGRAMS_PER_TON = 1000

const props = defineProps({
  recommendation: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['publish', 'cancel', 'clear-errors'])

function extractNumber(yieldString) {
  if (!yieldString) return KILOGRAMS_PER_TON
  // If it contains "tons", multiply by 1000
  const matchTon = String(yieldString).match(/([\d.,]+)\s*ton/i)
  if (matchTon) {
    return parseFloat(matchTon[1].replace(',', '')) * KILOGRAMS_PER_TON
  }
  // If it contains "kg"
  const matchKg = String(yieldString).match(/([\d.,]+)\s*kg/i)
  if (matchKg) {
    return parseFloat(matchKg[1].replace(',', ''))
  }
  // Generic fallback
  const genericMatch = String(yieldString).match(/[\d.,]+/)
  return genericMatch ? parseFloat(genericMatch[0].replace(',', '')) : KILOGRAMS_PER_TON
}

const form = reactive({
  title: `${props.recommendation.crop_name} — Forward Contract`,
  description: '',
  quantity_kg: extractNumber(props.recommendation.projected_yield),
  price_per_kg: 50.0,
  estimated_harvest_date: getFutureDateStr(90),
  expiry_date: getFutureDateStr(75),
})

const totalPrice = computed(() => {
  return form.quantity_kg * form.price_per_kg || 0
})

const formattedTotalPrice = computed(() => {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(
    totalPrice.value,
  )
})

watch(
  form,
  () => {
    if (Object.keys(props.errors).length > 0) {
      emit('clear-errors')
    }
  },
  { deep: true },
)

function submit() {
  emit('publish', { ...form })
}
</script>

<template>
  <div class="bg-white shadow-soft rounded-2xl border border-stone-200">
    <div class="px-6 py-5 border-b border-stone-200 bg-stone-50/50 rounded-t-2xl">
      <h3 class="font-serif text-lg leading-6 font-medium text-stone-900">
        Publish Forward Contract
      </h3>
      <p class="mt-1 text-sm text-stone-500">
        Create a listing on the YieldGrid Marketplace based on your accepted recommendation for
        {{ recommendation.crop_name }}.
      </p>
    </div>

    <div class="px-6 py-6">
      <form @submit.prevent="submit" class="space-y-6">
        <div>
          <label for="title" class="block text-sm font-medium text-soil-700 mb-1"
            >Listing Title <span class="text-red-500">*</span></label
          >
          <AppInput
            id="title"
            v-model="form.title"
            required
            :maxlength="CONTRACT_TITLE_MAX_LENGTH"
            :error="errors.title?.[0] ?? ''"
          />
          <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title[0] }}</p>
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label for="description" class="block text-sm font-medium text-soil-700"
              >Description</label
            >
            <span class="text-sm text-stone-600" id="description-counter">
              {{ (form.description || '').length }}/{{ CONTRACT_DESCRIPTION_MAX_LENGTH }}
            </span>
          </div>
          <AppTextarea
            minlength="0"
            id="description"
            aria-describedby="description-counter"
            v-model="form.description"
            rows="3"
            :maxlength="CONTRACT_DESCRIPTION_MAX_LENGTH"
            placeholder="Add any details about your farming practices, crop quality, etc."
          ></AppTextarea>
          <p v-if="errors.description" class="mt-1 text-sm text-red-600">
            {{ errors.description[0] }}
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div>
            <label for="quantity" class="block text-sm font-medium text-soil-700 mb-1"
              >Quantity (kg) <span class="text-red-500">*</span></label
            >
            <AppInput
              id="quantity"
              type="number"
              v-model="form.quantity_kg"
              required
              :min="CONTRACT_QUANTITY_MIN_KG"
              :max="CONTRACT_QUANTITY_MAX_KG"
              step="0.1"
              :error="errors.quantity_kg?.[0] ?? ''"
            />
            <p v-if="errors.quantity_kg" class="mt-1 text-sm text-red-600">
              {{ errors.quantity_kg[0] }}
            </p>
            <p v-else class="mt-1 text-xs text-stone-500">
              Projected yield was {{ recommendation.projected_yield }}
            </p>
          </div>

          <div>
            <label for="price" class="block text-sm font-medium text-soil-700 mb-1"
              >Price per kg (₱) <span class="text-red-500">*</span></label
            >
            <AppInput
              id="price"
              type="number"
              v-model="form.price_per_kg"
              required
              :min="CONTRACT_PRICE_MIN"
              :max="CONTRACT_PRICE_MAX"
              step="0.01"
              :error="errors.price_per_kg?.[0] ?? ''"
            />
            <p v-if="errors.price_per_kg" class="mt-1 text-sm text-red-600">
              {{ errors.price_per_kg[0] }}
            </p>
            <PriceGuideHint
              :crop-name="recommendation.crop_name"
              :current-price="Number(form.price_per_kg) || null"
              class="mt-2"
            />
          </div>
        </div>

        <div
          class="bg-stone-50 p-4 rounded-xl border border-stone-100 flex justify-between items-center"
        >
          <span class="text-sm font-medium text-stone-700">Total Contract Value:</span>
          <span class="text-xl font-bold text-harvest-700">{{ formattedTotalPrice }}</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div>
            <label for="harvest" class="block text-sm font-medium text-soil-700 mb-1"
              >Estimated Harvest Date <span class="text-red-500">*</span></label
            >
            <AppInput
              id="harvest"
              type="date"
              v-model="form.estimated_harvest_date"
              required
              :min="TODAY_ISO"
              :error="errors.estimated_harvest_date?.[0] ?? ''"
            />
            <p v-if="errors.estimated_harvest_date" class="mt-1 text-sm text-red-600">
              {{ errors.estimated_harvest_date[0] }}
            </p>
          </div>

          <div>
            <label for="expiry" class="block text-sm font-medium text-soil-700 mb-1"
              >Listing Expiry Date <span class="text-red-500">*</span></label
            >
            <AppInput
              id="expiry"
              type="date"
              v-model="form.expiry_date"
              required
              :min="TODAY_ISO"
              :max="form.estimated_harvest_date || undefined"
              :error="errors.expiry_date?.[0] ?? ''"
            />
            <p v-if="errors.expiry_date" class="mt-1 text-sm text-red-600">
              {{ errors.expiry_date[0] }}
            </p>
            <p v-else class="mt-1 text-xs text-stone-500">
              When the contract will be removed if unsold.
            </p>
          </div>
        </div>

        <div class="pt-5 flex justify-end gap-3 border-t border-stone-200 mt-8">
          <AppButton variant="ghost" :disabled="loading" @click="$emit('cancel')">
            Cancel
          </AppButton>
          <AppButton type="submit" variant="primary" :loading="loading" :disabled="loading">
            {{ loading ? 'Publishing...' : 'Publish to Marketplace' }}
          </AppButton>
        </div>
      </form>
    </div>
  </div>
</template>
