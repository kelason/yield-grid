import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AppButton from './AppButton.vue'

describe('AppButton', () => {
  it('renders correctly with default props', () => {
    const wrapper = mount(AppButton, {
      slots: {
        default: 'Click Me',
      },
    })
    expect(wrapper.text()).toContain('Click Me')
    expect(wrapper.classes()).toContain('bg-gradient-to-r')
  })

  it('renders a loading spinner and is disabled when loading is true', () => {
    const wrapper = mount(AppButton, {
      props: {
        loading: true,
      },
    })
    expect(wrapper.find('svg.animate-spin').exists()).toBe(true)
    expect(wrapper.attributes()).toHaveProperty('disabled')
  })

  it('applies the correct classes for a secondary variant', () => {
    const wrapper = mount(AppButton, {
      props: {
        variant: 'secondary',
      },
    })
    expect(wrapper.classes()).toContain('bg-farm-100')
  })
})
