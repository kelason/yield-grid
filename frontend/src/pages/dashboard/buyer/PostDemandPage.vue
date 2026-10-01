<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useDemandStore } from '@/stores/demandStore'
import { useAddressStore } from '@/stores/addressStore'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notificationStore'
import FormField from '@/components/molecules/FormField.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppAlert from '@/components/atoms/AppAlert.vue'
import AppCard from '@/components/atoms/AppCard.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'

const router = useRouter()
const demandStore = useDemandStore()
const addressStore = useAddressStore()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()

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

const DEMAND_TOTAL_MAX = 9999999999.99

const addressOptions = computed(() =>
  addressStore.addresses.map((a) => ({
    value: a.id,
    label: `${a.label || 'Address'} — ${a.formatted_address}`,
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
    notificationStore.error('Failed to load your addresses.')
  }
})

async function handleSubmit() {
  errors.value = {}
  const qty = parseFloat(form.value.quantity_kg)
  const target = parseFloat(form.value.target_price_per_kg)
  if (qty > 0 && target > 0 && qty * target > DEMAND_TOTAL_MAX) {
    errors.value.quantity_kg =
      'The combined quantity and target price exceed the maximum order total.'
    return
  }
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
    notificationStore.success('Demand posted! Farmers can now send you offers.')
    router.push({ name: 'buyer-demands' })
  } catch (err) {
    const apiErrors = err.response?.data?.errors || {}
    Object.keys(apiErrors).forEach((key) => {
      errors.value[key] = apiErrors[key][0]
    })
    if (Object.keys(errors.value).length === 0) {
      notificationStore.error(err.response?.data?.message || 'Failed to post demand.')
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <div>
      <h1 class="font-serif text-3xl font-bold text-stone-900">Post a Crop Demand</h1>
      <p class="text-base text-stone-600 font-light mt-1">
        Tell farmers what you need — they compete with offers and you pick the best.
      </p>
    </div>

    <AppAlert v-if="!addressStore.loading && addressStore.addresses.length === 0" type="warning">
      You need a saved delivery address before posting.
      <RouterLink
        :to="{ name: 'user-profile', params: { userId: authStore.user?.id } }"
        class="font-semibold underline hover:text-harvest-700"
      >
        Add one in your profile
      </RouterLink>
      first.
    </AppAlert>

    <AppCard padding="p-6">
      <form class="space-y-5" @submit.prevent="handleSubmit">
        <FormField
          id="demand-title"
          label="Title"
          placeholder="e.g. 600kg fresh tomatoes for July"
          v-model="form.title"
          :required="true"
          :error="errors.title || ''"
        />
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <FormField
            id="demand-crop"
            label="Crop"
            placeholder="e.g. Tomato"
            v-model="form.crop_name"
            :required="true"
            :error="errors.crop_name || ''"
          />
          <FormField
            id="demand-qty"
            label="Quantity needed (kg)"
            type="number"
            v-model="form.quantity_kg"
            :required="true"
            :error="errors.quantity_kg || ''"
          />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <FormField
            id="demand-price"
            label="Target price per kg (₱)"
            type="number"
            v-model="form.target_price_per_kg"
            :required="true"
            :error="errors.target_price_per_kg || ''"
          />
          <AppSelect
            id="demand-address"
            label="Delivery address"
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
              Needed by <span class="text-red-500">*</span>
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
              Offers close on <span class="text-red-500">*</span>
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
            Notes for farmers
          </label>
          <textarea
            id="demand-desc"
            v-model="form.description"
            rows="3"
            placeholder="Quality requirements, delivery notes…"
            class="mt-1 block w-full px-4 py-2.5 border border-stone-300 rounded-xl shadow-soft bg-stone-50 placeholder-stone-400 text-stone-900 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white sm:text-sm transition-all duration-200"
          ></textarea>
        </div>

        <div class="flex justify-end gap-3">
          <AppButton variant="ghost" @click="router.back()">Cancel</AppButton>
          <AppButton
            type="submit"
            variant="primary"
            :loading="submitting"
            :disabled="addressStore.addresses.length === 0"
          >
            Post demand
          </AppButton>
        </div>
      </form>
    </AppCard>
  </div>
</template>
