import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import FarmCard from '../FarmCard.vue'

describe('FarmCard.vue verification badge', () => {
  const baseFarm = {
    id: 1,
    name: 'Green Acres',
    city: 'Bocaue',
    plots_count: 2,
    total_area: 5,
  }

  it('shows the badge beside the name for a verified farm', () => {
    const wrapper = mount(FarmCard, {
      props: { farm: { ...baseFarm, verification_status: 'verified' } },
    })

    expect(wrapper.find('[data-testid="verified-badge"]').exists()).toBe(true)
  })

  it('hides the badge for a pending farm', () => {
    const wrapper = mount(FarmCard, {
      props: { farm: { ...baseFarm, verification_status: 'pending' } },
    })

    expect(wrapper.find('[data-testid="verified-badge"]').exists()).toBe(false)
  })

  it('hides the badge when verification status is missing', () => {
    const wrapper = mount(FarmCard, { props: { farm: baseFarm } })

    expect(wrapper.find('[data-testid="verified-badge"]').exists()).toBe(false)
  })
})
