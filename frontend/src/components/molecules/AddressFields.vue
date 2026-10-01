<script setup>
import { ref, watch, computed } from 'vue'
import AppSelect from '../atoms/AppSelect.vue'
import FormField from './FormField.vue'
import LeafletPinPicker from '../organisms/LeafletPinPicker.vue'
import { useGeo } from '@/composables/useGeo'

const ADDRESS_LABEL_MAX_LENGTH = 50
const ADDRESS_STREET_MAX_LENGTH = 255

const props = defineProps({
  modelValue: { type: Object, required: true },
  idPrefix: { type: String, default: 'addr' },
  showPinPicker: { type: Boolean, default: true },
  showLabel: { type: Boolean, default: true },
  errors: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue', 'pin-validation'])

const geo = useGeo()
const regions = ref([])
const provinces = ref([])
const cities = ref([])
const barangays = ref([])
const hasProvinces = ref(true)
const mapCenter = ref(null)
const maxRadiusKm = ref(null)
const loadingCenter = ref(false)

const selectedNames = computed(() => {
  const find = (list, code) => list.find((item) => item.code === code)?.name || null
  return {
    region: find(regions.value, props.modelValue.region_code),
    province: find(provinces.value, props.modelValue.province_code),
    city: find(cities.value, props.modelValue.city_municipality_code),
    barangay: find(barangays.value, props.modelValue.barangay_code),
  }
})

function patch(fields) {
  emit('update:modelValue', { ...props.modelValue, ...fields })
}

async function handleRegionSelect(code) {
  patch({
    region_code: code,
    province_code: null,
    city_municipality_code: null,
    barangay_code: null,
  })
  provinces.value = []
  cities.value = []
  barangays.value = []
  hasProvinces.value = true
  if (!code) return
  provinces.value = await geo.fetchProvinces(code)
  hasProvinces.value = provinces.value.length > 0
  if (!hasProvinces.value) {
    cities.value = await geo.fetchCities({ regionCode: code })
  }
}

async function handleProvinceSelect(code) {
  patch({ province_code: code, city_municipality_code: null, barangay_code: null })
  cities.value = []
  barangays.value = []
  if (!code) return
  cities.value = await geo.fetchCities({ provinceCode: code })
}

async function handleCitySelect(code) {
  patch({ city_municipality_code: code, barangay_code: null })
  barangays.value = []
  if (!code) return
  barangays.value = await geo.fetchBarangays(code)
}

function toOptions(list) {
  return list.map((item) => ({ value: item.code, label: item.name }))
}

async function loadRegions() {
  regions.value = await geo.fetchRegions()
}

async function refreshMapCenter() {
  mapCenter.value = null
  maxRadiusKm.value = null
  if (!props.showPinPicker) return
  const names = selectedNames.value
  if (!names.barangay || !names.city) return
  loadingCenter.value = true
  try {
    const center = await geo.fetchCenter({
      barangay: names.barangay,
      cityMunicipality: names.city,
      province: names.province,
    })
    mapCenter.value = { lat: center.lat, lng: center.lng }
    maxRadiusKm.value = center.max_radius_km
  } catch {
    mapCenter.value = null
  } finally {
    loadingCenter.value = false
  }
}

watch(() => props.modelValue.barangay_code, refreshMapCenter)

async function initCascade() {
  if (!props.modelValue.region_code) return
  provinces.value = await geo.fetchProvinces(props.modelValue.region_code)
  hasProvinces.value = provinces.value.length > 0
  if (!hasProvinces.value) {
    cities.value = await geo.fetchCities({ regionCode: props.modelValue.region_code })
  } else if (props.modelValue.province_code) {
    cities.value = await geo.fetchCities({ provinceCode: props.modelValue.province_code })
  }
  if (props.modelValue.city_municipality_code) {
    barangays.value = await geo.fetchBarangays(props.modelValue.city_municipality_code)
  }
  refreshMapCenter()
}

loadRegions().then(initCascade)
</script>

<template>
  <div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <FormField
        v-if="showLabel"
        :id="`${idPrefix}-label`"
        label="Label"
        placeholder="e.g. Home, Farm gate"
        :maxlength="ADDRESS_LABEL_MAX_LENGTH"
        :model-value="modelValue.label || ''"
        :error="errors.label || ''"
        @update:model-value="patch({ label: $event })"
      />
      <FormField
        :id="`${idPrefix}-street`"
        label="Street / House No."
        placeholder="e.g. 123 Sampaguita St."
        :maxlength="ADDRESS_STREET_MAX_LENGTH"
        :model-value="modelValue.street || ''"
        :error="errors.street || ''"
        :class="{ 'sm:col-span-2': !showLabel }"
        @update:model-value="patch({ street: $event })"
      />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <AppSelect
        :id="`${idPrefix}-region`"
        label="Region"
        :required="true"
        :options="toOptions(regions)"
        :model-value="modelValue.region_code || ''"
        :error="errors.region_code || ''"
        @update:model-value="handleRegionSelect"
      />
      <AppSelect
        v-if="hasProvinces"
        :id="`${idPrefix}-province`"
        label="Province"
        :required="true"
        :disabled="!modelValue.region_code"
        :options="toOptions(provinces)"
        :model-value="modelValue.province_code || ''"
        :error="errors.province_code || ''"
        @update:model-value="handleProvinceSelect"
      />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <AppSelect
        :id="`${idPrefix}-city`"
        label="City / Municipality"
        :required="true"
        :disabled="hasProvinces ? !modelValue.province_code : !modelValue.region_code"
        :options="toOptions(cities)"
        :model-value="modelValue.city_municipality_code || ''"
        :error="errors.city_municipality_code || ''"
        @update:model-value="handleCitySelect"
      />
      <AppSelect
        :id="`${idPrefix}-barangay`"
        label="Barangay"
        :required="true"
        :disabled="!modelValue.city_municipality_code"
        :options="toOptions(barangays)"
        :model-value="modelValue.barangay_code || ''"
        :error="errors.barangay_code || ''"
        @update:model-value="patch({ barangay_code: $event })"
      />
    </div>

    <div v-if="showPinPicker && modelValue.barangay_code">
      <p class="text-sm font-medium text-soil-700 mb-2">
        Pin exact location <span class="text-stone-400 font-normal">(optional)</span>
      </p>
      <p v-if="loadingCenter" class="text-sm text-stone-500 animate-pulse">
        Locating your barangay on the map…
      </p>
      <LeafletPinPicker
        v-else
        :model-value="
          modelValue.latitude != null && modelValue.longitude != null
            ? { lat: modelValue.latitude, lng: modelValue.longitude }
            : null
        "
        :center="mapCenter"
        :max-radius-km="maxRadiusKm"
        @update:model-value="patch({ latitude: $event.lat, longitude: $event.lng })"
        @validation="$emit('pin-validation', $event)"
      />
    </div>
  </div>
</template>
