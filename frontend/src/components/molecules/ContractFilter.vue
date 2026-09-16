<script setup>
import { ref, watch } from 'vue'
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:modelValue', 'search'])

const localFilters = ref({ ...props.modelValue })

// Debounce search
let searchTimeout = null
watch(() => localFilters.value.crop, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      emit('update:modelValue', localFilters.value)
      emit('search')
    }, 300)
  }
})

function applyFilters() {
  emit('update:modelValue', localFilters.value)
  emit('search')
}
</script>

<template>
  <div class="bg-white/80 backdrop-blur-sm border border-gray-200 shadow-sm rounded-lg p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
      
      <!-- Search -->
      <div class="md:col-span-4">
        <label for="search" class="block text-xs font-medium text-gray-700 mb-1">Search Crop</label>
        <div class="relative rounded-md shadow-sm">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <MagnifyingGlassIcon class="h-4 w-4 text-gray-400" aria-hidden="true" />
          </div>
          <input
            type="text"
            id="search"
            v-model="localFilters.crop"
            class="focus:ring-farm-500 focus:border-farm-500 block w-full pl-9 sm:text-sm border-gray-300 rounded-md"
            placeholder="e.g. Rice, Corn..."
          />
        </div>
      </div>
      
      <!-- Price Range -->
      <div class="md:col-span-4 flex space-x-2">
        <div class="w-1/2">
          <label for="min_price" class="block text-xs font-medium text-gray-700 mb-1">Min Price</label>
          <div class="relative rounded-md shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500 sm:text-sm">₱</span>
            </div>
            <input
              type="number"
              id="min_price"
              v-model="localFilters.minPrice"
              @change="applyFilters"
              class="focus:ring-farm-500 focus:border-farm-500 block w-full pl-7 sm:text-sm border-gray-300 rounded-md"
              placeholder="0"
            />
          </div>
        </div>
        <div class="w-1/2">
          <label for="max_price" class="block text-xs font-medium text-gray-700 mb-1">Max Price</label>
          <div class="relative rounded-md shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500 sm:text-sm">₱</span>
            </div>
            <input
              type="number"
              id="max_price"
              v-model="localFilters.maxPrice"
              @change="applyFilters"
              class="focus:ring-farm-500 focus:border-farm-500 block w-full pl-7 sm:text-sm border-gray-300 rounded-md"
              placeholder="Any"
            />
          </div>
        </div>
      </div>

      <!-- Sort -->
      <div class="md:col-span-4">
        <label for="sort" class="block text-xs font-medium text-gray-700 mb-1">Sort By</label>
        <select
          id="sort"
          v-model="localFilters.sort"
          @change="applyFilters"
          class="focus:ring-farm-500 focus:border-farm-500 block w-full sm:text-sm border-gray-300 rounded-md"
        >
          <option value="newest">Newest Listed</option>
          <option value="price_asc">Price: Low to High</option>
          <option value="price_desc">Price: High to Low</option>
          <option value="harvest_soonest">Harvesting Soonest</option>
        </select>
      </div>

    </div>
  </div>
</template>
