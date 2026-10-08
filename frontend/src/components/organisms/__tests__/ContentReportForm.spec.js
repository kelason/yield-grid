import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import ContentReportForm from '../ContentReportForm.vue'

describe('ContentReportForm.vue', () => {
  const target = { reportable_type: 'listing', reportable_id: '7', title: 'Rice lot' }

  function mountForm(props = {}) {
    return mount(ContentReportForm, {
      props: { target, reason: '', description: '', ...props },
    })
  }

  it('summarizes the report target', () => {
    const wrapper = mountForm()

    expect(wrapper.text()).toContain('Rice lot')
    expect(wrapper.text()).toContain('Harvest listing')
  })

  it('offers all eight report reasons', () => {
    const wrapper = mountForm()
    const options = wrapper
      .find('select')
      .findAll('option')
      .map((option) => option.element.value)

    expect(options).toEqual([
      '',
      'spam',
      'inappropriate',
      'misinformation',
      'harassment',
      'off_topic',
      'other',
      'suspected_fraud',
      'prohibited_item',
    ])
  })

  it('emits reason and description edits without calling any api', async () => {
    const wrapper = mountForm()

    await wrapper.find('select').setValue('other')
    await wrapper.find('textarea').setValue('Counterfeit seeds')

    expect(wrapper.emitted('update:reason')).toEqual([['other']])
    expect(wrapper.emitted('update:description')).toEqual([['Counterfeit seeds']])
    expect(wrapper.emitted('submit')).toBeUndefined()
  })

  it('caps the description at 1000 characters with a live counter', async () => {
    const wrapper = mountForm({ description: 'hello' })
    const textarea = wrapper.find('textarea')

    expect(textarea.attributes('maxlength')).toBe('1000')
    expect(wrapper.text()).toContain('5 / 1000')
  })

  it('explains that other requires a description', () => {
    const wrapper = mountForm({ reason: 'other' })

    expect(wrapper.text()).toContain('Description is required')
  })

  it('emits submit and cancel', async () => {
    const wrapper = mountForm({ reason: 'spam' })

    await wrapper.find('form').trigger('submit')
    await wrapper
      .findAll('button')
      .find((button) => button.text() === 'Cancel')
      .trigger('click')

    expect(wrapper.emitted('submit')).toHaveLength(1)
    expect(wrapper.emitted('cancel')).toHaveLength(1)
  })

  it('renders the submit error inside the form', () => {
    const wrapper = mountForm({ error: 'Too many reports. Try again later.' })
    const alert = wrapper.find('[role="alert"]')

    expect(alert.exists()).toBe(true)
    expect(alert.text()).toContain('Too many reports')
  })

  it('disables actions while busy', () => {
    const wrapper = mountForm({ busy: true })

    for (const button of wrapper.findAll('button')) {
      expect(button.attributes('disabled')).toBeDefined()
    }
  })
})
