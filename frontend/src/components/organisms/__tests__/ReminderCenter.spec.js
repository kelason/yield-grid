import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import i18n, { setInsuranceLocale } from '@/i18n'
import ReminderCenter from '../ReminderCenter.vue'

describe('ReminderCenter', () => {
  beforeEach(() => {
    setInsuranceLocale('en')
  })

  function mountCenter(reminders) {
    return mount(ReminderCenter, {
      props: { reminders },
      global: { plugins: [i18n] },
    })
  }

  it('renders reminders with interpolated translations', () => {
    const wrapper = mountCenter([
      {
        type: 'enrollment_window',
        title_key: 'insurance.reminders.enrollment_window.title',
        message_key: 'insurance.reminders.enrollment_window.message',
        params: { season: 'wet', season_year: 2026, window_label: 'May 1 – Jul 31' },
        sent_at: '2026-04-01T00:00:00Z',
      },
    ])

    expect(wrapper.text()).toContain('Enrollment window opening')
    expect(wrapper.text()).toContain('wet 2026 planting window (May 1 – Jul 31)')
  })

  it('renders translated reminders in Tagalog', () => {
    setInsuranceLocale('tl')
    const wrapper = mountCenter([
      {
        type: 'renewal',
        title_key: 'insurance.reminders.renewal.title',
        message_key: 'insurance.reminders.renewal.message',
        params: { expires_at: '2026-12-01' },
        sent_at: '2026-11-01T00:00:00Z',
      },
    ])

    expect(wrapper.text()).toContain('Pag-renew ng polisa')
    expect(wrapper.text()).toContain('2026-12-01')
  })

  it('shows an empty state when there are no reminders', () => {
    const wrapper = mountCenter([])

    expect(wrapper.get('[data-testid="reminders-empty"]').text()).toContain('caught up')
  })
})
