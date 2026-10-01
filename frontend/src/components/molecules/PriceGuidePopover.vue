<script setup>
import { computed, nextTick, onBeforeUnmount, ref } from 'vue'
import { InformationCircleIcon } from '@heroicons/vue/24/outline'
import PriceGuideHint from '@/components/molecules/PriceGuideHint.vue'

const props = defineProps({
  cropName: {
    type: String,
    default: '',
  },
  currentPrice: {
    type: Number,
    default: null,
  },
  regionCode: {
    type: String,
    default: null,
  },
})

const PANEL_OFFSET_PX = 8
const VIEWPORT_MARGIN_PX = 16
const PANEL_FALLBACK_WIDTH_PX = 416

const isOpen = ref(false)
const panelStyle = ref({})
const triggerRef = ref(null)
const panelRef = ref(null)

const hasCrop = computed(() => (props.cropName ?? '').trim() !== '')

function positionPanel() {
  if (!triggerRef.value) {
    return
  }
  const rect = triggerRef.value.getBoundingClientRect()
  const width = panelRef.value?.offsetWidth || PANEL_FALLBACK_WIDTH_PX
  const left = Math.max(
    VIEWPORT_MARGIN_PX,
    Math.min(rect.left, window.innerWidth - width - VIEWPORT_MARGIN_PX),
  )
  panelStyle.value = {
    top: `${rect.bottom + PANEL_OFFSET_PX}px`,
    left: `${left}px`,
  }
}

function onOutsideMousedown(event) {
  const target = event.target
  if (
    panelRef.value?.contains(target) ||
    triggerRef.value?.contains(target) ||
    triggerRef.value === target
  ) {
    return
  }
  close()
}

function onEscape(event) {
  if (event.key === 'Escape') {
    close()
  }
}

function addListeners() {
  document.addEventListener('mousedown', onOutsideMousedown)
  document.addEventListener('keydown', onEscape)
  window.addEventListener('scroll', positionPanel, true)
  window.addEventListener('resize', positionPanel)
}

function removeListeners() {
  document.removeEventListener('mousedown', onOutsideMousedown)
  document.removeEventListener('keydown', onEscape)
  window.removeEventListener('scroll', positionPanel, true)
  window.removeEventListener('resize', positionPanel)
}

async function open() {
  isOpen.value = true
  addListeners()
  await nextTick()
  positionPanel()
}

function close() {
  isOpen.value = false
  removeListeners()
}

function toggle() {
  if (isOpen.value) {
    close()
  } else {
    open()
  }
}

onBeforeUnmount(() => {
  removeListeners()
})
</script>

<template>
  <span class="inline-flex">
    <button
      ref="triggerRef"
      type="button"
      :aria-expanded="isOpen"
      aria-label="Show price guide"
      title="Show price guide"
      class="inline-flex items-center gap-1 rounded-full py-0.5 pl-1 pr-2 text-xs font-medium text-dew-700 transition-all duration-200 hover:bg-dew-100 hover:text-dew-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500"
      @click="toggle"
    >
      <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
      Price Guide
    </button>
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div
          v-if="isOpen"
          ref="panelRef"
          :style="panelStyle"
          role="dialog"
          aria-label="Price guide"
          class="fixed z-[110] max-h-[70vh] w-[min(26rem,calc(100vw-2rem))] overflow-y-auto rounded-2xl border border-stone-200 bg-white p-4 shadow-organic"
        >
          <PriceGuideHint
            v-if="hasCrop"
            :crop-name="cropName"
            :current-price="currentPrice"
            :region-code="regionCode"
          />
          <p v-else class="text-sm font-light text-stone-600">
            Enter a crop name to load the price guide.
          </p>
        </div>
      </Transition>
    </Teleport>
  </span>
</template>
