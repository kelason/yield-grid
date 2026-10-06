import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import CompatibilityChecker from '../CompatibilityChecker.vue'

const taxonomy = {
  types: {
    vegetable: { label: 'Vegetables', subtypes: {} },
    fruit: { label: 'Fruits', subtypes: {} },
  },
  crops: [
    { slug: 'tomato', name: 'Tomatoes (Kamatis)', type: 'vegetable', subtype: 'fruiting' },
    { slug: 'lettuce', name: 'Lettuce', type: 'vegetable', subtype: 'leafy_greens' },
    { slug: 'calamansi', name: 'Calamansi', type: 'fruit', subtype: 'citrus' },
  ],
}

const avoidResult = {
  crop_a: { slug: 'tomato', name: 'Tomatoes (Kamatis)', family: 'nightshade' },
  crop_b: { slug: 'eggplant', name: 'Eggplant (Talong)', family: 'nightshade' },
  verdict: 'avoid',
  rotation: {
    verdict: 'avoid',
    reasons: [
      'Both are nightshade family — planting them back to back carries soil disease pressure.',
    ],
  },
  companion: { verdict: 'compatible', reasons: ['No companion conflict between these families.'] },
}

function mountChecker(props = {}) {
  return mount(CompatibilityChecker, {
    props: { taxonomy, result: null, checking: false, error: '', ...props },
  })
}

describe('CompatibilityChecker.vue', () => {
  it('groups crop options by produce type', () => {
    const wrapper = mountChecker()
    const groups = wrapper.find('[data-test="crop-a-select"]').findAll('optgroup')

    expect(groups.map((group) => group.attributes('label'))).toEqual(['Vegetables', 'Fruits'])
    expect(wrapper.find('[data-test="crop-a-select"]').text()).toContain('Tomatoes (Kamatis)')
  })

  it('renders nothing selectable without taxonomy', () => {
    const wrapper = mountChecker({ taxonomy: null })

    expect(wrapper.find('[data-test="crop-a-select"]').findAll('option').length).toBe(1)
    expect(wrapper.find('[data-test="verdict-badge"]').exists()).toBe(false)
  })

  it('emits the selected pair on check', async () => {
    const wrapper = mountChecker()

    await wrapper.find('[data-test="crop-a-select"] select').setValue('tomato')
    await wrapper.find('[data-test="crop-b-select"] select').setValue('lettuce')
    await wrapper.find('[data-test="check-button"]').trigger('click')

    expect(wrapper.emitted('check-compatibility')[0][0]).toEqual({
      cropA: 'tomato',
      cropB: 'lettuce',
    })
  })

  it('requires both crops before checking', async () => {
    const wrapper = mountChecker()

    await wrapper.find('[data-test="crop-a-select"] select').setValue('tomato')

    expect(wrapper.find('[data-test="check-button"]').attributes('disabled')).toBeDefined()
    expect(wrapper.emitted('check-compatibility')).toBeUndefined()
  })

  it('shows the verdict with rotation and companion reasons', () => {
    const wrapper = mountChecker({ result: avoidResult })

    expect(wrapper.find('[data-test="verdict-badge"]').text()).toContain('Avoid')
    expect(wrapper.find('[data-test="rotation-reasons"]').text()).toContain('nightshade')
    expect(wrapper.find('[data-test="companion-reasons"]').text()).toContain(
      'No companion conflict',
    )
  })

  it('shows errors from the check', () => {
    const wrapper = mountChecker({ error: 'Could not check compatibility. Please try again.' })

    expect(wrapper.find('[data-test="checker-error"]').text()).toContain(
      'Could not check compatibility',
    )
  })
})
