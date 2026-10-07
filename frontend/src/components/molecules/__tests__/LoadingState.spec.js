import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import LoadingState from '../LoadingState.vue'

describe('LoadingState', () => {
  it('announces one contextual loading status for repeated skeletons', () => {
    const wrapper = mount(LoadingState, {
      props: { label: 'Loading farms' },
      slots: {
        default:
          '<div aria-hidden="true">placeholder</div><div aria-hidden="true">placeholder</div>',
      },
    })
    expect(wrapper.findAll('[role="status"]')).toHaveLength(1)
    expect(wrapper.get('[role="status"]').text()).toContain('Loading farms')
  })
})
