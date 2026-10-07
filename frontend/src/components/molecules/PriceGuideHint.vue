<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import {
  InformationCircleIcon,
  ExclamationTriangleIcon,
  BuildingStorefrontIcon,
  ShieldCheckIcon,
  SparklesIcon,
} from '@heroicons/vue/24/outline'
import AppSpinner from '@/components/atoms/AppSpinner.vue'
import { usePriceGuide } from '@/composables/usePriceGuide'
import {
  PRICE_CHECK_STAGES,
  PRICE_GUIDE,
  PRICE_TIER,
  PRICE_TIER_LABELS,
  PRICE_SOURCE_LABELS,
} from '@/constants/prices'

const props = defineProps({
  cropName: {
    type: String,
    default: '',
  },
  regionCode: {
    type: String,
    default: null,
  },
  currentPrice: {
    type: Number,
    default: null,
  },
})

const TIER_ORDER = [PRICE_TIER.FARMGATE, PRICE_TIER.WHOLESALE, PRICE_TIER.RETAIL]
const PERCENT_MULTIPLIER = 100
const STAGE_YIELDGRID = 'yieldgrid'
const STAGE_DA = 'da'
const STAGE_AI = 'ai'
const STAGE_ICONS = {
  [STAGE_YIELDGRID]: BuildingStorefrontIcon,
  [STAGE_DA]: ShieldCheckIcon,
  [STAGE_AI]: SparklesIcon,
}
const STAGE_ICON_CLASS = {
  [STAGE_YIELDGRID]: 'text-harvest-600',
  [STAGE_DA]: 'text-moss-600',
  [STAGE_AI]: 'text-dew-600',
}
const TOTAL_STAGES = PRICE_CHECK_STAGES.length

const { fetchComparison } = usePriceGuide()
const meta = ref(null)
const sections = ref(emptySections())
const sequenceDone = ref(false)
const aiPollCount = ref(0)
let debounceTimer = null
let pollTimer = null
let requestSeq = 0

function emptySections() {
  return { [STAGE_YIELDGRID]: null, [STAGE_DA]: null, [STAGE_AI]: null }
}

function formatPeso(value) {
  return `₱${Number(value).toLocaleString('en-PH', { maximumFractionDigits: 2 })}`
}

async function runSequence(crop, seq) {
  sections.value = emptySections()
  sequenceDone.value = false
  meta.value = null

  for (const stage of PRICE_CHECK_STAGES) {
    if (seq !== requestSeq) {
      return
    }
    sections.value[stage.key] = { status: 'loading' }
    const result = await fetchComparison(crop, props.regionCode, [stage.key])
    if (seq !== requestSeq) {
      return
    }
    captureMeta(result)
    sections.value[stage.key] = {
      status: 'ready',
      ...(result?.sections?.[stage.key] ?? { available: false }),
    }
  }

  if (seq !== requestSeq) return

  if (shouldPollAi()) {
    pollAi(crop, seq)
  } else {
    sequenceDone.value = true
  }
}

function captureMeta(result) {
  if (meta.value || !result?.crop_display_name) {
    return
  }
  meta.value = {
    displayName: result.crop_display_name,
    correctedFrom: result.corrected_from ?? null,
  }
}

function shouldPollAi() {
  const ai = sections.value[STAGE_AI]
  return !!ai?.pending && aiPollCount.value < PRICE_GUIDE.PENDING_MAX_ATTEMPTS
}

function pollAi(crop, seq) {
  if (seq !== requestSeq) {
    return
  }
  if (!shouldPollAi()) {
    giveUpAi()
    sequenceDone.value = true
    return
  }

  aiPollCount.value += 1
  pollTimer = setTimeout(async () => {
    if (seq !== requestSeq || (props.cropName ?? '').trim() !== crop) {
      return
    }
    const result = await fetchComparison(crop, props.regionCode, [STAGE_AI], { force: true })
    if (seq !== requestSeq) {
      return
    }
    sections.value[STAGE_AI] = {
      status: 'ready',
      ...(result?.sections?.[STAGE_AI] ?? { available: false }),
    }
    pollAi(crop, seq)
  }, PRICE_GUIDE.RETRY_DELAY_MS)
}

function giveUpAi() {
  const ai = sections.value[STAGE_AI]
  if (ai) {
    ai.pending = false
  }
}

function scheduleLoad(crop) {
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }
  if (pollTimer) {
    clearTimeout(pollTimer)
    pollTimer = null
  }
  aiPollCount.value = 0
  const seq = ++requestSeq

  const name = (crop ?? '').trim()
  if (name === '') {
    sections.value = emptySections()
    sequenceDone.value = false
    meta.value = null
    return
  }

  debounceTimer = setTimeout(() => {
    runSequence(name, seq)
  }, PRICE_GUIDE.DEBOUNCE_MS)
}

watch(
  () => props.cropName,
  (crop) => scheduleLoad(crop),
  { immediate: true },
)

onBeforeUnmount(() => {
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }
  if (pollTimer) {
    clearTimeout(pollTimer)
  }
})

const displayName = computed(() => meta.value?.displayName ?? (props.cropName ?? '').trim())

const anySectionStarted = computed(() => Object.values(sections.value).some((s) => s !== null))

const anyAvailable = computed(() => Object.values(sections.value).some((s) => !!s?.available))

const showQuietNote = computed(() => sequenceDone.value && !anyAvailable.value)

const showCard = computed(() => !showQuietNote.value && anySectionStarted.value)

const readyCount = computed(
  () => Object.values(sections.value).filter((s) => s?.status === 'ready').length,
)

function tiersFor(stageKey) {
  const tiers = sections.value[stageKey]?.tiers ?? {}
  return TIER_ORDER.filter((tier) => tiers[tier]).map((tier) => ({
    key: tier,
    label: PRICE_TIER_LABELS[tier] ?? tier,
    ...tiers[tier],
  }))
}

const daTiers = computed(() => tiersFor(STAGE_DA))

const aiTiers = computed(() => tiersFor(STAGE_AI))

const aiLoading = computed(() => {
  const ai = sections.value[STAGE_AI]
  if (!ai) {
    return false
  }
  return ai.status === 'loading' || (!ai.available && ai.pending === true)
})

const daSourceLabel = computed(() => {
  const first = daTiers.value[0]
  if (!first) {
    return ''
  }
  return PRICE_SOURCE_LABELS[first.source] ?? first.source
})

const showStale = computed(
  () =>
    Boolean(sections.value[STAGE_DA]?.available && sections.value[STAGE_DA]?.is_stale) ||
    Boolean(sections.value[STAGE_AI]?.available && sections.value[STAGE_AI]?.is_stale),
)

const referencePrice = computed(
  () =>
    sections.value[STAGE_YIELDGRID]?.price_per_kg ??
    sections.value[STAGE_DA]?.price_per_kg ??
    sections.value[STAGE_AI]?.price_per_kg ??
    null,
)

const priceComparison = computed(() => {
  if (props.currentPrice === null || referencePrice.value === null || referencePrice.value <= 0) {
    return null
  }

  const diffPct =
    ((props.currentPrice - referencePrice.value) / referencePrice.value) * PERCENT_MULTIPLIER
  const rounded = Math.abs(Math.round(diffPct))

  if (Math.abs(diffPct) <= PRICE_GUIDE.FAIR_BAND_PCT) {
    return 'Your price is within the fair range of this guide.'
  }

  if (diffPct > 0) {
    return `Your price is ${rounded}% above the guide.`
  }

  return `Your price is ${rounded}% below the guide.`
})
</script>

<template>
  <div v-if="(cropName ?? '').trim() !== ''" aria-live="polite">
    <div v-if="showCard" class="bg-dew-50 border border-dew-100 rounded-2xl shadow-soft p-4">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <InformationCircleIcon class="h-5 w-5 text-dew-700" aria-hidden="true" />
          <p class="font-serif text-lg font-bold text-stone-900">Price guide · {{ displayName }}</p>
        </div>
        <span
          class="rounded-full bg-dew-100 text-dew-700 text-xs font-medium px-2.5 py-0.5 tabular-nums whitespace-nowrap"
        >
          {{ readyCount }} of {{ TOTAL_STAGES }}
        </span>
      </div>

      <p v-if="meta?.correctedFrom" class="mt-1 text-sm text-stone-600">
        Showing guide for “{{ displayName }}” (from “{{ meta.correctedFrom }}”).
      </p>

      <div class="mt-1 divide-y divide-dew-100">
        <div v-if="sections.yieldgrid" class="py-3">
          <div class="flex items-center gap-2">
            <component
              :is="STAGE_ICONS[STAGE_YIELDGRID]"
              :class="['h-5 w-5', STAGE_ICON_CLASS[STAGE_YIELDGRID]]"
              aria-hidden="true"
            />
            <p class="text-sm font-semibold text-stone-900">YieldGrid price</p>
          </div>
          <div
            v-if="sections.yieldgrid.status === 'loading'"
            class="mt-2 flex items-center gap-2 text-sm font-normal text-stone-600"
            role="status"
            aria-label="Loading YieldGrid price"
          >
            <AppSpinner class="loading-spinner h-4 w-4 text-moss-600" aria-hidden="true" />
            <span>Loading…</span>
          </div>
          <p
            v-else-if="sections.yieldgrid.available"
            class="mt-1.5 flex items-baseline justify-between gap-4 text-sm"
          >
            <span class="font-normal text-stone-600">{{ sections.yieldgrid.market_name }}</span>
            <span class="font-semibold tabular-nums text-stone-900 whitespace-nowrap">
              {{ formatPeso(sections.yieldgrid.price_per_kg) }}
              <span class="font-normal text-stone-600">/kg</span>
            </span>
          </p>
          <p v-else class="mt-1.5 text-sm font-normal italic text-stone-600">
            No YieldGrid price yet.
          </p>
        </div>

        <div v-if="sections.da" class="py-3">
          <div class="flex items-center gap-2">
            <component
              :is="STAGE_ICONS[STAGE_DA]"
              :class="['h-5 w-5', STAGE_ICON_CLASS[STAGE_DA]]"
              aria-hidden="true"
            />
            <p class="text-sm font-semibold text-stone-900">DA price</p>
          </div>
          <div
            v-if="sections.da.status === 'loading'"
            class="mt-2 flex items-center gap-2 text-sm font-normal text-stone-600"
            role="status"
            aria-label="Loading DA price"
          >
            <AppSpinner class="loading-spinner h-4 w-4 text-moss-600" aria-hidden="true" />
            <span>Loading…</span>
          </div>
          <div v-else-if="sections.da.available">
            <dl class="mt-1.5 space-y-1.5">
              <div
                v-for="tier in daTiers"
                :key="tier.key"
                class="flex items-baseline justify-between gap-4 text-sm"
              >
                <dt class="font-medium text-soil-700">{{ tier.label }}</dt>
                <dd class="font-semibold tabular-nums text-stone-900 whitespace-nowrap">
                  {{ formatPeso(tier.price_per_kg) }}
                  <span class="font-normal text-stone-600">/kg</span>
                </dd>
              </div>
            </dl>
            <p class="mt-1.5 text-xs text-stone-600">
              Source: {{ daSourceLabel }}
              <span v-if="sections.da.observed_at"> · as of {{ sections.da.observed_at }}</span>
            </p>
          </div>
          <p v-else class="mt-1.5 text-sm font-normal italic text-stone-600">No DA price yet.</p>
        </div>

        <div v-if="sections.ai" class="py-3">
          <div class="flex items-center gap-2">
            <component
              :is="STAGE_ICONS[STAGE_AI]"
              :class="['h-5 w-5', STAGE_ICON_CLASS[STAGE_AI]]"
              aria-hidden="true"
            />
            <p class="text-sm font-semibold text-stone-900">AI estimate</p>
          </div>
          <div
            v-if="aiLoading"
            class="mt-2 flex items-center gap-2 text-sm font-normal text-stone-600"
            role="status"
            aria-label="Loading AI estimate"
          >
            <AppSpinner class="loading-spinner h-4 w-4 text-moss-600" aria-hidden="true" />
            <span>Loading…</span>
          </div>
          <div v-else-if="sections.ai.available">
            <dl class="mt-1.5 space-y-1.5">
              <div
                v-for="tier in aiTiers"
                :key="tier.key"
                class="flex items-baseline justify-between gap-4 text-sm"
              >
                <dt class="font-medium text-soil-700">{{ tier.label }}</dt>
                <dd class="font-semibold tabular-nums text-stone-900 whitespace-nowrap">
                  {{ formatPeso(tier.price_per_kg) }}
                  <span class="font-normal text-stone-600">/kg</span>
                </dd>
              </div>
            </dl>
            <p class="mt-1.5 text-xs text-stone-600">
              AI estimate, not a DA price
              <span v-if="sections.ai.observed_at"> · as of {{ sections.ai.observed_at }}</span>
            </p>
            <p
              v-if="sections.ai.pending"
              class="mt-1 flex items-center gap-1.5 text-xs font-normal text-stone-600"
              role="status"
              aria-label="Refreshing AI estimate"
            >
              <AppSpinner class="loading-spinner h-3.5 w-3.5 text-moss-600" aria-hidden="true" />
              <span>Refreshing…</span>
            </p>
          </div>
          <p v-else class="mt-1.5 text-sm font-normal italic text-stone-600">No AI estimate yet.</p>
        </div>
      </div>

      <div v-if="showStale || priceComparison" class="border-t border-dew-100 pt-3">
        <p v-if="showStale" class="flex items-center gap-1.5 text-xs font-medium text-harvest-800">
          <ExclamationTriangleIcon class="h-4 w-4" aria-hidden="true" />
          These prices may be outdated — treat as a rough guide.
        </p>
        <p v-if="priceComparison" class="mt-1.5 text-sm font-medium text-stone-900">
          {{ priceComparison }}
        </p>
      </div>
    </div>

    <p v-else-if="showQuietNote" class="text-sm font-normal text-stone-600">
      No price guide for “{{ (cropName ?? '').trim() }}” yet.
    </p>
  </div>
</template>
