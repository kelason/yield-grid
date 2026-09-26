<script setup>
import { ref, onUnmounted } from 'vue'

defineProps({
  modelValue: {
    type: String,
    required: true,
  },
  id: {
    type: String,
    default: 'soil-type',
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

const activeTooltip = ref(null)

const SOIL_TYPES = [
  {
    value: 'clay',
    label: 'Clay',
    photo: '/images/soils/clay.jpg',
    badge: 'Heavy & Dense',
    description:
      'Heavy, nutrient-rich soil composed of extremely fine particles. It retains moisture and nutrients exceptionally well, but drains slowly and can become dense and sticky when wet, or hard and cracked when dry.',
    bestFor: 'Palay (Rice), Kangkong, Gabi, Sitaw (String Beans)',
  },
  {
    value: 'sandy',
    label: 'Sandy',
    photo: '/images/soils/sandy.jpg',
    badge: 'Light & Gritty',
    description:
      'Light, warm, and gritty soil with large granular particles. Water drains freely and quickly through it, making it easy to cultivate and quick to warm in spring, though it requires frequent watering and organic matter.',
    bestFor: 'Kamote (Sweet Potato), Peanuts, Cassava, Watermelon, Coconut',
  },
  {
    value: 'loamy',
    label: 'Loamy',
    photo: '/images/soils/loamy.jpg',
    badge: 'Optimal & Fertile',
    description:
      'The agricultural gold standard. An ideal, highly fertile mixture of sand, silt, and clay enriched with organic humus. Provides superior aeration, optimal moisture retention, and easy root penetration.',
    bestFor: 'Mais (Corn), Sugarcane, Talong (Eggplant), Kamatis (Tomatoes), Bananas',
  },
  {
    value: 'silt',
    label: 'Silt',
    photo: '/images/soils/silt.jpg',
    badge: 'Smooth & Moisture-Rich',
    description:
      'Fine-textured, velvety smooth alluvial sediment soil. Holds moisture better than sandy soil and possesses high natural fertility, though it can form a crust and compact under heavy rainfall or machinery.',
    bestFor: 'Pechay, Ampalaya (Bitter Gourd), Okra, Kalabasa (Squash)',
  },
  {
    value: 'peat',
    label: 'Peat',
    photo: '/images/soils/peat.jpg',
    badge: 'Dark & Organic',
    description:
      'Dark brown to black spongy soil rich in decomposed organic matter and moss. Has extraordinarily high moisture retention and an acidic pH, naturally suppressing harmful fungal pathogens.',
    bestFor: 'Repolyo (Cabbage), Lettuce, Carrots, Strawberries',
  },
  {
    value: 'chalky',
    label: 'Chalky',
    photo: '/images/soils/chalky.jpg',
    badge: 'Stony & Alkaline',
    description:
      'Stony, pale, alkaline soil overlying limestone or chalk bedrock. Very free-draining and quick to warm, but typically shallow and low in iron and manganese. Thrives with regular mulching.',
    bestFor: 'Coconut, Calamansi, Mais (Corn), Papaya, Cassava',
  },
]

function selectSoil(soil) {
  emit('update:modelValue', soil.value)
  activeTooltip.value = null
}

let hideTimeout = null

function showTooltip(value, e) {
  if (e) e.stopPropagation()
  if (hideTimeout) {
    clearTimeout(hideTimeout)
    hideTimeout = null
  }
  activeTooltip.value = value
}

function hideTooltip(value, immediate = false) {
  if (immediate) {
    if (hideTimeout) clearTimeout(hideTimeout)
    if (activeTooltip.value === value) {
      activeTooltip.value = null
    }
    return
  }
  if (hideTimeout) clearTimeout(hideTimeout)
  hideTimeout = setTimeout(() => {
    if (activeTooltip.value === value) {
      activeTooltip.value = null
    }
  }, 150)
}

function toggleTooltip(value, e) {
  if (e) e.stopPropagation()
  if (hideTimeout) clearTimeout(hideTimeout)
  activeTooltip.value = activeTooltip.value === value ? null : value
}

onUnmounted(() => {
  if (hideTimeout) clearTimeout(hideTimeout)
})
</script>

<template>
  <div class="relative w-full">
    <!-- Top Field Label -->
    <div class="flex items-center justify-between mb-2">
      <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
        {{ label }}
        <span v-if="required" class="text-red-500">*</span>
      </label>
      <span class="text-[11px] text-gray-400 font-medium">Select 1 type</span>
    </div>

    <!-- Error message if any -->
    <p v-if="error" class="mb-2 text-xs text-red-600 font-medium">{{ error }}</p>

    <!-- Radio Grid -->
    <div class="grid grid-cols-1 gap-2.5" role="radiogroup" :aria-label="label">
      <div
        v-for="(soil, index) in SOIL_TYPES"
        :key="soil.value"
        @click="selectSoil(soil)"
        class="group relative flex items-center justify-between px-3 py-3 rounded-xl border transition-all duration-200 cursor-pointer hover:z-40"
        :class="[
          modelValue === soil.value
            ? 'border-green-500 bg-green-50/90 ring-1 ring-green-500 shadow-sm'
            : 'border-gray-200 bg-white hover:border-green-300 hover:bg-green-50/40 shadow-xs',
          activeTooltip === soil.value ? 'z-50' : 'z-10',
        ]"
        role="radio"
        :aria-checked="modelValue === soil.value"
        tabindex="0"
        @keydown.enter="selectSoil(soil)"
        @keydown.space.prevent="selectSoil(soil)"
      >
        <!-- Left: Soil Photo & Label with Tooltip Trigger -->
        <div class="flex items-center gap-3 min-w-0 flex-1">
          <!-- Soil Photo with smooth zoom animation on mouse hover -->
          <div class="relative flex-shrink-0 group/img">
            <img
              :src="soil.photo"
              :alt="soil.label"
              class="w-10 h-10 rounded-xl object-cover border border-gray-200 shadow-sm flex-shrink-0 transition-all duration-300 ease-out cursor-zoom-in group-hover/img:scale-[2.5] group-hover/img:z-40 group-hover/img:shadow-2xl group-hover/img:border-gray-900 group-hover/img:rounded-xl relative"
            />
          </div>

          <!-- Name and Question Mark Icon -->
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <span class="text-sm font-bold text-gray-900 leading-none">{{ soil.label }}</span>

              <!-- Question Mark Button -->
              <div class="relative inline-flex items-center">
                <button
                  type="button"
                  @mouseenter="showTooltip(soil.value, $event)"
                  @mouseleave="hideTooltip(soil.value)"
                  @click.stop="toggleTooltip(soil.value, $event)"
                  class="w-5 h-5 rounded-full bg-gray-100 hover:bg-green-100 text-gray-500 hover:text-green-700 flex items-center justify-center text-[11px] font-bold transition-colors cursor-help"
                  aria-label="Soil details"
                >
                  ?
                </button>
              </div>
            </div>

            <!-- Characteristic subtitle -->
            <p class="text-xs text-gray-500 mt-1 truncate">
              {{ soil.badge }}
            </p>
          </div>
        </div>

        <!-- Right: Selection Radio / Checkmark -->
        <div class="ml-2 flex-shrink-0">
          <div
            v-if="modelValue === soil.value"
            class="w-5 h-5 rounded-full bg-green-600 text-white flex items-center justify-center text-xs shadow-xs"
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
            class="w-5 h-5 rounded-full border-2 border-gray-300 group-hover:border-green-400 transition-colors"
          ></div>
        </div>

        <!-- Overlapping Description Card (overlaps directly over select option to see whole description) -->
        <Transition
          enter-active-class="transition-all duration-200 ease-out z-50"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition-all duration-150 ease-in z-50"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-if="activeTooltip === soil.value"
            @mouseenter="showTooltip(soil.value)"
            @mouseleave="hideTooltip(soil.value)"
            class="absolute -inset-x-1 z-50 p-3 bg-gray-900/95 text-white rounded-xl shadow-2xl border border-gray-700/80 backdrop-blur-xl"
            :class="index >= Math.floor(SOIL_TYPES.length / 2) ? 'bottom-0' : 'top-0'"
            @click.stop="selectSoil(soil)"
          >
            <div class="relative space-y-2">
              <div class="flex items-center justify-between pb-2 border-b border-gray-800">
                <div class="flex items-center gap-2.5">
                  <span class="font-bold text-green-300 text-sm">{{ soil.label }} Soil</span>
                  <span
                    class="text-[11px] px-2 py-0.5 rounded-full bg-green-900/70 text-green-300 font-semibold border border-green-700/60"
                  >
                    {{ soil.badge }}
                  </span>
                </div>
                <button
                  type="button"
                  @click.stop="hideTooltip(soil.value, true)"
                  class="text-gray-400 hover:text-white text-sm px-1.5 py-1 leading-none transition-colors"
                  aria-label="Close details"
                >
                  ✕
                </button>
              </div>
              <p class="text-gray-200 text-xs leading-relaxed">
                {{ soil.description }}
              </p>
              <div class="pt-2 border-t border-gray-800/80 flex items-start gap-1.5 text-xs">
                <span class="font-bold text-emerald-400 flex-shrink-0">🌱 Best for:</span>
                <span class="text-gray-300">{{ soil.bestFor }}</span>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </div>
</template>
