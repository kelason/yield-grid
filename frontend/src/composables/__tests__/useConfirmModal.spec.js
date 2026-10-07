import { describe, it, expect, vi } from 'vitest'
import { useConfirmModal } from '../useConfirmModal'
import { usePendingConfirmation } from '../useConfirmModal'
import { ref } from 'vue'

function deferred() {
  let resolve
  const promise = new Promise((res) => {
    resolve = res
  })
  return { promise, resolve }
}

describe('useConfirmModal', () => {
  it('holds pending data and ignores cancel/repeated execution until the request settles', async () => {
    const pending = ref({ id: 7 })
    const modal = usePendingConfirmation(pending)
    const gate = deferred()
    const action = vi.fn(() => gate.promise)
    const running = modal.execute(action)
    modal.cancel()
    await modal.execute(action)
    expect(pending.value).toEqual({ id: 7 })
    expect(action).toHaveBeenCalledTimes(1)
    expect(action).toHaveBeenCalledWith({ id: 7 })
    gate.resolve()
    await running
    expect(pending.value).toBeNull()
    expect(modal.isExecuting.value).toBe(false)
  })
  it('resets action-specific text before opening the next confirmation', () => {
    const modal = useConfirmModal()
    modal.confirm({ title: 'Pay', message: 'Pay?', confirmText: 'Pay now' }, () => {})
    modal.cancel()
    modal.confirm({ title: 'Save', message: 'Save?' }, () => {})
    expect(modal.config.value.confirmText).toBeUndefined()
  })
  it('tracks execution while the confirmed action runs', async () => {
    const modal = useConfirmModal()
    const gate = deferred()
    modal.confirm({ title: 'Do it', message: 'Sure?' }, () => gate.promise)

    const running = modal.execute()
    expect(modal.isExecuting.value).toBe(true)

    gate.resolve()
    await running
    expect(modal.isExecuting.value).toBe(false)
    expect(modal.isOpen.value).toBe(false)
  })

  it('ignores re-entrant confirms while an action is running', async () => {
    const modal = useConfirmModal()
    const action = vi.fn(() => new Promise(() => {}))
    modal.confirm({ title: 'Do it', message: 'Sure?' }, action)

    modal.execute()
    await modal.execute()

    expect(action).toHaveBeenCalledTimes(1)
  })

  it('allows confirming again after the previous action settles', async () => {
    const modal = useConfirmModal()
    const action = vi.fn(async () => {})
    modal.confirm({ title: 'Do it', message: 'Sure?' }, action)
    await modal.execute()

    modal.confirm({ title: 'Do it', message: 'Sure?' }, action)
    await modal.execute()

    expect(action).toHaveBeenCalledTimes(2)
  })
})
