<script setup>
import { computed, nextTick, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { CheckIcon, ChevronDownIcon, GlobeAltIcon } from '@heroicons/vue/24/outline'
import { SUPPORTED_LOCALES } from '@/i18n'
import { useAuthStore } from '@/stores/auth'

const { t, locale } = useI18n()
const authStore = useAuthStore()
const open = ref(false)
const toggleRef = ref(null)
const optionRefs = ref([])

const options = computed(() =>
  SUPPORTED_LOCALES.map((code) => ({ code, label: t(`common.language.${labelKey(code)}`) })),
)
const currentCode = computed(() => locale.value.toUpperCase())

function labelKey(code) {
  return code === 'en' ? 'english' : code === 'tl' ? 'tagalog' : 'bisaya'
}

function setOptionRef(element, index) {
  optionRefs.value[index] = element
}

function focusOption(index) {
  const clamped = (index + options.value.length) % options.value.length
  optionRefs.value[clamped]?.focus()
}

function close() {
  open.value = false
}

function choose(code) {
  authStore.switchLocale(code)
  close()
}

async function onToggleKeydown(event) {
  if (event.key === 'Escape') {
    close()
    return
  }
  if (event.key === 'ArrowDown') {
    event.preventDefault()
    open.value = true
    await nextTick()
    focusOption(0)
  }
}

function onOptionKeydown(event, index) {
  if (event.key === 'ArrowDown') {
    event.preventDefault()
    focusOption(index + 1)
  } else if (event.key === 'ArrowUp') {
    event.preventDefault()
    focusOption(index - 1)
  } else if (event.key === 'Escape') {
    toggleRef.value?.focus()
  }
}
</script>

<template>
  <div class="relative inline-flex">
    <button
      ref="toggleRef"
      type="button"
      data-testid="language-menu"
      class="inline-flex min-h-11 items-center gap-2 rounded-full border border-stone-200 bg-white px-4 text-sm font-medium text-stone-700 shadow-soft transition-all duration-200 hover:text-stone-900 hover:bg-stone-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-moss-500"
      :aria-label="t('common.language.label')"
      aria-haspopup="listbox"
      :aria-expanded="open"
      @click="open = !open"
      @keydown="onToggleKeydown"
    >
      <GlobeAltIcon class="h-5 w-5" aria-hidden="true" />
      <span aria-hidden="true">{{ currentCode }}</span>
      <ChevronDownIcon
        class="h-4 w-4 transition-transform duration-200"
        :class="{ 'rotate-180': open }"
        aria-hidden="true"
      />
    </button>
    <ul
      v-if="open"
      role="listbox"
      :aria-label="t('common.language.label')"
      class="absolute right-0 top-full z-50 mt-2 min-w-44 rounded-2xl border border-stone-200 bg-white py-2 shadow-organic"
      @keydown.escape="close"
    >
      <li v-for="(option, index) in options" :key="option.code">
        <button
          :ref="(element) => setOptionRef(element, index)"
          type="button"
          role="option"
          :data-testid="`locale-${option.code}`"
          :aria-selected="locale === option.code"
          :aria-current="locale === option.code ? 'true' : undefined"
          class="flex min-h-11 w-full items-center justify-between gap-3 px-4 text-left text-sm text-stone-700 transition-colors duration-200 hover:bg-stone-100 hover:text-stone-900 focus:outline-none focus-visible:bg-stone-100"
          @click="choose(option.code)"
          @keydown="onOptionKeydown($event, index)"
        >
          {{ option.label }}
          <CheckIcon
            v-if="locale === option.code"
            class="h-4 w-4 text-moss-600"
            aria-hidden="true"
          />
        </button>
      </li>
    </ul>
  </div>
</template>
