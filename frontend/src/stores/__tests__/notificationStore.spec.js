import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach } from 'vitest'
import { useNotificationStore } from '../notificationStore'

describe('notificationStore keys', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('keeps translation keys and params on the notification', () => {
    const store = useNotificationStore()

    store.addNotification({
      type: 'info',
      messageKey: 'common.actions.retry',
      titleKey: 'common.nav.home',
      messageParams: { count: 2 },
      duration: 0,
    })

    expect(store.notifications).toHaveLength(1)
    expect(store.notifications[0]).toMatchObject({
      messageKey: 'common.actions.retry',
      titleKey: 'common.nav.home',
      messageParams: { count: 2 },
    })
  })

  it('keeps legacy plain messages working', () => {
    const store = useNotificationStore()

    store.error('Something broke')

    expect(store.notifications).toHaveLength(1)
    expect(store.notifications[0].message).toBe('Something broke')
    expect(store.notifications[0].messageKey).toBeUndefined()
  })
})
