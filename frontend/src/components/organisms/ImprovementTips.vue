<script setup>
import { computed } from 'vue'
import { LightBulbIcon } from '@heroicons/vue/24/outline'
import { CREDIT_DIMENSIONS } from '@/constants/creditScoring'

const props = defineProps({
  tips: {
    type: Array,
    default: () => [],
  },
})

const GENERAL_GROUP_KEY = 'general'

const normalizedTips = computed(() =>
  (props.tips ?? [])
    .filter((tip) => tip !== null && tip !== undefined)
    .map((tip) => (typeof tip === 'string' ? { dimension: GENERAL_GROUP_KEY, message: tip } : tip)),
)

const groupedTips = computed(() => {
  const groups = []

  for (const dimension of CREDIT_DIMENSIONS) {
    const messages = normalizedTips.value
      .filter((tip) => tip.dimension === dimension.key)
      .map((tip) => tip.message)

    if (messages.length > 0) {
      groups.push({
        key: dimension.key,
        label: dimension.label,
        weightPct: dimension.weightPct,
        messages,
      })
    }
  }

  appendGeneralTips(groups)

  return groups
})

function appendGeneralTips(groups) {
  const generalMessages = normalizedTips.value
    .filter((tip) => !CREDIT_DIMENSIONS.some((dimension) => dimension.key === tip.dimension))
    .map((tip) => tip.message)

  if (generalMessages.length > 0) {
    groups.push({
      key: GENERAL_GROUP_KEY,
      label: 'General',
      weightPct: null,
      messages: generalMessages,
    })
  }
}

const summary = computed(() => {
  const actionWord = normalizedTips.value.length === 1 ? 'action' : 'actions'
  const areaWord = groupedTips.value.length === 1 ? 'area' : 'areas'
  return `${normalizedTips.value.length} suggested ${actionWord} across ${groupedTips.value.length} ${areaWord}`
})
</script>

<template>
  <div class="bg-dew-50 rounded-2xl border border-dew-200 shadow-soft p-6">
    <h3 class="font-serif text-2xl font-bold text-stone-900">How to Improve Your Score</h3>
    <div v-if="groupedTips.length > 0">
      <p class="mt-1 text-sm font-normal text-stone-600">{{ summary }}</p>
      <div v-for="group in groupedTips" :key="group.key" class="mt-4">
        <div class="flex items-center gap-2">
          <p class="text-sm font-semibold text-stone-900">{{ group.label }}</p>
          <span
            v-if="group.weightPct !== null"
            class="rounded-full bg-dew-100 text-dew-700 text-xs font-medium px-2.5 py-0.5 tabular-nums whitespace-nowrap"
          >
            {{ group.weightPct }}%
          </span>
        </div>
        <ul class="mt-2 space-y-2">
          <li
            v-for="(message, index) in group.messages"
            :key="index"
            class="flex items-start gap-2.5"
          >
            <LightBulbIcon class="h-5 w-5 shrink-0 text-harvest-500" aria-hidden="true" />
            <span class="text-base text-stone-600 font-normal leading-relaxed">{{ message }}</span>
          </li>
        </ul>
      </div>
    </div>
    <p v-else class="mt-4 text-base text-stone-600 font-normal leading-relaxed">
      Outstanding — your profile is in excellent shape. Keep completing contracts and deliveries to
      stay there.
    </p>
  </div>
</template>
