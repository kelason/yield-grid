<script setup>
import { ref } from 'vue'
import SearchInput from './SearchInput.vue'
import SortSelect from './SortSelect.vue'
import AppInput from '../atoms/AppInput.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { CATALOG_LIMITS, catalogFilterError } from '@/constants/catalog'

const props = defineProps({
  modelValue: { type: Object, required: true },
})

const filterError = ref('')

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
  filterError.value = catalogFilterError(localFilters.value, 'minBudget', 'maxBudget')
  if (filterError.value) return
  emit('update:modelValue', { ...localFilters.value })
  emit('search')
}
</script>

<template>
  <div class="bg-white border border-stone-200 shadow-soft rounded-2xl p-4 mb-6">
    <AppAlert v-if="filterError" type="error" class="mb-4">{{ filterError }}</AppAlert>
    <div class="grid grid-cols-1 md:grid-cols-12 items-end gap-4">
      <div class="md:col-span-4 flex flex-col">
        <label for="demand-crop" class="block text-sm font-medium text-soil-700 mb-1"
          >Search Crop</label
        >
        <SearchInput
          id="demand-crop"
          label="Search Crop"
          :maxlength="CATALOG_LIMITS.CROP_MAX_LENGTH"
          v-model="localFilters.crop"
          placeholder="e.g. Tomato, Rice..."
          class="flex-grow w-full"
          @update:model-value="onCropInput"
        />
      </div>

      <div class="md:col-span-4 flex items-end space-x-2">
        <div class="w-1/2">
          <label for="demand-min-budget" class="block text-xs font-medium text-soil-700 mb-1">
            Min Budget
          </label>
          <div class="relative rounded-full shadow-soft">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <span class="text-stone-500 font-medium">₱</span>
            </div>
            <AppInput
              type="number"
              :min="CATALOG_LIMITS.PRICE_MIN"
              :max="CATALOG_LIMITS.PRICE_MAX"
              :maxlength="CATALOG_LIMITS.PRICE_MAX_LENGTH"
              step="0.01"
              id="demand-min-budget"
              v-model="localFilters.minBudget"
              @change="applyFilters"
              class="block w-full pl-9 pr-4 py-2.5 border border-stone-300 rounded-full bg-stone-50 placeholder-stone-400 text-stone-900 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white transition-all duration-200 shadow-soft hover:border-stone-400"
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
              <span class="text-stone-500 font-medium">₱</span>
            </div>
            <AppInput
              type="number"
              :min="CATALOG_LIMITS.PRICE_MIN"
              :max="CATALOG_LIMITS.PRICE_MAX"
              :maxlength="CATALOG_LIMITS.PRICE_MAX_LENGTH"
              step="0.01"
              id="demand-max-budget"
              v-model="localFilters.maxBudget"
              @change="applyFilters"
              class="block w-full pl-9 pr-4 py-2.5 border border-stone-300 rounded-full bg-stone-50 placeholder-stone-400 text-stone-900 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white transition-all duration-200 shadow-soft hover:border-stone-400"
              placeholder="Any"
            />
          </div>
        </div>
      </div>

      <div class="md:col-span-4 flex flex-col">
        <label for="demand-sort" class="block text-sm font-medium text-soil-700 mb-1"
          >Sort By</label
        >
        <SortSelect
          id="demand-sort"
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
