import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import ImprovementTips from '../ImprovementTips.vue'

const TIPS = [
  { dimension: 'plot_activity', message: 'Complete polygon boundaries for all your plots.' },
  { dimension: 'plot_activity', message: 'Add soil type information to your plots.' },
  { dimension: 'platform_tenure', message: 'Verify your email address.' },
]

function mountTips(tips = TIPS) {
  return mount(ImprovementTips, { props: { tips } })
}

describe('ImprovementTips', () => {
  it('groups tips under dimension headings with weights', () => {
    const wrapper = mountTips()

    expect(wrapper.text()).toContain('Plot Activity')
    expect(wrapper.text()).toContain('15%')
    expect(wrapper.text()).toContain('Complete polygon boundaries for all your plots.')
    expect(wrapper.text()).toContain('Add soil type information to your plots.')
    expect(wrapper.text()).toContain('Platform Tenure')
    expect(wrapper.text()).toContain('10%')
    expect(wrapper.text()).toContain('Verify your email address.')
  })

  it('summarizes how many actions there are', () => {
    const wrapper = mountTips()

    expect(wrapper.text()).toContain('3 suggested actions')
  })

  it('accepts legacy plain-string tips', () => {
    const wrapper = mountTips(['Complete your plot boundaries.'])

    expect(wrapper.text()).toContain('Complete your plot boundaries.')
  })

  it('shows the empty state when there are no tips', () => {
    const wrapper = mountTips([])

    expect(wrapper.text()).toContain('Outstanding')
  })
})
