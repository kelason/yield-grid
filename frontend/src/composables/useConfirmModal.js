import { ref } from 'vue'

export function useConfirmModal() {
  const isOpen = ref(false)
  const isExecuting = ref(false)
  const config = ref({ title: '', message: '', type: 'primary' })
  let confirmAction = null

  const confirm = (options, action) => {
    config.value = { ...config.value, ...options }
    confirmAction = action
    isOpen.value = true
  }

  const execute = async () => {
    if (isExecuting.value) return
    isExecuting.value = true
    try {
      if (confirmAction) await confirmAction()
    } finally {
      isOpen.value = false
      isExecuting.value = false
      confirmAction = null
    }
  }

  const cancel = () => {
    if (isExecuting.value) return
    isOpen.value = false
    confirmAction = null
  }

  return { isOpen, isExecuting, config, confirm, execute, cancel }
}
