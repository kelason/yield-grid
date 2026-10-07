import { ref } from 'vue'

export function usePendingConfirmation(pending) {
  const isExecuting = ref(false)
  const cancel = () => {
    if (!isExecuting.value) pending.value = null
  }
  const execute = async (action) => {
    if (isExecuting.value || !pending.value) return
    isExecuting.value = true
    try {
      await action(pending.value)
    } finally {
      isExecuting.value = false
      pending.value = null
    }
  }
  return { isExecuting, execute, cancel }
}

async function runModalAction(isOpen, action) {
  try {
    await action?.()
  } finally {
    isOpen.value = false
  }
}
export function useConfirmModal() {
  const isOpen = ref(false)
  const config = ref({ title: '', message: '', type: 'primary' })
  const pending = ref(null)
  const { isExecuting, execute: run, cancel: clear } = usePendingConfirmation(pending)
  const confirm = (options, action) => {
    if (isExecuting.value) return
    config.value = { title: '', message: '', type: 'primary', ...options }
    pending.value = { action }
    isOpen.value = true
  }
  const execute = () => run(({ action }) => runModalAction(isOpen, action))
  const cancel = () => {
    if (isExecuting.value) return
    clear()
    isOpen.value = false
  }
  return { isOpen, isExecuting, config, confirm, execute, cancel }
}
