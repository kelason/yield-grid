import { mount } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'
import ServerErrorPage from '../ServerErrorPage.vue'
const { push } = vi.hoisted(() => ({ push: vi.fn() }))
vi.mock('vue-router', () => ({ useRouter: () => ({ push }) }))
describe('ServerErrorPage navigation', () => {
  it('routes home and support actions without confirmation', async () => {
    const wrapper = mount(ServerErrorPage)
    await wrapper.findAll('button')[0].trigger('click')
    expect(push).toHaveBeenCalledWith('/')
    await wrapper.findAll('button')[1].trigger('click')
    expect(push).toHaveBeenCalledWith('/contact')
  })
})
