<script setup>
import { reactive, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import PriceGuideHint from '@/components/molecules/PriceGuideHint.vue'
import FormField from '@/components/molecules/FormField.vue'
import AppAlert from '@/components/atoms/AppAlert.vue'
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
const DEFAULT_PRICE_PER_KG = 50
const DEFAULT_HARVEST_DAYS_AHEAD = 90
const DEFAULT_EXPIRY_DAYS_AHEAD = 75
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

const { t } = useI18n()

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
  price_per_kg: DEFAULT_PRICE_PER_KG,
  estimated_harvest_date: getFutureDateStr(DEFAULT_HARVEST_DAYS_AHEAD),
  expiry_date: getFutureDateStr(DEFAULT_EXPIRY_DAYS_AHEAD),
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
    if (Object.keys(props.errors || {}).length > 0) {
      emit('clear-errors')
    }
  },
  { deep: true },
)

function submit() {
  if (props.loading) return
  emit('publish', { ...form })
}
</script>

<template>
  <form @submit.prevent="submit" class="space-y-6">
    <p class="text-sm text-stone-600">
      {{ t('market.publish.intro', { crop: recommendation.crop_name }) }}
    </p>
    <AppAlert v-if="errors?.form?.[0]" type="error">{{ errors.form[0] }}</AppAlert>
    <FormField
      id="title"
      :label="t('market.publish.title_label')"
      v-model="form.title"
      required
      :maxlength="CONTRACT_TITLE_MAX_LENGTH"
      :error="errors?.title?.[0] || ''"
    />
    <FormField
      id="description"
      :label="t('market.publish.desc_label')"
      v-model="form.description"
      multiline
      rows="3"
      :maxlength="CONTRACT_DESCRIPTION_MAX_LENGTH"
      :error="errors?.description?.[0] || ''"
      :placeholder="t('market.publish.desc_ph')"
    />
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <FormField
        id="quantity"
        :label="t('market.publish.qty_label')"
        type="number"
        v-model="form.quantity_kg"
        required
        :min="CONTRACT_QUANTITY_MIN_KG"
        :max="CONTRACT_QUANTITY_MAX_KG"
        step="0.1"
        :error="errors?.quantity_kg?.[0] || ''"
        :hint="t('market.publish.qty_hint', { yield: recommendation.projected_yield })"
      />
      <div>
        <FormField
          id="price"
          :label="t('market.publish.price_label')"
          type="number"
          v-model="form.price_per_kg"
          required
          :min="CONTRACT_PRICE_MIN"
          :max="CONTRACT_PRICE_MAX"
          step="0.01"
          :error="errors?.price_per_kg?.[0] || ''"
        /><PriceGuideHint
          :crop-name="recommendation.crop_name"
          :current-price="Number(form.price_per_kg) || null"
          class="mt-2"
        />
      </div>
    </div>
    <div
      class="flex flex-wrap justify-between gap-3 rounded-xl border border-stone-200 bg-stone-50 p-4"
    >
      <span class="text-sm text-stone-600">{{ t('market.publish.total') }}</span
      ><strong class="text-xl text-harvest-700 break-words">{{ formattedTotalPrice }}</strong>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <FormField
        id="harvest"
        :label="t('market.publish.harvest_label')"
        type="date"
        v-model="form.estimated_harvest_date"
        required
        :min="TODAY_ISO"
        :error="errors?.estimated_harvest_date?.[0] || ''"
      />
      <FormField
        id="expiry"
        :label="t('market.publish.expiry_label')"
        type="date"
        v-model="form.expiry_date"
        required
        :min="TODAY_ISO"
        :max="form.estimated_harvest_date || undefined"
        :error="errors?.expiry_date?.[0] || ''"
        :hint="t('market.publish.expiry_hint')"
      />
    </div>
    <div class="flex flex-wrap justify-end gap-3 border-t border-stone-200 pt-5">
      <AppButton variant="ghost" :disabled="loading" @click="$emit('cancel')">{{
        t('shell.cancel')
      }}</AppButton>
      <AppButton type="submit" :loading="loading">{{
        loading ? t('market.publish.publishing') : t('market.publish.publish')
      }}</AppButton>
    </div>
  </form>
</template>
