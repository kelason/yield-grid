<script setup>
import AppTextarea from '@/components/atoms/AppTextarea.vue'
import PageHeader from '@/components/molecules/PageHeader.vue'
import { usePendingConfirmation } from '@/composables/useConfirmModal'
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useMarketStore } from '@/stores/marketStore'
import { useNotificationStore } from '@/stores/notificationStore'
import AppButton from '@/components/atoms/AppButton.vue'
import AppInput from '@/components/atoms/AppInput.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import PriceGuidePopover from '@/components/molecules/PriceGuidePopover.vue'

const router = useRouter()
const marketStore = useMarketStore()
const notificationStore = useNotificationStore()

const cropCatalog = [
  { name: 'Rice', defaultShelfLife: 180 },
  { name: 'Corn', defaultShelfLife: 90 },
  { name: 'Tomato', defaultShelfLife: 14 },
  { name: 'Potato', defaultShelfLife: 60 },
  { name: 'Onion', defaultShelfLife: 90 },
  { name: 'Other', defaultShelfLife: 30 },
]

const form = ref({
  title: '',
  description: '',
  crop_name: 'Rice',
  custom_crop_name: '',
  quantity_kg: null,
  price_per_kg: null,
  estimated_harvest_date: '',
  shelf_life_days: 180,
  is_harvest_available: false,
})

const isCustomCrop = computed(() => form.value.crop_name === 'Other')

const finalCropName = computed(() =>
  isCustomCrop.value ? form.value.custom_crop_name : form.value.crop_name,
)

// Update shelf life when crop changes
watch(
  () => form.value.crop_name,
  (newCrop) => {
    const catalogEntry = cropCatalog.find((c) => c.name === newCrop)
    if (catalogEntry) {
      form.value.shelf_life_days = catalogEntry.defaultShelfLife
    }
  },
)

// Clear estimated date when harvest becomes available
watch(
  () => form.value.is_harvest_available,
  (isAvailable) => {
    if (isAvailable) {
      form.value.estimated_harvest_date = ''
    }
  },
)

const isSubmitting = ref(false)

const TITLE_MAX_LENGTH = 50
const DESCRIPTION_MAX_LENGTH = 5000
const CROP_NAME_MAX_LENGTH = 100
const QUANTITY_MIN_KG = 1
const QUANTITY_MAX_KG = 999999
const QUANTITY_MAX_DIGITS = 6
const PRICE_MIN = 0.01
const PRICE_MAX = 99999999
const PRICE_MAX_DIGITS = 8
const SHELF_MIN_DAYS = 1
const SHELF_MAX_DAYS = 9999
const SHELF_MAX_DIGITS = 4

const pendingConfirm = ref(null)
const { isExecuting, execute, cancel } = usePendingConfirmation(pendingConfirm)

const confirmConfig = computed(() => {
  if (!pendingConfirm.value) return null
  return {
    title: 'Post this listing?',
    message: `List ${pendingConfirm.value.quantity_kg} kg of ${pendingConfirm.value.crop_name} at ₱${pendingConfirm.value.price_per_kg}/kg on the marketplace?`,
    confirmText: 'Post listing',
    type: 'primary',
  }
})

const descriptionLength = computed(() => (form.value.description || '').length)
const isTitleOverLimit = computed(() => (form.value.title || '').length > TITLE_MAX_LENGTH)
const isDescriptionOverLimit = computed(() => descriptionLength.value > DESCRIPTION_MAX_LENGTH)

const validateListingForm = () => {
  if (isTitleOverLimit.value) {
    return `Title must be ${TITLE_MAX_LENGTH} characters or less.`
  }
  if (isDescriptionOverLimit.value) {
    return `Description must be ${DESCRIPTION_MAX_LENGTH} characters or less.`
  }
  if ((form.value.custom_crop_name || '').length > CROP_NAME_MAX_LENGTH) {
    return `Crop name cannot exceed ${CROP_NAME_MAX_LENGTH} characters.`
  }
  const qty = parseFloat(form.value.quantity_kg)
  if (!qty || qty < QUANTITY_MIN_KG) {
    return 'Please enter a quantity greater than zero.'
  }
  if (qty > QUANTITY_MAX_KG) {
    return `Quantity cannot exceed ${QUANTITY_MAX_KG.toLocaleString()} kg.`
  }
  const price = parseFloat(form.value.price_per_kg)
  if (!price || price < PRICE_MIN) {
    return 'Please enter a price greater than zero.'
  }
  if (price > PRICE_MAX) {
    return `Price cannot exceed ${PRICE_MAX_DIGITS} digits (₱${PRICE_MAX.toLocaleString()}).`
  }
  const shelf = parseInt(form.value.shelf_life_days)
  if (!shelf || shelf < SHELF_MIN_DAYS) {
    return 'Please enter a shelf life of at least 1 day.'
  }
  if (shelf > SHELF_MAX_DAYS) {
    return `Shelf life cannot exceed ${SHELF_MAX_DAYS.toLocaleString()} days.`
  }
  const needsDate = !form.value.is_harvest_available
  if (!finalCropName.value || (needsDate && !form.value.estimated_harvest_date)) {
    return 'Please fill in all required fields.'
  }
  return null
}

const askListingConfirm = () => {
  const error = validateListingForm()
  if (error) {
    notificationStore.error(error)
    return
  }
  pendingConfirm.value = {
    crop_name: finalCropName.value,
    quantity_kg: form.value.quantity_kg,
    price_per_kg: form.value.price_per_kg,
  }
}

const handleSubmit = () => execute(performConfirmedAction)

const performConfirmedAction = async () => {
  isSubmitting.value = true
  try {
    const payload = {
      title: form.value.title || `${finalCropName.value} Harvest`,
      description: form.value.description,
      crop_name: finalCropName.value,
      quantity_kg: form.value.quantity_kg,
      price_per_kg: form.value.price_per_kg,
      estimated_harvest_date: form.value.estimated_harvest_date,
      shelf_life_days: form.value.shelf_life_days,
      is_harvest_available: form.value.is_harvest_available,
    }

    await marketStore.createManualListing(payload)
    notificationStore.success('Listing created successfully!')
    router.push({ name: 'farmer-contracts' })
  } catch (error) {
    const msg = error.response?.data?.message || 'Failed to create listing'
    notificationStore.error(msg)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      title="Post Manual Harvest"
      description="List your harvest directly on the marketplace without AI crop planning."
    />

    <div class="bg-white rounded-2xl shadow-soft border border-stone-200 p-6">
      <form @submit.prevent="askListingConfirm" class="space-y-6">
        <div class="space-y-4">
          <h3 class="font-serif text-xl font-bold text-stone-900 border-b border-stone-300 pb-2">
            Crop Details
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="crop-type" class="block text-sm font-medium text-soil-700 mb-1"
                >Crop Type</label
              >
              <AppSelect id="crop-type" v-model="form.crop_name">
                <option v-for="crop in cropCatalog" :key="crop.name" :value="crop.name">
                  {{ crop.name }}
                </option>
              </AppSelect>
            </div>
            <div v-if="isCustomCrop">
              <label for="custom_crop_name" class="block text-sm font-medium text-soil-700 mb-1"
                >Custom Crop Name</label
              >
              <AppInput
                id="custom_crop_name"
                v-model="form.custom_crop_name"
                placeholder="e.g. Cabbage"
                :maxlength="CROP_NAME_MAX_LENGTH"
                required
              />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="shelf_life_days" class="block text-sm font-medium text-soil-700 mb-1"
                >Shelf Life (Days)</label
              >
              <p class="text-xs text-stone-500 mb-2">
                Pre-populated from catalog, but you can edit it.
              </p>
              <AppInput
                id="shelf_life_days"
                type="number"
                v-model="form.shelf_life_days"
                :maxlength="SHELF_MAX_DIGITS"
                :min="SHELF_MIN_DAYS"
                :max="SHELF_MAX_DAYS"
                required
              />
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <h3 class="font-serif text-xl font-bold text-stone-900 border-b border-stone-300 pb-2">
            Listing Details
          </h3>

          <div>
            <div class="flex items-center justify-between mb-1">
              <label for="title" class="block text-sm font-medium text-soil-700"
                >Title (Optional)</label
              >
              <span
                class="text-[11px]"
                :class="isTitleOverLimit ? 'text-red-600 font-semibold' : 'text-stone-400'"
              >
                {{ (form.title || '').length }}/{{ TITLE_MAX_LENGTH }}
              </span>
            </div>
            <AppInput
              id="title"
              v-model="form.title"
              placeholder="e.g. Premium Grade Rice Harvest"
              :maxlength="TITLE_MAX_LENGTH"
            />
          </div>

          <div>
            <div class="flex items-center justify-between mb-1">
              <label for="listing-description" class="block text-sm font-medium text-soil-700"
                >Description (Optional)</label
              >
              <span
                class="text-[11px]"
                :class="isDescriptionOverLimit ? 'text-red-600 font-semibold' : 'text-stone-400'"
                id="listing-description-counter"
              >
                {{ descriptionLength }}/{{ DESCRIPTION_MAX_LENGTH }}
              </span>
            </div>
            <AppTextarea
              id="listing-description"
              aria-describedby="listing-description-counter"
              minlength="0"
              v-model="form.description"
              rows="3"
              :maxlength="DESCRIPTION_MAX_LENGTH"
            ></AppTextarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="quantity_kg" class="block text-sm font-medium text-soil-700 mb-1"
                >Quantity (kg)</label
              >
              <AppInput
                id="quantity_kg"
                type="number"
                v-model="form.quantity_kg"
                :maxlength="QUANTITY_MAX_DIGITS"
                :min="QUANTITY_MIN_KG"
                :max="QUANTITY_MAX_KG"
                step="0.1"
                required
              />
            </div>
            <div>
              <div class="mb-1 flex items-center gap-1">
                <label for="price_per_kg" class="block text-sm font-medium text-soil-700"
                  >Price per kg (₱)</label
                >
                <PriceGuidePopover
                  :crop-name="finalCropName"
                  :current-price="Number(form.price_per_kg) || null"
                />
              </div>
              <AppInput
                id="price_per_kg"
                type="number"
                v-model="form.price_per_kg"
                :maxlength="PRICE_MAX_DIGITS"
                :min="PRICE_MIN"
                :max="PRICE_MAX"
                step="0.01"
                required
              />
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <h3 class="font-serif text-xl font-bold text-stone-900 border-b border-stone-300 pb-2">
            Availability
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label
                for="estimated_harvest_date"
                class="block text-sm font-medium text-soil-700 mb-1"
                >Estimated Harvest Date</label
              >
              <AppInput
                id="estimated_harvest_date"
                type="date"
                v-model="form.estimated_harvest_date"
                :required="!form.is_harvest_available"
                :disabled="form.is_harvest_available"
              />
            </div>

            <div class="flex items-center mt-6">
              <input
                type="checkbox"
                id="harvest_available"
                v-model="form.is_harvest_available"
                class="h-4 w-4 text-moss-600 focus:ring-moss-500 border-stone-300 rounded-xl"
              />
              <label for="harvest_available" class="ml-2 block text-sm font-medium text-soil-700">
                Harvest is already available for pickup/delivery
              </label>
            </div>
          </div>
        </div>

        <div class="pt-4 flex justify-end">
          <AppButton
            type="submit"
            class="bg-gradient-to-br from-moss-500 to-moss-600 text-white hover:from-moss-600 hover:to-moss-700"
          >
            Post Listing
          </AppButton>
        </div>
      </form>
    </div>

    <ConfirmModal
      v-if="confirmConfig"
      :is-open="pendingConfirm !== null"
      :title="confirmConfig.title"
      :message="confirmConfig.message"
      :confirm-text="confirmConfig.confirmText"
      :type="confirmConfig.type"
      @confirm="handleSubmit"
      :loading="isExecuting"
      @cancel="cancel"
    />
  </div>
</template>
