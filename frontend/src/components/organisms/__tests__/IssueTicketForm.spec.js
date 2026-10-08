import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import IssueTicketForm from '../IssueTicketForm.vue'

describe('IssueTicketForm.vue', () => {
  function mountForm(props = {}) {
    return mount(IssueTicketForm, {
      props: {
        category: '',
        subject: '',
        description: '',
        pagePath: '',
        ...props,
      },
    })
  }

  it('offers all five issue categories', () => {
    const wrapper = mountForm()
    const options = wrapper
      .find('#issue-category')
      .findAll('option')
      .map((option) => option.element.value)

    expect(options).toEqual(['', 'technical', 'account', 'marketplace', 'payment', 'other'])
    expect(wrapper.text()).toContain('Payment')
  })

  it('warns members not to include passwords or payment credentials', () => {
    const wrapper = mountForm()
    const text = wrapper.text()

    expect(text).toContain('Do not include passwords or payment credentials')
  })

  it('emits field edits without submitting', async () => {
    const wrapper = mountForm()

    await wrapper.find('#issue-category').setValue('payment')
    await wrapper.find('#issue-subject').setValue('Charged twice')
    await wrapper.find('#issue-description').setValue('My card shows two charges.')
    await wrapper.find('#issue-page-path').setValue('/dashboard/buyer/purchases')

    expect(wrapper.emitted('update:category')).toEqual([['payment']])
    expect(wrapper.emitted('update:subject')).toEqual([['Charged twice']])
    expect(wrapper.emitted('update:description')).toEqual([['My card shows two charges.']])
    expect(wrapper.emitted('update:pagePath')).toEqual([['/dashboard/buyer/purchases']])
    expect(wrapper.emitted('submit')).toBeUndefined()
  })

  it('emits submit from the form action', async () => {
    const wrapper = mountForm({
      category: 'technical',
      subject: 'Map fails',
      description: 'The plot map never loads.',
    })

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.emitted('submit')).toHaveLength(1)
  })

  it('requests the current page path without reading the full URL itself', async () => {
    const wrapper = mountForm()

    await wrapper.find('[data-testid="issue-use-current-page"]').trigger('click')

    expect(wrapper.emitted('use-current-page')).toHaveLength(1)
    expect(wrapper.emitted('update:pagePath')).toBeUndefined()
  })

  it('emits new-draft when the member starts over', async () => {
    const wrapper = mountForm({ subject: 'Old draft' })

    await wrapper.find('[data-testid="issue-new-draft"]').trigger('click')

    expect(wrapper.emitted('new-draft')).toHaveLength(1)
  })

  it('renders field errors and the submit error accessibly', () => {
    const wrapper = mountForm({
      errors: {
        category: 'Choose a category.',
        subject: 'Enter a subject.',
        description: '',
        pagePath: 'Use a relative path like /dashboard/issues.',
      },
      submitError: 'Unable to submit this issue.',
    })
    const text = wrapper.text()

    expect(text).toContain('Choose a category.')
    expect(text).toContain('Enter a subject.')
    expect(text).toContain('Use a relative path like /dashboard/issues.')

    const alert = wrapper.find('[data-testid="issue-submit-error"]')
    expect(alert.exists()).toBe(true)
    expect(alert.attributes('role')).toBe('alert')
    expect(alert.text()).toBe('Unable to submit this issue.')
  })

  it('enforces input bounds through maxlength attributes', () => {
    const wrapper = mountForm()

    expect(wrapper.find('#issue-subject').attributes('maxlength')).toBe('150')
    expect(wrapper.find('#issue-description').attributes('maxlength')).toBe('5000')
    expect(wrapper.find('#issue-page-path').attributes('maxlength')).toBe('255')
  })

  it('disables every control while busy', () => {
    const wrapper = mountForm({ busy: true })

    expect(wrapper.find('#issue-category').attributes('disabled')).toBeDefined()
    expect(wrapper.find('#issue-subject').attributes('disabled')).toBeDefined()
    expect(wrapper.find('#issue-description').attributes('disabled')).toBeDefined()
    expect(wrapper.find('#issue-page-path').attributes('disabled')).toBeDefined()
    expect(
      wrapper.find('[data-testid="issue-use-current-page"]').attributes('disabled'),
    ).toBeDefined()
    expect(wrapper.find('[data-testid="issue-new-draft"]').attributes('disabled')).toBeDefined()
  })
})
