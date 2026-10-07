import { describe, it, expect, vi } from 'vitest'
import { ref } from 'vue'
import { useProfileAddresses } from '../useProfileAddresses'

function setup() {
  const isOwnProfile = ref(true)
  const addressStore = {
    addresses: [{ id: 8 }],
    createAddress: vi.fn().mockResolvedValue({}),
    updateAddress: vi.fn().mockResolvedValue({}),
    deleteAddress: vi.fn().mockResolvedValue({}),
  }
  const notificationStore = { error: vi.fn(), success: vi.fn() }
  const state = useProfileAddresses({
    addressStore,
    isOwnProfile,
    notificationStore,
    onSaved: vi.fn(),
  })
  return { ...state, isOwnProfile, addressStore }
}
describe('profile address ownership and payloads', () => {
  it('captures a valid draft, confirms once and retains it after server failure', async () => {
    const state = setup()
    state.openAddAddress()
    Object.assign(state.addressDraft.value, {
      label: 'Home',
      street: '',
      region_code: '13',
      province_code: '',
      city_municipality_code: '1374',
      barangay_code: '137401',
      latitude: 14.6,
      longitude: 121,
    })
    state.addressStore.createAddress.mockRejectedValue({
      response: { data: { message: 'Address service unavailable.' } },
    })
    state.requestSaveAddress()
    expect(state.addressStore.createAddress).not.toHaveBeenCalled()
    await state.confirmPending()
    expect(state.addressStore.createAddress).toHaveBeenCalledWith({
      label: 'Home',
      region_code: '13',
      province_code: null,
      city_municipality_code: '1374',
      barangay_code: '137401',
      latitude: 14.6,
      longitude: 121,
      is_default: false,
    })
    expect(state.showAddressModal.value).toBe(true)
    expect(state.addressDraft.value.label).toBe('Home')
    expect(state.addressErrors.value.form).toBe('Address service unavailable.')
  })
  it('keeps the update address ID and sends the cleaned editor payload', async () => {
    const state = setup()
    const address = {
      id: 8,
      label: 'Gate',
      street: 'Farm road',
      region_code: '13',
      province_code: null,
      city_municipality_code: '1374',
      barangay_code: '137401',
      latitude: 14.6,
      longitude: 121,
      is_default: false,
    }
    state.addressStore.addresses = [address]
    state.openEditAddress(address)
    state.addressDraft.value.label = 'Updated gate'
    state.requestSaveAddress()
    await state.confirmPending()
    expect(state.addressStore.updateAddress).toHaveBeenCalledWith(8, {
      ...state.addressDraft.value,
    })
    expect(state.showAddressModal.value).toBe(false)
  })
  it('deletes only the selected owned address after confirmation', async () => {
    const state = setup()
    state.requestDeleteAddress(8)
    expect(state.addressStore.deleteAddress).not.toHaveBeenCalled()
    await state.confirmPending()
    expect(state.addressStore.deleteAddress).toHaveBeenCalledExactlyOnceWith(8)
  })

  it('preserves the existing complete default-address update payload', async () => {
    const state = setup()
    const address = {
      id: 8,
      label: '',
      street: '',
      region_code: '13',
      province_code: null,
      city_municipality_code: '1374',
      barangay_code: '137401',
      latitude: 14.6,
      longitude: 121,
      is_default: false,
    }
    state.addressStore.addresses = [address]
    state.requestDefaultAddress(address)
    await state.confirmPending()
    const payload = { ...address }
    delete payload.id
    expect(state.addressStore.updateAddress).toHaveBeenCalledWith(8, {
      ...payload,
      is_default: true,
    })
  })

  it('does not delete when ownership changes before confirmation', async () => {
    const state = setup()
    state.requestDeleteAddress(8)
    state.isOwnProfile.value = false
    await state.confirmPending()
    expect(state.addressStore.deleteAddress).not.toHaveBeenCalled()
    state.openAddAddress()
    expect(state.showAddressModal.value).toBe(false)
  })
})
