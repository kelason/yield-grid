<script setup>
import { ref } from 'vue'
import SearchInput from './SearchInput.vue'
import SortSelect from './SortSelect.vue'

const props = defineProps({
  modelValue: { type: Object, required: true },
})

const emit = defineEmits(['update:modelValue', 'search'])

const localFilters = ref({ ...props.modelValue })

let searchTimeout = null
const SEARCH_DEBOUNCE_MS = 300

function onCropInput() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, SEARCH_DEBOUNCE_MS)
}

function applyFilters() {
  emit('update:modelValue', { ...localFilters.value })
  emit('search')
}
</script>

<template>
  <div class="bg-white border border-stone-200 shadow-soft rounded-2xl p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
      <div class="md:col-span-4 flex flex-col">
        <label class="block text-xs font-medium text-soil-700 mb-1">Search Crop</label>
        <SearchInput
          v-model="localFilters.crop"
          placeholder="e.g. Tomato, Rice..."
          class="flex-grow w-full"
          @update:model-value="onCropInput"
        />
      </div>

      <div class="md:col-span-4 flex space-x-2">
        <div class="w-1/2">
          <label for="demand-min-budget" class="block text-xs font-medium text-soil-700 mb-1">
            Min Budget
          </label>
          <div class="relative rounded-full shadow-soft">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <span class="text-stone-400 font-medium">₱</span>
            </div>
            <input
              type="number"
              id="demand-min-budget"
              v-model="localFilters.minBudget"
              @change="applyFilters"
              class="block w-full pl-9 pr-4 py-2.5 border border-stone-300 rounded-full leading-5 bg-stone-50 placeholder-stone-400 text-stone-900 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white sm:text-sm transition-all duration-200 shadow-soft hover:border-stone-400"
              placeholder="0"
            />
          </div>
        </div>
        <div class="w-1/2">
          <label for="demand-max-budget" class="block text-xs font-medium text-soil-700 mb-1">
            Max Budget
          </label>
          <div class="relative rounded-full shadow-soft">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <span class="text-stone-400 font-medium">₱</span>
            </div>
            <input
              type="number"
              id="demand-max-budget"
              v-model="localFilters.maxBudget"
              @change="applyFilters"
              class="block w-full pl-9 pr-4 py-2.5 border border-stone-300 rounded-full leading-5 bg-stone-50 placeholder-stone-400 text-stone-900 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white sm:text-sm transition-all duration-200 shadow-soft hover:border-stone-400"
              placeholder="Any"
            />
          </div>
        </div>
      </div>

      <div class="md:col-span-4 flex flex-col">
        <label class="block text-xs font-medium text-soil-700 mb-1">Sort By</label>
        <SortSelect
          v-model="localFilters.sort"
          :options="[
            { value: 'newest', label: 'Newest Posted' },
            { value: 'nearest', label: 'Nearest First' },
            { value: 'budget_asc', label: 'Budget: Low to High' },
            { value: 'budget_desc', label: 'Budget: High to Low' },
            { value: 'needed_soonest', label: 'Needed Soonest' },
          ]"
          @update:model-value="
            (val) => {
              localFilters.sort = val
              applyFilters()
            }
          "
        />
      </div>
    </div>
  </div>
</template>
