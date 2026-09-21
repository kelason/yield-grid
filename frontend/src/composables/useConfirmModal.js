import { ref } from 'vue'

export function useConfirmModal() {
  const isOpen = ref(false)
  const config = ref({ title: '', message: '', type: 'primary' })
  let confirmAction = null

  const confirm = (options, action) => {
    config.value = { ...config.value, ...options }
    confirmAction = action
    isOpen.value = true
  }

  const execute = async () => {
    try {
      if (confirmAction) await confirmAction()
    } finally {
      isOpen.value = false
      confirmAction = null
    }
  }

  const cancel = () => {
    isOpen.value = false
    confirmAction = null
  }

  return { isOpen, config, confirm, execute, cancel }
}
