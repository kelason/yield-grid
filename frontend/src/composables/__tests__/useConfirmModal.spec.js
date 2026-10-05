import { describe, it, expect, vi } from 'vitest'
import { useConfirmModal } from '../useConfirmModal'

function deferred() {
  let resolve
  const promise = new Promise((res) => {
    resolve = res
  })
  return { promise, resolve }
}

describe('useConfirmModal', () => {
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
