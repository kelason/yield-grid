<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  id: {
    type: String,
    default: 'soil-type-select',
  },
  label: {
    type: String,
    default: 'Soil Type',
  },
  required: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const selectContainerRef = ref(null)
const activeTooltip = ref(null)

const SOIL_TYPES = [
  {
    value: 'clay',
    label: 'Clay',
    photo: '/images/soils/clay.jpg',
    badge: 'Heavy & Dense',
    description:
      'Heavy, nutrient-rich soil composed of extremely fine particles. It retains moisture and nutrients exceptionally well, but drains slowly and can become dense and sticky when wet, or hard and cracked when dry.',
    bestFor: 'Rice, broccoli, cabbage, cauliflower, beans',
  },
  {
    value: 'sandy',
    label: 'Sandy',
    photo: '/images/soils/sandy.jpg',
    badge: 'Light & Gritty',
    description:
      'Light, warm, and gritty soil with large granular particles. Water drains freely and quickly through it, making it easy to cultivate and quick to warm in spring, though it requires frequent watering and organic matter.',
    bestFor: 'Carrots, potatoes, watermelon, peanuts, maize',
  },
  {
    value: 'loamy',
    label: 'Loamy',
    photo: '/images/soils/loamy.jpg',
    badge: 'Optimal & Fertile',
    description:
      'The agricultural gold standard. An ideal, highly fertile mixture of sand, silt, and clay enriched with organic humus. Provides superior aeration, optimal moisture retention, and easy root penetration.',
    bestFor: 'Wheat, corn, sugarcane, tomatoes, cotton, legumes',
  },
  {
    value: 'silt',
    label: 'Silt',
    photo: '/images/soils/silt.jpg',
    badge: 'Smooth & Moisture-Rich',
    description:
      'Fine-textured, velvety smooth alluvial sediment soil. Holds moisture better than sandy soil and possesses high natural fertility, though it can form a crust and compact under heavy rainfall or machinery.',
    bestFor: 'Cereals, grains, leafy greens, perennial vegetables',
  },
  {
    value: 'peat',
    label: 'Peat',
    photo: '/images/soils/peat.jpg',
    badge: 'Dark & Organic',
    description:
      'Dark brown to black spongy soil rich in decomposed organic matter and moss. Has extraordinarily high moisture retention and an acidic pH, naturally suppressing harmful fungal pathogens.',
    bestFor: 'Brassicas, legumes, salad crops, berries, root crops',
  },
  {
    value: 'chalky',
    label: 'Chalky',
    photo: '/images/soils/chalky.jpg',
    badge: 'Stony & Alkaline',
    description:
      'Stony, pale, alkaline soil overlying limestone or chalk bedrock. Very free-draining and quick to warm, but typically shallow and low in iron and manganese. Thrives with regular mulching.',
    bestFor: 'Beets, cabbage, sweet corn, spinach, vines',
  },
]

const selectedSoil = computed(() => {
  return SOIL_TYPES.find((s) => s.value === props.modelValue) || null
})

function toggleDropdown() {
  isOpen.value = !isOpen.value
}

function selectSoil(soil) {
  emit('update:modelValue', soil.value)
  isOpen.value = false
  activeTooltip.value = null
}

function showTooltip(value, e) {
  if (e) e.stopPropagation()
  activeTooltip.value = value
}

function hideTooltip(value) {
  if (activeTooltip.value === value) {
    activeTooltip.value = null
  }
}

function toggleTooltip(value, e) {
  if (e) e.stopPropagation()
  activeTooltip.value = activeTooltip.value === value ? null : value
}

function handleClickOutside(event) {
  if (selectContainerRef.value && !selectContainerRef.value.contains(event.target)) {
    isOpen.value = false
    activeTooltip.value = null
  }
}

function handleKeydown(event) {
  if (event.key === 'Escape') {
    isOpen.value = false
    activeTooltip.value = null
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <div ref="selectContainerRef" class="relative w-full">
    <!-- Top Field Label -->
    <div class="flex items-center justify-between mb-1.5">
      <label :for="id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
        {{ label }}
        <span v-if="required" class="text-red-500">*</span>
      </label>
      <span class="text-[11px] text-gray-400 font-medium">Select 1 type</span>
    </div>

    <!-- Select Box Trigger Button -->
    <button
      :id="id"
      type="button"
      @click="toggleDropdown"
      :class="[
        'w-full flex items-center justify-between px-3 py-2.5 bg-white border rounded-xl shadow-sm text-left transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-farm-500 focus:border-farm-500',
        isOpen
          ? 'border-farm-500 ring-2 ring-farm-100'
          : error
            ? 'border-red-300'
            : 'border-gray-200 hover:border-gray-300',
      ]"
      aria-haspopup="listbox"
      :aria-expanded="isOpen"
    >
      <!-- Selected Soil Preview -->
      <div v-if="selectedSoil" class="flex items-center gap-2.5 min-w-0 flex-1">
        <img
          :src="selectedSoil.photo"
          :alt="selectedSoil.label"
          class="w-7 h-7 rounded-lg object-cover border border-gray-200 shadow-xs flex-shrink-0"
        />
        <div class="flex items-center gap-2 min-w-0">
          <span class="text-sm font-bold text-gray-900 truncate">{{ selectedSoil.label }}</span>
          <span
            class="text-[10px] px-1.5 py-0.5 rounded-full bg-farm-50 text-farm-700 font-semibold border border-farm-200"
          >
            {{ selectedSoil.badge }}
          </span>
        </div>
      </div>

      <!-- Placeholder when none selected -->
      <div v-else class="flex items-center gap-2.5 text-gray-400 text-sm">
        <div
          class="w-7 h-7 rounded-lg bg-earth-50 border border-earth-100 flex items-center justify-center text-sm"
        >
          🌱
        </div>
        <span>Select soil type...</span>
      </div>

      <!-- Right icons -->
      <div class="flex items-center gap-1 text-gray-400 ml-2 flex-shrink-0">
        <svg
          class="w-4 h-4 transition-transform duration-200"
          :class="{ 'rotate-180 text-farm-600': isOpen }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 9l-7 7-7-7"
          />
        </svg>
      </div>
    </button>

    <!-- Error message if any -->
    <p v-if="error" class="mt-1 text-xs text-red-600 font-medium">{{ error }}</p>

    <!-- Dropdown Menu -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 -translate-y-2 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 -translate-y-2 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute left-0 right-0 z-50 mt-1.5 bg-white rounded-2xl shadow-xl border border-gray-200 overflow-visible divide-y divide-gray-100 max-h-80 overflow-y-auto"
        role="listbox"
      >
        <div
          v-for="soil in SOIL_TYPES"
          :key="soil.value"
          @click="selectSoil(soil)"
          class="group relative flex items-center justify-between px-3 py-2.5 transition-colors cursor-pointer hover:bg-farm-50/70"
          :class="{
            'bg-farm-50/90': modelValue === soil.value,
          }"
          role="option"
          :aria-selected="modelValue === soil.value"
        >
          <!-- Left: Soil Photo & Label with Tooltip Trigger -->
          <div class="flex items-center gap-3 min-w-0 flex-1">
            <!-- Soil Photo -->
            <img
              :src="soil.photo"
              :alt="soil.label"
              class="w-10 h-10 rounded-xl object-cover border border-gray-200 shadow-sm flex-shrink-0 transition-transform group-hover:scale-105"
            />

            <!-- Name and Question Mark Icon -->
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-1.5">
                <span class="text-sm font-bold text-gray-900 leading-none">{{ soil.label }}</span>

                <!-- Question Mark Button & Hover Tooltip -->
                <div class="relative inline-flex items-center">
                  <button
                    type="button"
                    @mouseenter="showTooltip(soil.value, $event)"
                    @mouseleave="hideTooltip(soil.value)"
                    @click="toggleTooltip(soil.value, $event)"
                    class="w-4 h-4 rounded-full bg-gray-100 hover:bg-farm-100 text-gray-500 hover:text-farm-700 flex items-center justify-center text-[10px] font-bold transition-colors cursor-help"
                    aria-label="Soil details"
                  >
                    ?
                  </button>

                  <!-- Floating Tooltip Box -->
                  <div
                    v-if="activeTooltip === soil.value"
                    class="absolute left-6 top-1/2 -translate-y-1/2 z-50 w-64 p-3 bg-gray-900 text-white rounded-xl shadow-2xl border border-gray-700 text-xs pointer-events-none transition-all duration-200"
                  >
                    <!-- Arrow -->
                    <div
                      class="absolute -left-1.5 top-1/2 -translate-y-1/2 w-3 h-3 bg-gray-900 rotate-45 border-l border-b border-gray-700"
                    ></div>

                    <!-- Tooltip Content -->
                    <div class="relative space-y-1.5">
                      <div class="flex items-center justify-between pb-1 border-b border-gray-800">
                        <span class="font-bold text-farm-300">{{ soil.label }} Soil</span>
                        <span class="text-[10px] text-gray-400 font-mono">{{ soil.badge }}</span>
                      </div>
                      <p class="text-gray-300 text-[11px] leading-relaxed">
                        {{ soil.description }}
                      </p>
                      <div class="pt-1 text-[10px] text-earth-200">
                        <span class="font-semibold text-white">Ideal Crops:</span>
                        {{ soil.bestFor }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Characteristic subtitle -->
              <p class="text-[11px] text-gray-500 mt-1 truncate">
                {{ soil.badge }} • {{ soil.bestFor }}
              </p>
            </div>
          </div>

          <!-- Right: Selection Radio / Checkmark -->
          <div class="ml-2 flex-shrink-0">
            <div
              v-if="modelValue === soil.value"
              class="w-5 h-5 rounded-full bg-farm-600 text-white flex items-center justify-center text-xs shadow-xs"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="3"
                  d="M5 13l4 4L19 7"
                />
              </svg>
            </div>
            <div
              v-else
              class="w-5 h-5 rounded-full border-2 border-gray-200 group-hover:border-farm-400 transition-colors"
            ></div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>
