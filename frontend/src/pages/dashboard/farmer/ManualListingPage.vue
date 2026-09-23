<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useMarketStore } from '@/stores/marketStore'
import { useNotificationStore } from '@/stores/notificationStore'
import AppButton from '@/components/atoms/AppButton.vue'
import AppInput from '@/components/atoms/AppInput.vue'

const router = useRouter()
const marketStore = useMarketStore()
const notificationStore = useNotificationStore()

const cropCatalog = [
  { name: 'Rice', defaultShelfLife: 180 },
  { name: 'Corn', defaultShelfLife: 90 },
  { name: 'Tomato', defaultShelfLife: 14 },
  { name: 'Potato', defaultShelfLife: 60 },
  { name: 'Onion', defaultShelfLife: 90 },
  { name: 'Other', defaultShelfLife: 30 }
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
  is_harvest_available: false
})

const isCustomCrop = computed(() => form.value.crop_name === 'Other')

const finalCropName = computed(() => isCustomCrop.value ? form.value.custom_crop_name : form.value.crop_name)

// Update shelf life when crop changes
watch(() => form.value.crop_name, (newCrop) => {
  const catalogEntry = cropCatalog.find(c => c.name === newCrop)
  if (catalogEntry) {
    form.value.shelf_life_days = catalogEntry.defaultShelfLife
  }
})

// Clear estimated date when harvest becomes available
watch(() => form.value.is_harvest_available, (isAvailable) => {
  if (isAvailable) {
    form.value.estimated_harvest_date = ''
  }
})

const isSubmitting = ref(false)

const handleSubmit = async () => {
  const needsDate = !form.value.is_harvest_available
  if (!finalCropName.value || !form.value.quantity_kg || !form.value.price_per_kg || (needsDate && !form.value.estimated_harvest_date) || !form.value.shelf_life_days) {
    notificationStore.error('Please fill in all required fields.')
    return
  }

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
      is_harvest_available: form.value.is_harvest_available
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
  <div class="py-6 max-w-3xl mx-auto">
    <div class="mb-8">
      <h2 class="font-serif text-3xl font-bold text-stone-900">Post Manual Harvest</h2>
      <p class="mt-1 text-sm text-stone-600">List your harvest directly on the marketplace without AI crop planning.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-soft border border-stone-200 p-6">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        
        <div class="space-y-4">
          <h3 class="font-serif text-xl font-bold text-stone-900 border-b border-stone-100 pb-2">Crop Details</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-soil-700 mb-1">Crop Type</label>
              <select v-model="form.crop_name" class="block w-full pl-4 pr-10 py-2.5 appearance-none bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20viewBox%3D%220%200%2020%2020%22%20stroke%3D%22%236b7280%22%3E%3Cpath%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-width%3D%221.5%22%20d%3D%22M6%208l4%204%204-4%22%2F%3E%3C%2Fsvg%3E')] bg-[length:1.25rem_1.25rem] bg-[position:right_1rem_center] bg-no-repeat border border-stone-300 rounded-xl shadow-sm bg-stone-50 text-stone-900 transition-all duration-200 sm:text-sm hover:border-stone-400 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white">
                <option v-for="crop in cropCatalog" :key="crop.name" :value="crop.name">
                  {{ crop.name }}
                </option>
              </select>
            </div>
            <div v-if="isCustomCrop">
              <label class="block text-sm font-medium text-soil-700 mb-1">Custom Crop Name</label>
              <AppInput v-model="form.custom_crop_name" placeholder="e.g. Cabbage" required />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-soil-700 mb-1">Shelf Life (Days)</label>
              <p class="text-xs text-stone-500 mb-2">Pre-populated from catalog, but you can edit it.</p>
              <AppInput type="number" v-model="form.shelf_life_days" min="1" required />
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <h3 class="font-serif text-xl font-bold text-stone-900 border-b border-stone-100 pb-2">Listing Details</h3>
          
          <div>
            <label class="block text-sm font-medium text-soil-700 mb-1">Title (Optional)</label>
            <AppInput v-model="form.title" placeholder="e.g. Premium Grade Rice Harvest" />
          </div>

          <div>
            <label class="block text-sm font-medium text-soil-700 mb-1">Description (Optional)</label>
            <textarea v-model="form.description" rows="3" class="block w-full px-4 py-2.5 border border-stone-300 rounded-xl shadow-sm placeholder-stone-400 transition-all duration-200 sm:text-sm bg-stone-50 text-stone-900 hover:border-stone-400 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white"></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-soil-700 mb-1">Quantity (kg)</label>
              <AppInput type="number" v-model="form.quantity_kg" min="1" step="0.1" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-soil-700 mb-1">Price per kg (₱)</label>
              <AppInput type="number" v-model="form.price_per_kg" min="0.01" step="0.01" required />
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <h3 class="font-serif text-xl font-bold text-stone-900 border-b border-stone-100 pb-2">Availability</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-soil-700 mb-1">Estimated Harvest Date</label>
              <AppInput type="date" v-model="form.estimated_harvest_date" :required="!form.is_harvest_available" :disabled="form.is_harvest_available" />
            </div>
            
            <div class="flex items-center mt-6">
              <input type="checkbox" id="harvest_available" v-model="form.is_harvest_available" class="h-4 w-4 text-moss-600 focus:ring-moss-500 border-gray-300 rounded">
              <label for="harvest_available" class="ml-2 block text-sm font-medium text-stone-700">
                Harvest is already available for pickup/delivery
              </label>
            </div>
          </div>
        </div>

        <div class="pt-4 flex justify-end">
          <AppButton type="submit" :loading="isSubmitting" class="bg-gradient-to-br from-moss-500 to-moss-600 text-white hover:from-moss-600 hover:to-moss-700">
            Post Listing
          </AppButton>
        </div>
      </form>
    </div>
  </div>
</template>
