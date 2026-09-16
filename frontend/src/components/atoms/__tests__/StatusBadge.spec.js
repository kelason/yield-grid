import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import StatusBadge from '../StatusBadge.vue'

describe('StatusBadge', () => {
  it('renders available status correctly', () => {
    const wrapper = mount(StatusBadge, {
      props: {
        status: 'available'
      }
    })
    
    expect(wrapper.text()).toContain('Available')
    expect(wrapper.classes()).toContain('bg-farm-100')
    expect(wrapper.classes()).toContain('text-farm-800')
  })

  it('renders sold status correctly', () => {
    const wrapper = mount(StatusBadge, {
      props: {
        status: 'sold'
      }
    })
    
    expect(wrapper.text()).toContain('Sold')
    expect(wrapper.classes()).toContain('bg-green-100')
    expect(wrapper.classes()).toContain('text-green-800')
  })
  
  it('applies small size classes when requested', () => {
    const wrapper = mount(StatusBadge, {
      props: {
        status: 'available',
        size: 'sm'
      }
    })
    
    expect(wrapper.classes()).toContain('text-xs')
  })
})
