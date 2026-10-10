<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import SearchInput from './SearchInput.vue'
import SortSelect from './SortSelect.vue'
import AppInput from '../atoms/AppInput.vue'
import AppAlert from '../atoms/AppAlert.vue'
import { CATALOG_LIMITS, catalogFilterError } from '@/constants/catalog'

const SEARCH_DEBOUNCE_MS = 300

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
})

const { t } = useI18n()
const filterError = ref('')

const emit = defineEmits(['update:modelValue', 'search'])

const sortOptions = computed(() => [
  { value: 'newest', label: t('farmer.contract_filter.sort_newest') },
  { value: 'nearest', label: t('farmer.demand_filter.sort_nearest') },
  { value: 'harvest_available', label: t('farmer.contract_filter.sort_harvest_first') },
  { value: 'incoming_harvest', label: t('farmer.contract_filter.sort_incoming_first') },
  { value: 'price_asc', label: t('farmer.contract_filter.sort_price_asc') },
  { value: 'price_desc', label: t('farmer.contract_filter.sort_price_desc') },
  { value: 'harvest_soonest', label: t('farmer.contract_filter.sort_harvest_soonest') },
])

const localFilters = ref({
  ...props.modelValue,
  availability: props.modelValue.availability || 'all',
})

// Debounce search so crop and price filter live after typing
let searchTimeout = null
watch(
  () => [localFilters.value.crop, localFilters.value.minPrice, localFilters.value.maxPrice],
  (newVal, oldVal) => {
    if (JSON.stringify(newVal) !== JSON.stringify(oldVal)) {
      if (searchTimeout) clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        applyFilters()
      }, SEARCH_DEBOUNCE_MS)
    }
  },
)

function applyFilters() {
  if (searchTimeout) clearTimeout(searchTimeout)
  filterError.value = catalogFilterError(localFilters.value, 'minPrice', 'maxPrice')
  if (filterError.value) return
  emit('update:modelValue', localFilters.value)
  emit('search')
}

function setAvailability(availability) {
  localFilters.value.availability = availability
  applyFilters()
}
</script>

<template>
  <div class="bg-white border border-stone-200 shadow-soft rounded-2xl p-4 mb-6">
    <AppAlert v-if="filterError" type="error" class="mb-4">{{ filterError }}</AppAlert>
    <!-- Availability Tabs -->
    <div
      class="flex flex-wrap gap-1 bg-stone-100/50 p-1 rounded-xl mb-5 w-fit border border-stone-200/50"
    >
      <button
        type="button"
        @click="setAvailability('all')"
        :class="[
          localFilters.availability === 'all'
            ? 'bg-white text-stone-900 shadow-soft ring-1 ring-stone-900/5'
            : 'text-stone-600 hover:text-stone-900 hover:bg-stone-50',
          'min-h-11 px-4 py-1.5 text-sm font-medium rounded-xl transition-all',
        ]"
      >
        {{ t('farmer.contract_filter.all_markets') }}
      </button>
      <button
        type="button"
        @click="setAvailability('available')"
        :class="[
          localFilters.availability === 'available'
            ? 'bg-white text-moss-700 shadow-soft ring-1 ring-stone-900/5'
            : 'text-stone-600 hover:text-stone-900 hover:bg-stone-50',
          'min-h-11 px-4 py-1.5 text-sm font-medium rounded-xl transition-all flex items-center gap-1.5',
        ]"
      >
        <span class="w-2 h-2 rounded-full bg-moss-500"></span>
        {{ t('farmer.contract_card.harvest_available') }}
      </button>
      <button
        type="button"
        @click="setAvailability('incoming')"
        :class="[
          localFilters.availability === 'incoming'
            ? 'bg-white text-harvest-700 shadow-soft ring-1 ring-stone-900/5'
            : 'text-stone-600 hover:text-stone-900 hover:bg-stone-50',
          'min-h-11 px-4 py-1.5 text-sm font-medium rounded-xl transition-all flex items-center gap-1.5',
        ]"
      >
        <span class="w-2 h-2 rounded-full bg-harvest-500"></span>
        {{ t('farmer.contract_card.incoming_harvest') }}
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 items-end gap-4">
      <!-- Search -->
      <div class="md:col-span-4 flex flex-col">
        <label for="contract-crop" class="block text-sm font-medium text-soil-700 mb-1">{{
          t('farmer.demand_filter.crop')
        }}</label>
        <SearchInput
          id="contract-crop"
          :label="t('farmer.demand_filter.crop')"
          :maxlength="CATALOG_LIMITS.CROP_MAX_LENGTH"
          v-model="localFilters.crop"
          :placeholder="t('farmer.contract_filter.crop_placeholder')"
          class="flex-grow w-full"
        />
      </div>

      <!-- Price Range -->
      <div class="md:col-span-4 flex items-end space-x-2">
        <div class="w-1/2">
          <label for="min_price" class="block text-xs font-medium text-soil-700 mb-1">{{
            t('farmer.contract_filter.min_price')
          }}</label>
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
              id="min_price"
              v-model="localFilters.minPrice"
              @keyup.enter="applyFilters"
              class="block w-full pl-9 pr-4 py-2.5 border border-stone-300 rounded-full bg-stone-50 placeholder-stone-400 text-stone-900 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white transition-all duration-200 shadow-soft hover:border-stone-400"
              placeholder="0"
            />
          </div>
        </div>
        <div class="w-1/2">
          <label for="max_price" class="block text-xs font-medium text-soil-700 mb-1">{{
            t('farmer.contract_filter.max_price')
          }}</label>
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
              id="max_price"
              v-model="localFilters.maxPrice"
              @keyup.enter="applyFilters"
              class="block w-full pl-9 pr-4 py-2.5 border border-stone-300 rounded-full bg-stone-50 placeholder-stone-400 text-stone-900 focus:outline-none focus:ring-2 focus:ring-moss-500 focus:border-moss-500 focus:bg-white transition-all duration-200 shadow-soft hover:border-stone-400"
              :placeholder="t('farmer.demand_filter.any')"
            />
          </div>
        </div>
      </div>

      <!-- Sort -->
      <div class="md:col-span-4 flex flex-col">
        <label for="contract-sort" class="block text-sm font-medium text-soil-700 mb-1">{{
          t('farmer.demand_filter.sort_by')
        }}</label>
        <SortSelect
          id="contract-sort"
          v-model="localFilters.sort"
          @update:modelValue="
            (val) => {
              localFilters.sort = val
              applyFilters()
            }
          "
          :options="sortOptions"
        />
      </div>
    </div>
  </div>
</template>
