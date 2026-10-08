import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import { useAdminContent } from '../useAdminContent'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

describe('useAdminContent', () => {
  let apiGet
  let apiPost

  function listResponse(data) {
    return {
      data: {
        data,
        meta: { current_page: 1, last_page: 1, total: data.length },
      },
    }
  }

  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    sessionStorage.clear()
    apiGet = vi.fn()
    apiPost = vi.fn()
    useApi.mockReturnValue({ get: apiGet, post: apiPost })
  })

  it('fetches one content type with search and visibility filters', async () => {
    const content = useAdminContent()
    apiGet.mockResolvedValueOnce(listResponse([{ id: '9', title: 'Rice lot' }]))
    content.filters.value = { search: 'rice', visibility: 'visible' }

    await content.fetchContent('listing', 1)

    expect(apiGet).toHaveBeenCalledWith('/admin/content/listing', {
      params: { page: 1, per_page: 10, search: 'rice', visibility: 'visible' },
    })
    expect(content.activeType.value).toBe('listing')
    expect(content.list.value).toEqual([{ id: '9', title: 'Rice lot' }])
    expect(content.loading.value).toBe(false)
  })

  it('ignores a late list response for a superseded type', async () => {
    const content = useAdminContent()
    let releaseFirst
    apiGet.mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          releaseFirst = resolve
        }),
    )
    apiGet.mockResolvedValueOnce(listResponse([{ id: '3', title: 'Corn' }]))

    const first = content.fetchContent('contract', 1)
    const second = content.fetchContent('listing', 1)
    await second
    releaseFirst(listResponse([{ id: '7', title: 'Stale contract' }]))
    await first

    expect(content.activeType.value).toBe('listing')
    expect(content.list.value).toEqual([{ id: '3', title: 'Corn' }])
    expect(content.loading.value).toBe(false)
  })

  it('loads admin detail even where member routes would deny access', async () => {
    const content = useAdminContent()
    const detail = { type: 'contract', id: '7', title: 'Corn lot', is_hidden: false }
    apiGet.mockResolvedValueOnce({ data: { data: detail } })

    await content.fetchDetail('contract', '7')

    expect(apiGet).toHaveBeenCalledWith('/admin/content/contract/7')
    expect(content.selected.value).toEqual(detail)
  })

  it('ignores a late detail response for a superseded target', async () => {
    const content = useAdminContent()
    let releaseFirst
    apiGet.mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          releaseFirst = resolve
        }),
    )
    apiGet.mockResolvedValueOnce({ data: { data: { type: 'contract', id: '8' } } })

    const first = content.fetchDetail('contract', '7')
    const second = content.fetchDetail('contract', '8')
    await second
    releaseFirst({ data: { data: { type: 'contract', id: '7' } } })
    await first

    expect(content.selected.value).toEqual({ type: 'contract', id: '8' })
    expect(content.detailLoading.value).toBe(false)
  })

  it('hides content and reports the affected root', async () => {
    const content = useAdminContent()
    content.list.value = [{ type: 'listing', id: '3', is_hidden: false }]
    content.selected.value = { type: 'listing', id: '9', moderation_root_id: '3' }
    const hidden = { type: 'listing', id: '3', is_hidden: true }
    apiPost.mockResolvedValueOnce({
      data: { data: hidden, meta: { selected_id: '9', affected_root_id: '3' } },
    })

    const result = await content.hide('listing', '9', 'Prohibited item.')

    expect(apiPost).toHaveBeenCalledWith('/admin/content/listing/9/hide', {
      reason: 'Prohibited item.',
    })
    expect(result).toEqual({ item: hidden, selectedId: '9', affectedRootId: '3' })
    expect(content.list.value).toEqual([hidden])
    expect(content.mutating.value).toBe(false)
  })

  it('restores hidden content and refreshes the selected detail', async () => {
    const content = useAdminContent()
    content.selected.value = { type: 'thread', id: '7', is_hidden: true }
    const restored = { type: 'thread', id: '7', is_hidden: false }
    apiPost.mockResolvedValueOnce({
      data: { data: restored, meta: { selected_id: '7', affected_root_id: '7' } },
    })

    await content.restore('thread', '7', 'Appeal upheld.')

    expect(apiPost).toHaveBeenCalledWith('/admin/content/thread/7/restore', {
      reason: 'Appeal upheld.',
    })
    expect(content.selected.value).toEqual(restored)
  })

  it('preserves the repeated-state message on conflict', async () => {
    const content = useAdminContent()
    apiPost.mockRejectedValueOnce({
      response: { status: 409, data: { message: 'Content is already hidden.' } },
    })

    const failure = await content.hide('thread', '7', 'Spam.').catch((error) => error)

    expect(failure.message).toContain('already hidden')
    expect(content.mutating.value).toBe(false)
  })
})
