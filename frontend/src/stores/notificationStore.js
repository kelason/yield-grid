import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref([])
  let nextId = 1

  function addNotification(notification) {
    const id = nextId++
    const newNotification = {
      id,
      type: notification.type || 'info', // 'success', 'error', 'info', 'warning'
      title: notification.title,
      message: notification.message,
      duration: notification.duration || 5000,
    }
    
    notifications.value.push(newNotification)

    if (newNotification.duration > 0) {
      setTimeout(() => {
        removeNotification(id)
      }, newNotification.duration)
    }
  }

  function removeNotification(id) {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index !== -1) {
      notifications.value.splice(index, 1)
    }
  }

  function success(message, title = 'Success') {
    addNotification({ type: 'success', message, title })
  }

  function error(message, title = 'Error') {
    addNotification({ type: 'error', message, title })
  }

  function info(message, title = 'Info') {
    addNotification({ type: 'info', message, title })
  }

  function warning(message, title = 'Warning') {
    addNotification({ type: 'warning', message, title })
  }

  return {
    notifications,
    addNotification,
    removeNotification,
    success,
    error,
    info,
    warning
  }
})
