import { setActivePinia, createPinia } from 'pinia'
import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useApi } from '@/composables/useApi'
import CashPaymentApprovalsPage from '../CashPaymentApprovalsPage.vue'
import ConfirmModal from '@/components/molecules/ConfirmModal.vue'
import { useMarketStore } from '@/stores/marketStore'

vi.mock('@/composables/useApi', () => ({
  useApi: vi.fn(),
}))

vi.mock('@/composables/useChatEntry', () => ({
  useChatEntry: () => ({ openChat: vi.fn() }),
}))

describe('CashPaymentApprovalsPage.vue amount cap', () => {
  const purchase = {
    id: 7,
    payment_status: 'pending',
    cash_payment_status: 'pending',
    payment_method: 'cash',
    total_contract_amount: 50000,
    cash_amount_confirmed: 0,
    currency: 'PHP',
    created_at: '2026-09-01T00:00:00Z',
    buyer: { id: 3, name: 'Buyer' },
    contract: { title: 'Rice Harvest' },
  }

  let pinia

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    useApi.mockReturnValue({
      get: vi.fn().mockResolvedValue({ data: [purchase] }),
      post: vi.fn(),
    })
  })

  async function mountPage() {
    const wrapper = mount(CashPaymentApprovalsPage, {
      global: { plugins: [pinia], stubs: { teleport: true } },
    })
    await flushPromises()
    return wrapper
  }

  async function openPartialModal(wrapper) {
    const partialButton = wrapper
      .findAll('button')
      .find((button) => button.text() === 'Approve 10%')
    await partialButton.trigger('click')
  }

  it('caps the approval amount at 8 characters', async () => {
    const wrapper = await mountPage()
    await openPartialModal(wrapper)

    const input = wrapper.find('input[type="number"]')
    expect(input.attributes('maxlength')).toBe('8')

    await input.setValue('123456789')

    expect(input.element.value).toBe('12345678')
  })

  it('confirms the exact bounded amount before approving and prevents duplicate requests', async () => {
    const wrapper = await mountPage()
    const store = useMarketStore()
    let resolve
    const approve = vi.spyOn(store, 'approveCashPayment').mockImplementation(
      () =>
        new Promise((done) => {
          resolve = done
        }),
    )
    await openPartialModal(wrapper)
    await wrapper.find('input[type="number"]').setValue('1234.56')
    await wrapper.find('form').trigger('submit')
    expect(approve).not.toHaveBeenCalled()
    const confirmation = wrapper.findComponent(ConfirmModal)
    expect(confirmation.props('message')).toContain('1,234.56')
    confirmation.vm.$emit('confirm')
    await flushPromises()
    confirmation.vm.$emit('confirm')
    expect(approve).toHaveBeenCalledTimes(1)
    expect(approve).toHaveBeenCalledWith(7, 'partial', 1234.56)
    expect(confirmation.props('loading')).toBe(true)
    resolve()
    await flushPromises()
    wrapper.unmount()
  })
})
