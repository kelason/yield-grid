<script setup>
import { ref, watch } from 'vue'
import SearchInput from './SearchInput.vue'
import SortSelect from './SortSelect.vue'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:modelValue', 'search'])

const localFilters = ref({
  ...props.modelValue,
  availability: props.modelValue.availability || 'all',
})

// Debounce search
let searchTimeout = null
watch(
  () => localFilters.value.crop,
  (newVal, oldVal) => {
    if (newVal !== oldVal) {
      if (searchTimeout) clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        emit('update:modelValue', localFilters.value)
        emit('search')
      }, 300)
    }
  },
)

function applyFilters() {
  emit('update:modelValue', localFilters.value)
  emit('search')
}

function setAvailability(availability) {
  localFilters.value.availability = availability
  applyFilters()
}
</script>

<template>
  <div class="bg-white/80 backdrop-blur-sm border border-gray-200 shadow-sm rounded-lg p-4 mb-6">
    <!-- Availability Tabs -->
    <div
      class="flex space-x-1 bg-stone-100/50 p-1 rounded-lg mb-5 w-fit border border-stone-200/50"
    >
      <button
        type="button"
        @click="setAvailability('all')"
        :class="[
          localFilters.availability === 'all'
            ? 'bg-white text-stone-900 shadow-sm ring-1 ring-stone-900/5'
            : 'text-stone-600 hover:text-stone-900 hover:bg-stone-50',
          'px-4 py-1.5 text-sm font-medium rounded-md transition-all',
        ]"
      >
        All Markets
      </button>
      <button
        type="button"
        @click="setAvailability('available')"
        :class="[
          localFilters.availability === 'available'
            ? 'bg-white text-moss-700 shadow-sm ring-1 ring-stone-900/5'
            : 'text-stone-600 hover:text-stone-900 hover:bg-stone-50',
          'px-4 py-1.5 text-sm font-medium rounded-md transition-all flex items-center gap-1.5',
        ]"
      >
        <span class="w-2 h-2 rounded-full bg-moss-500"></span>
        Harvest Available
      </button>
      <button
        type="button"
        @click="setAvailability('incoming')"
        :class="[
          localFilters.availability === 'incoming'
            ? 'bg-white text-harvest-700 shadow-sm ring-1 ring-stone-900/5'
            : 'text-stone-600 hover:text-stone-900 hover:bg-stone-50',
          'px-4 py-1.5 text-sm font-medium rounded-md transition-all flex items-center gap-1.5',
        ]"
      >
        <span class="w-2 h-2 rounded-full bg-harvest-500"></span>
        Incoming Harvest
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
      <!-- Search -->
      <div class="md:col-span-4 flex flex-col">
        <label class="block text-xs font-medium text-gray-700 mb-1">Search Crop</label>
        <SearchInput
          v-model="localFilters.crop"
          placeholder="e.g. Rice, Corn..."
          class="flex-grow w-full"
        />
      </div>

      <!-- Price Range -->
      <div class="md:col-span-4 flex space-x-2">
        <div class="w-1/2">
          <label for="min_price" class="block text-xs font-medium text-gray-700 mb-1"
            >Min Price</label
          >
          <div class="relative rounded-full shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <span class="text-stone-400 font-medium">₱</span>
            </div>
            <input
              type="number"
              id="min_price"
              v-model="localFilters.minPrice"
              @change="applyFilters"
              class="block w-full pl-9 pr-4 py-2.5 border border-stone-300 rounded-full leading-5 bg-stone-50 placeholder-stone-400 text-stone-900 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white sm:text-sm transition-all duration-200 shadow-sm hover:border-stone-400"
              placeholder="0"
            />
          </div>
        </div>
        <div class="w-1/2">
          <label for="max_price" class="block text-xs font-medium text-gray-700 mb-1"
            >Max Price</label
          >
          <div class="relative rounded-full shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <span class="text-stone-400 font-medium">₱</span>
            </div>
            <input
              type="number"
              id="max_price"
              v-model="localFilters.maxPrice"
              @change="applyFilters"
              class="block w-full pl-9 pr-4 py-2.5 border border-stone-300 rounded-full leading-5 bg-stone-50 placeholder-stone-400 text-stone-900 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white sm:text-sm transition-all duration-200 shadow-sm hover:border-stone-400"
              placeholder="Any"
            />
          </div>
        </div>
      </div>

      <!-- Sort -->
      <div class="md:col-span-4 flex flex-col">
        <label class="block text-xs font-medium text-gray-700 mb-1">Sort By</label>
        <SortSelect
          v-model="localFilters.sort"
          @update:modelValue="
            (val) => {
              localFilters.sort = val
              applyFilters()
            }
          "
          :options="[
            { value: 'newest', label: 'Newest Listed' },
            { value: 'harvest_available', label: 'Harvest Available First' },
            { value: 'incoming_harvest', label: 'Incoming Harvest First' },
            { value: 'price_asc', label: 'Price: Low to High' },
            { value: 'price_desc', label: 'Price: High to Low' },
            { value: 'harvest_soonest', label: 'Harvesting Soonest' },
          ]"
        />
      </div>
    </div>
  </div>
</template>
