<script setup>
import { reactive, computed, watch } from 'vue'

// Helper to format date as yyyy-MM-dd
function getFutureDateStr(daysAhead) {
  const date = new Date()
  date.setDate(date.getDate() + daysAhead)
  return date.toISOString().split('T')[0]
}

const props = defineProps({
  recommendation: {
    type: Object,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['publish', 'cancel', 'clear-errors'])

function extractNumber(yieldString) {
  if (!yieldString) return 1000;
  // If it contains "tons", multiply by 1000
  const matchTon = String(yieldString).match(/([\d.,]+)\s*ton/i);
  if (matchTon) {
    return parseFloat(matchTon[1].replace(',', '')) * 1000;
  }
  // If it contains "kg"
  const matchKg = String(yieldString).match(/([\d.,]+)\s*kg/i);
  if (matchKg) {
    return parseFloat(matchKg[1].replace(',', ''));
  }
  // Generic fallback
  const genericMatch = String(yieldString).match(/[\d.,]+/);
  return genericMatch ? parseFloat(genericMatch[0].replace(',', '')) : 1000;
}

const form = reactive({
  title: `${props.recommendation.crop_name} — Forward Contract`,
  description: '',
  quantity_kg: extractNumber(props.recommendation.projected_yield),
  price_per_kg: 50.00,
  estimated_harvest_date: getFutureDateStr(90),
  expiry_date: getFutureDateStr(75)
})

const totalPrice = computed(() => {
  return (form.quantity_kg * form.price_per_kg) || 0
})

const formattedTotalPrice = computed(() => {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(totalPrice.value)
})

watch(form, () => {
  if (Object.keys(props.errors).length > 0) {
    emit('clear-errors')
  }
}, { deep: true })

function submit() {
  emit('publish', { ...form })
}
</script>

<template>
  <div class="bg-white shadow-sm rounded-lg border border-gray-200">
    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50/50 rounded-t-lg">
      <h3 class="text-lg leading-6 font-medium text-gray-900">Publish Forward Contract</h3>
      <p class="mt-1 text-sm text-gray-500">
        Create a listing on the YieldGrid Marketplace based on your accepted recommendation for {{ recommendation.crop_name }}.
      </p>
    </div>
    
    <div class="px-6 py-6">
      <form @submit.prevent="submit" class="space-y-6">
        
        <div>
          <label for="title" class="block text-sm font-medium text-gray-700">Listing Title <span class="text-red-500">*</span></label>
          <div class="mt-1">
            <input type="text" id="title" v-model="form.title" required class="shadow-sm focus:ring-farm-500 focus:border-farm-500 block w-full sm:text-sm border-gray-300 rounded-md" :class="{'border-red-300 focus:ring-red-500 focus:border-red-500': errors.title}" />
          </div>
          <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title[0] }}</p>
        </div>

        <div>
          <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
          <div class="mt-1">
            <textarea id="description" v-model="form.description" rows="3" class="shadow-sm focus:ring-farm-500 focus:border-farm-500 block w-full sm:text-sm border-gray-300 rounded-md" :class="{'border-red-300 focus:ring-red-500 focus:border-red-500': errors.description}" placeholder="Add any details about your farming practices, crop quality, etc."></textarea>
          </div>
          <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description[0] }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div>
            <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity (kg) <span class="text-red-500">*</span></label>
            <div class="mt-1 relative rounded-md shadow-sm">
              <input type="number" id="quantity" v-model="form.quantity_kg" required min="1" step="0.1" class="focus:ring-farm-500 focus:border-farm-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md" :class="{'border-red-300 focus:ring-red-500 focus:border-red-500': errors.quantity_kg}" />
              <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <span class="text-gray-500 sm:text-sm">kg</span>
              </div>
            </div>
            <p v-if="errors.quantity_kg" class="mt-1 text-sm text-red-600">{{ errors.quantity_kg[0] }}</p>
            <p v-else class="mt-1 text-xs text-gray-500">Projected yield was {{ recommendation.projected_yield }}</p>
          </div>

          <div>
            <label for="price" class="block text-sm font-medium text-gray-700">Price per kg (₱) <span class="text-red-500">*</span></label>
            <div class="mt-1 relative rounded-md shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500 sm:text-sm">₱</span>
              </div>
              <input type="number" id="price" v-model="form.price_per_kg" required min="0.01" step="0.01" class="focus:ring-farm-500 focus:border-farm-500 block w-full pl-7 sm:text-sm border-gray-300 rounded-md" :class="{'border-red-300 focus:ring-red-500 focus:border-red-500': errors.price_per_kg}" />
            </div>
            <p v-if="errors.price_per_kg" class="mt-1 text-sm text-red-600">{{ errors.price_per_kg[0] }}</p>
          </div>
        </div>

        <div class="bg-gray-50 p-4 rounded-md border border-gray-100 flex justify-between items-center">
          <span class="text-sm font-medium text-gray-700">Total Contract Value:</span>
          <span class="text-xl font-bold text-farm-700">{{ formattedTotalPrice }}</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div>
            <label for="harvest" class="block text-sm font-medium text-gray-700">Estimated Harvest Date <span class="text-red-500">*</span></label>
            <div class="mt-1">
              <input type="date" id="harvest" v-model="form.estimated_harvest_date" required class="shadow-sm focus:ring-farm-500 focus:border-farm-500 block w-full sm:text-sm border-gray-300 rounded-md" :class="{'border-red-300 focus:ring-red-500 focus:border-red-500': errors.estimated_harvest_date}" />
            </div>
            <p v-if="errors.estimated_harvest_date" class="mt-1 text-sm text-red-600">{{ errors.estimated_harvest_date[0] }}</p>
          </div>

          <div>
            <label for="expiry" class="block text-sm font-medium text-gray-700">Listing Expiry Date <span class="text-red-500">*</span></label>
            <div class="mt-1">
              <input type="date" id="expiry" v-model="form.expiry_date" required class="shadow-sm focus:ring-farm-500 focus:border-farm-500 block w-full sm:text-sm border-gray-300 rounded-md" :class="{'border-red-300 focus:ring-red-500 focus:border-red-500': errors.expiry_date}" />
            </div>
            <p v-if="errors.expiry_date" class="mt-1 text-sm text-red-600">{{ errors.expiry_date[0] }}</p>
            <p v-else class="mt-1 text-xs text-gray-500">When the contract will be removed if unsold.</p>
          </div>
        </div>

        <div class="pt-5 flex justify-end gap-3 border-t border-gray-200 mt-8">
          <button type="button" @click="$emit('cancel')" :disabled="loading" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-farm-500 disabled:opacity-50">
            Cancel
          </button>
          <button type="submit" :disabled="loading" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gradient-to-r from-farm-600 to-farm-700 hover:from-farm-700 hover:to-farm-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-farm-500 disabled:opacity-50">
            <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ loading ? 'Publishing...' : 'Publish to Marketplace' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
