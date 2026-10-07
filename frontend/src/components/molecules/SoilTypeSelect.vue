<script setup>
import { computed } from 'vue'
import AppSelect from '../atoms/AppSelect.vue'
const props = defineProps({
  modelValue: { type: String, required: true },
  id: { type: String, default: 'soil-type' },
  label: { type: String, default: 'Soil Type' },
  required: Boolean,
  error: { type: String, default: '' },
})
defineEmits(['update:modelValue'])
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

const options = SOIL_TYPES.map((soil) => ({ value: soil.value, label: soil.label }))
const selectedSoil = computed(() => SOIL_TYPES.find((soil) => soil.value === props.modelValue))
</script>
<template>
  <div class="space-y-3">
    <AppSelect
      :id="id"
      :label="label"
      :required="required"
      :error="error"
      :options="options"
      :model-value="modelValue"
      @update:model-value="$emit('update:modelValue', $event)"
    />
    <details v-if="selectedSoil" class="rounded-xl border border-stone-200 bg-stone-50 px-4">
      <summary class="min-h-11 cursor-pointer py-3 text-sm font-medium text-moss-700">
        About {{ selectedSoil.label }} soil
      </summary>
      <div class="pb-4 space-y-3 text-sm text-stone-600 leading-relaxed">
        <div class="flex items-center gap-3">
          <img
            :src="selectedSoil.photo"
            :alt="`${selectedSoil.label} soil`"
            class="h-12 w-12 rounded-xl object-cover"
          /><strong>{{ selectedSoil.badge }}</strong>
        </div>
        <p>{{ selectedSoil.description }}</p>
        <p><strong>Best for:</strong> {{ selectedSoil.bestFor }}</p>
      </div>
    </details>
  </div>
</template>
