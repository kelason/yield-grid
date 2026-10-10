<script setup>
import AppTextarea from '@/components/atoms/AppTextarea.vue'
import { usePendingConfirmation } from '@/composables/useConfirmModal'
import { onMounted, ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter, RouterLink } from 'vue-router'
import { useDemandStore } from '@/stores/demandStore'
import { useAddressStore } from '@/stores/addressStore'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notificationStore'
import FormField from '@/components/molecules/FormField.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import PriceGuidePopover from '@/components/molecules/PriceGuidePopover.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppAlert from '@/components/atoms/AppAlert.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'

const router = useRouter()
const demandStore = useDemandStore()
const addressStore = useAddressStore()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()
const { t } = useI18n()

const form = ref({
  title: '',
  crop_name: '',
  quantity_kg: '',
  target_price_per_kg: '',
  needed_by_date: '',
  expiry_date: '',
  description: '',
  address_id: '',
})
const errors = ref({})
const submitting = ref(false)
const pendingConfirm = ref(null)
const { isExecuting, execute, cancel } = usePendingConfirmation(pendingConfirm)

const DEMAND_TOTAL_MAX = 9999999999.99
const DEMAND_TITLE_MAX_LENGTH = 255
const DEMAND_CROP_NAME_MAX_LENGTH = 100
const DEMAND_QUANTITY_MIN_KG = 0.01
const DEMAND_QUANTITY_MAX_KG = 1000000
const DEMAND_PRICE_MIN = 0.01
const DEMAND_PRICE_MAX = 1000000
const DEMAND_DESCRIPTION_MAX_LENGTH = 2000

const confirmConfig = computed(() => {
  if (!pendingConfirm.value) return null
  return {
    title: t('buyer.post.confirm_title'),
    message: t('buyer.post.confirm_msg', {
      title: pendingConfirm.value.title,
      qty: pendingConfirm.value.quantity_kg,
      crop: pendingConfirm.value.crop_name,
    }),
    confirmText: t('buyer.post.confirm_ok'),
    type: 'primary',
  }
})

const addressOptions = computed(() =>
  addressStore.addresses.map((a) => ({
    value: a.id,
    label: `${a.label || t('buyer.post.address_fallback')} — ${a.formatted_address}`,
  })),
)

function toISODate(date) {
  return date.toISOString().slice(0, 10)
}

const todayISO = toISODate(new Date())

onMounted(async () => {
  try {
    await addressStore.fetchAddresses()
    if (addressStore.defaultAddress) {
      form.value.address_id = addressStore.defaultAddress.id
    }
  } catch {
    notificationStore.addNotification({
      type: 'error',
      messageKey: 'buyer.post.load_addresses_error',
    })
  }
})

function validateDemandForm() {
  const fieldErrors = {}
  const title = (form.value.title || '').trim()
  const crop = (form.value.crop_name || '').trim()
  const qty = parseFloat(form.value.quantity_kg)
  const target = parseFloat(form.value.target_price_per_kg)
  const description = (form.value.description || '').trim()
  if (title.length > DEMAND_TITLE_MAX_LENGTH) {
    fieldErrors.title = t('buyer.post.err_title', { max: DEMAND_TITLE_MAX_LENGTH })
  }
  if (crop.length > DEMAND_CROP_NAME_MAX_LENGTH) {
    fieldErrors.crop_name = t('buyer.post.err_crop', { max: DEMAND_CROP_NAME_MAX_LENGTH })
  }
  if (!qty || qty < DEMAND_QUANTITY_MIN_KG) {
    fieldErrors.quantity_kg = t('buyer.post.err_qty_zero')
  } else if (qty > DEMAND_QUANTITY_MAX_KG) {
    fieldErrors.quantity_kg = t('buyer.post.err_qty_max', {
      max: DEMAND_QUANTITY_MAX_KG.toLocaleString(),
    })
  }
  if (!target || target < DEMAND_PRICE_MIN) {
    fieldErrors.target_price_per_kg = t('buyer.post.err_price_zero')
  } else if (target > DEMAND_PRICE_MAX) {
    fieldErrors.target_price_per_kg = t('buyer.post.err_price_max', {
      max: DEMAND_PRICE_MAX.toLocaleString(),
    })
  }
  if (description.length > DEMAND_DESCRIPTION_MAX_LENGTH) {
    fieldErrors.description = t('buyer.post.err_notes', { max: DEMAND_DESCRIPTION_MAX_LENGTH })
  }
  validateDemandTotal(fieldErrors, qty, target)
  return fieldErrors
}

function validateDemandTotal(fieldErrors, qty, target) {
  if (
    Object.keys(fieldErrors).length === 0 &&
    qty > 0 &&
    target > 0 &&
    qty * target > DEMAND_TOTAL_MAX
  ) {
    fieldErrors.quantity_kg = t('buyer.post.err_total')
  }
}

function askDemandConfirm() {
  errors.value = validateDemandForm()
  if (Object.keys(errors.value).length > 0) return
  pendingConfirm.value = {
    title: form.value.title,
    crop_name: form.value.crop_name,
    quantity_kg: form.value.quantity_kg,
  }
}

const confirmPendingDemand = () => execute(performConfirmedAction)

async function performConfirmedAction() {
  submitting.value = true
  try {
    await demandStore.postDemand({
      title: form.value.title,
      crop_name: form.value.crop_name,
      quantity_kg: parseFloat(form.value.quantity_kg),
      target_price_per_kg: parseFloat(form.value.target_price_per_kg),
      needed_by_date: form.value.needed_by_date,
      expiry_date: form.value.expiry_date,
      description: form.value.description || null,
      address_id: form.value.address_id,
    })
    notificationStore.addNotification({ type: 'success', messageKey: 'buyer.post.posted' })
    router.push({ name: 'buyer-demands' })
  } catch (err) {
    const apiErrors = err.response?.data?.errors || {}
    Object.keys(apiErrors).forEach((key) => {
      errors.value[key] = apiErrors[key][0]
    })
    if (Object.keys(errors.value).length === 0) {
      if (err.response?.data?.message) {
        notificationStore.error(err.response.data.message)
      } else {
        notificationStore.addNotification({ type: 'error', messageKey: 'buyer.post.post_failed' })
      }
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="font-serif text-3xl font-bold text-stone-900">{{ t('buyer.post.title') }}</h1>
      <p class="text-base text-stone-600 font-normal mt-1">
        {{ t('buyer.post.description') }}
      </p>
    </div>

    <AppAlert v-if="!addressStore.loading && addressStore.addresses.length === 0" type="warning">
      {{ t('buyer.post.no_address') }}
      <RouterLink
        :to="{ name: 'user-profile', params: { userId: authStore.user?.id } }"
        class="font-semibold underline hover:text-harvest-700"
      >
        {{ t('buyer.post.add_address') }}
      </RouterLink>
      {{ t('buyer.post.no_address_suffix') }}
    </AppAlert>

    <AppCard padding="p-6">
      <form class="space-y-5" @submit.prevent="askDemandConfirm">
        <FormField
          id="demand-title"
          :label="t('buyer.post.field_title')"
          :placeholder="t('buyer.post.title_ph')"
          v-model="form.title"
          :required="true"
          :maxlength="DEMAND_TITLE_MAX_LENGTH"
          :error="errors.title || ''"
        />
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <FormField
            id="demand-crop"
            :label="t('buyer.post.field_crop')"
            :placeholder="t('buyer.post.crop_ph')"
            v-model="form.crop_name"
            :required="true"
            :maxlength="DEMAND_CROP_NAME_MAX_LENGTH"
            :error="errors.crop_name || ''"
          />
          <FormField
            id="demand-qty"
            :label="t('buyer.post.field_qty')"
            type="number"
            v-model="form.quantity_kg"
            :required="true"
            :min="DEMAND_QUANTITY_MIN_KG"
            :max="DEMAND_QUANTITY_MAX_KG"
            :error="errors.quantity_kg || ''"
          />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 [&>div>span]:flex [&>div>span]:min-h-11">
          <FormField
            id="demand-price"
            :label="t('buyer.post.field_price')"
            type="number"
            v-model="form.target_price_per_kg"
            :required="true"
            :min="DEMAND_PRICE_MIN"
            :max="DEMAND_PRICE_MAX"
            :error="errors.target_price_per_kg || ''"
          >
            <template #labelSuffix>
              <PriceGuidePopover
                :crop-name="form.crop_name"
                :current-price="Number(form.target_price_per_kg) || null"
              />
            </template>
          </FormField>
          <AppSelect
            id="demand-address"
            :label="t('buyer.post.field_address')"
            :required="true"
            :options="addressOptions"
            :model-value="form.address_id"
            :error="errors.address_id || ''"
            @update:model-value="form.address_id = $event"
          />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="demand-needed" class="block text-sm font-medium text-soil-700">
              {{ t('buyer.post.field_needed') }} <span class="text-red-500">*</span>
            </label>
            <input
              id="demand-needed"
              type="date"
              v-model="form.needed_by_date"
              :min="todayISO"
              required
              class="mt-1 block w-full px-4 py-2.5 border border-stone-300 rounded-xl shadow-soft bg-stone-50 text-stone-900 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white sm:text-sm transition-all duration-200"
            />
            <p v-if="errors.needed_by_date" class="mt-1 text-sm text-red-600">
              {{ errors.needed_by_date }}
            </p>
          </div>
          <div>
            <label for="demand-expiry" class="block text-sm font-medium text-soil-700">
              {{ t('buyer.post.field_expiry') }} <span class="text-red-500">*</span>
            </label>
            <input
              id="demand-expiry"
              type="date"
              v-model="form.expiry_date"
              :min="todayISO"
              :max="form.needed_by_date || undefined"
              required
              class="mt-1 block w-full px-4 py-2.5 border border-stone-300 rounded-xl shadow-soft bg-stone-50 text-stone-900 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white sm:text-sm transition-all duration-200"
            />
            <p v-if="errors.expiry_date" class="mt-1 text-sm text-red-600">
              {{ errors.expiry_date }}
            </p>
          </div>
        </div>
        <div>
          <label for="demand-desc" class="block text-sm font-medium text-soil-700">
            {{ t('buyer.post.field_notes') }}
          </label>
          <AppTextarea
            minlength="0"
            id="demand-desc"
            aria-describedby="demand-desc-counter"
            v-model="form.description"
            rows="3"
            :maxlength="DEMAND_DESCRIPTION_MAX_LENGTH"
            :placeholder="t('buyer.post.notes_ph')"
            class="mt-1"
          ></AppTextarea>
          <p class="text-xs text-stone-500 mt-1 text-right" id="demand-desc-counter">
            {{ (form.description || '').length }} / {{ DEMAND_DESCRIPTION_MAX_LENGTH }}
          </p>
          <p v-if="errors.description" class="mt-1 text-sm text-red-600">
            {{ errors.description }}
          </p>
        </div>

        <div class="flex justify-end gap-3">
          <AppButton variant="ghost" @click="router.back()">{{ t('shell.cancel') }}</AppButton>
          <AppButton
            type="submit"
            variant="primary"
            :disabled="addressStore.addresses.length === 0"
          >
            {{ t('buyer.post.submit') }}
          </AppButton>
        </div>
      </form>
    </AppCard>

    <ConfirmModal
      v-if="confirmConfig"
      :is-open="pendingConfirm !== null"
      :title="confirmConfig.title"
      :message="confirmConfig.message"
      :confirm-text="confirmConfig.confirmText"
      :type="confirmConfig.type"
      @confirm="confirmPendingDemand"
      :loading="isExecuting"
      @cancel="cancel"
    />
  </div>
</template>
