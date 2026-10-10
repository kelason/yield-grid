<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { MapPinIcon, ClipboardDocumentIcon, CheckIcon } from '@heroicons/vue/24/outline'

const { t } = useI18n()

const props = defineProps({
  address: { type: Object, required: true },
  title: { type: String, default: '' },
})

const copied = ref(false)
const COPY_RESET_MS = 2000

async function copyAddress() {
  const parts = [props.address.formatted_address]
  if (props.address.latitude != null && props.address.longitude != null) {
    parts.push(`${props.address.latitude}, ${props.address.longitude}`)
  }
  try {
    await navigator.clipboard.writeText(parts.join('\n'))
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, COPY_RESET_MS)
  } catch {
    copied.value = false
  }
}
</script>

<template>
  <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4 shadow-soft">
    <div class="flex items-start gap-3">
      <div
        class="w-9 h-9 rounded-full bg-gradient-to-br from-moss-500 to-moss-600 flex items-center justify-center flex-shrink-0 shadow-soft"
      >
        <MapPinIcon class="h-5 w-5 text-white" aria-hidden="true" />
      </div>
      <div class="min-w-0 flex-1">
        <p class="text-xs font-semibold uppercase tracking-wider text-soil-700">
          {{ title || t('market.delivery_address.title') }}
        </p>
        <p class="text-sm text-stone-900 font-medium mt-1 leading-relaxed">
          {{ address.formatted_address }}
        </p>
        <div class="flex flex-wrap gap-2 mt-3">
          <button
            type="button"
            @click="copyAddress"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-white border border-stone-200 text-stone-700 hover:border-moss-500 hover:text-moss-700 transition-all duration-200 hover:scale-[1.02] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500"
          >
            <CheckIcon v-if="copied" class="h-4 w-4" aria-hidden="true" />
            <ClipboardDocumentIcon v-else class="h-4 w-4" aria-hidden="true" />
            {{ copied ? t('market.delivery_address.copied') : t('market.delivery_address.copy') }}
          </button>
          <a
            v-if="address.maps_url"
            :href="address.maps_url"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-gradient-to-br from-moss-500 to-moss-600 text-white hover:from-moss-600 hover:to-moss-700 transition-all duration-300 hover:scale-[1.02] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-moss-500"
          >
            {{ t('market.delivery_address.open_maps') }}
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
