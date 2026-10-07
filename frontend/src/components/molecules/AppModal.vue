<script setup>
import { ref, useId, watch, onMounted, onBeforeUnmount } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import AppButton from '../atoms/AppButton.vue'
const props = defineProps({
  isOpen: { type: Boolean, required: true },
  title: { type: String, default: '' },
  labelledby: { type: String, default: '' },
  describedby: { type: String, default: '' },
  size: { type: String, default: 'lg' },
  placement: { type: String, default: 'center' },
  busy: Boolean,
})
const emit = defineEmits(['close'])
const dialog = ref(null)
const titleId = `modal-${useId()}`
const sizes = { sm: 'max-w-md', md: 'max-w-xl', lg: 'max-w-2xl' }
let returnFocus = null

function restoreFocus() {
  if (
    returnFocus?.isConnected &&
    returnFocus.getClientRects().length &&
    !returnFocus.matches(':disabled')
  )
    returnFocus.focus()
  else document.getElementById('main-content')?.focus()
}
function syncDialog() {
  if (!dialog.value) return
  if (props.isOpen && !dialog.value.open) {
    returnFocus = document.activeElement
    dialog.value.showModal?.()
  } else if (!props.isOpen && dialog.value.open) {
    dialog.value.close()
    restoreFocus()
  }
}
function requestClose() {
  if (!props.busy) emit('close')
}
function handleBackdrop(event) {
  if (event.target !== dialog.value) return
  const bounds = dialog.value.getBoundingClientRect()
  const outside =
    event.clientX < bounds.left ||
    event.clientX > bounds.right ||
    event.clientY < bounds.top ||
    event.clientY > bounds.bottom
  if (outside) requestClose()
}
function handleNativeClose() {
  if (props.isOpen) requestClose()
}
watch(() => props.isOpen, syncDialog, { flush: 'post' })
onMounted(syncDialog)
onBeforeUnmount(() => {
  if (dialog.value?.open) {
    dialog.value.close()
    restoreFocus()
  }
})
</script>
<template>
  <Teleport to="body">
    <dialog
      ref="dialog"
      :aria-labelledby="labelledby || (title ? titleId : undefined)"
      :aria-describedby="describedby || undefined"
      :aria-busy="busy ? 'true' : undefined"
      :class="[
        'app-dialog rounded-3xl border border-stone-200 bg-white p-0 text-stone-900 shadow-organic',
        sizes[size],
        placement === 'left' ? 'app-drawer' : '',
      ]"
      @cancel.prevent="requestClose"
      @close="handleNativeClose"
      @click="handleBackdrop"
    >
      <div v-if="isOpen" class="relative flex max-h-[90dvh] flex-col">
        <header
          v-if="title"
          class="flex items-start justify-between gap-4 border-b border-stone-200 px-6 py-4"
        >
          <h2 :id="titleId" class="self-center font-serif text-2xl font-bold break-words">
            {{ title }}
          </h2>
          <AppButton
            variant="ghost"
            size="sm"
            :disabled="busy"
            aria-label="Close dialog"
            @click="requestClose"
            ><XMarkIcon class="h-5 w-5" aria-hidden="true"
          /></AppButton>
        </header>
        <AppButton
          v-else
          variant="ghost"
          size="sm"
          class="absolute right-3 top-3 z-10"
          :disabled="busy"
          aria-label="Close dialog"
          @click="requestClose"
          ><XMarkIcon class="h-5 w-5" aria-hidden="true"
        /></AppButton>
        <div class="min-h-0 overflow-y-auto p-6"><slot /></div>
        <footer v-if="$slots.footer" class="border-t border-stone-200 p-6">
          <slot name="footer" />
        </footer>
      </div>
    </dialog>
  </Teleport>
</template>
<style scoped>
.app-dialog {
  width: calc(100% - 2rem);
}
.app-dialog:not([open]) {
  display: none;
}
.app-dialog::backdrop {
  @apply bg-soil-900/50;
}
.app-dialog[open] {
  animation: dialog-enter 200ms ease-out;
}
.app-drawer {
  margin: 0;
  width: min(22rem, 90vw);
  height: 100dvh;
  max-height: none;
}
.app-drawer > div {
  max-height: 100dvh;
}
@keyframes dialog-enter {
  from {
    opacity: 0;
    transform: translateY(6px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: none;
  }
}
@media (prefers-reduced-motion: reduce) {
  .app-dialog[open] {
    animation: none;
  }
}
</style>
