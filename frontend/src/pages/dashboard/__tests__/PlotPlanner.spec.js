import { createPinia, setActivePinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { it, expect, vi } from 'vitest'
import PlotPlanner from '../PlotPlanner.vue'
import PlotDrawer from '@/components/organisms/PlotDrawer.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import SoilTypeSelect from '@/components/molecules/SoilTypeSelect.vue'
import { useApi } from '@/composables/useApi'
import { useFarmingStore } from '@/stores/farming'
import { useAuthStore } from '@/stores/auth'
vi.mock('@/composables/useApi', () => ({ useApi: vi.fn() }))
vi.mock('vue-router', () => ({
  useRoute: () => ({ params: { farmId: '1' } }),
  useRouter: () => ({ push: vi.fn() }),
}))
it('confirms the unchanged plot geometry once and holds the dialog until saving settles', async () => {
  setActivePinia(createPinia())
  const api = {
    get: vi.fn().mockResolvedValue({ data: { type: 'FeatureCollection', features: [] } }),
    post: vi.fn(),
  }
  useApi.mockReturnValue(api)
  useFarmingStore().activeFarm = { id: 1, name: 'Green Acres' }
  useAuthStore().user = { id: 1, email_verified_at: '2026-01-01' }
  let resolve
  api.post.mockImplementation(
    () =>
      new Promise((done) => {
        resolve = done
      }),
  )
  const wrapper = mount(PlotPlanner, {
    global: { stubs: { PlotDrawer: true, RouterLink: true, teleport: true } },
  })
  await flushPromises()
  const coordinates = [
    [121, 15],
    [121.01, 15],
    [121.01, 15.01],
    [121, 15],
  ]
  wrapper.findComponent(PlotDrawer).vm.$emit('plot-drawn', { layer: {}, coordinates })
  await wrapper.vm.$nextTick()
  await wrapper.get('#plot-name').setValue('North field')
  wrapper.findComponent(SoilTypeSelect).vm.$emit('update:modelValue', 'clay')
  await wrapper.vm.$nextTick()
  await wrapper.get('form').trigger('submit')
  expect(api.post).not.toHaveBeenCalled()
  const dialog = wrapper.findComponent(ConfirmModal)
  dialog.vm.$emit('confirm')
  await flushPromises()
  expect(dialog.props('isOpen')).toBe(true)
  expect(dialog.props('loading')).toBe(true)
  dialog.vm.$emit('confirm')
  expect(api.post).toHaveBeenCalledTimes(1)
  expect(api.post).toHaveBeenCalledWith('/farms/1/plots', {
    name: 'North field',
    soil_type: 'clay',
    coordinates,
  })
  resolve({ data: { data: { id: 4 } } })
  await flushPromises()
  expect(dialog.props('isOpen')).toBe(false)
  wrapper.unmount()
})

it('shows the badge only on plot rows whose plot and farm are both verified', async () => {
  setActivePinia(createPinia())
  const api = {
    get: vi.fn().mockImplementation((url) => {
      if (url === '/plots') {
        return Promise.resolve({
          data: {
            data: [
              {
                id: 5,
                verification_status: 'verified',
                farm: { id: 1, verification_status: 'verified' },
              },
              {
                id: 6,
                verification_status: 'pending',
                farm: { id: 1, verification_status: 'verified' },
              },
            ],
          },
        })
      }
      return Promise.resolve({
        data: {
          type: 'FeatureCollection',
          features: [
            { properties: { id: 5, name: 'North', soil_type: 'clay', calculated_area: 1.5 } },
            { properties: { id: 6, name: 'South', soil_type: 'loam', calculated_area: 2 } },
          ],
        },
      })
    }),
    post: vi.fn(),
  }
  useApi.mockReturnValue(api)
  useFarmingStore().activeFarm = { id: 1, name: 'Green Acres' }
  useAuthStore().user = { id: 1, email_verified_at: '2026-01-01' }

  const wrapper = mount(PlotPlanner, {
    global: { stubs: { PlotDrawer: true, RouterLink: true, teleport: true } },
  })
  await flushPromises()

  expect(wrapper.findAll('[data-testid="verified-badge"]')).toHaveLength(1)
  wrapper.unmount()
})

it('shows no badge on plot rows when verification data is unavailable', async () => {
  setActivePinia(createPinia())
  const api = {
    get: vi.fn().mockImplementation((url) => {
      if (url === '/plots') return Promise.resolve({ data: { data: [] } })
      return Promise.resolve({
        data: {
          type: 'FeatureCollection',
          features: [{ properties: { id: 5, name: 'North', soil_type: 'clay' } }],
        },
      })
    }),
    post: vi.fn(),
  }
  useApi.mockReturnValue(api)
  useFarmingStore().activeFarm = { id: 1, name: 'Green Acres' }
  useAuthStore().user = { id: 1, email_verified_at: '2026-01-01' }

  const wrapper = mount(PlotPlanner, {
    global: { stubs: { PlotDrawer: true, RouterLink: true, teleport: true } },
  })
  await flushPromises()

  expect(wrapper.findAll('[data-testid="verified-badge"]')).toHaveLength(0)
  wrapper.unmount()
})
